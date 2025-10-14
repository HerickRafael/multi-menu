<?php
declare(strict_types=1);
// app/services/EvolutionNotifier.php

require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/EvolutionInstance.php';

class EvolutionNotifier
{
    // target number fixed as requested
    public static function TARGET_NUMBER(): string { return '+5551920017687'; }

    public static function notifyOrderCreated(array $company, array $orderRow, array $items): void
    {
        // ensure composer autoload is available when running from CLI scripts
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (file_exists($autoload)) {
            try { require_once $autoload; } catch (Throwable $e) { /* ignore */ }
        }

        // only send for specific company slug 'wollburger' as requested
        if (!isset($company['slug']) || $company['slug'] !== 'wollburger') {
            return;
        }

        $companyId = (int)($company['id'] ?? 0);

        // choose best instance: prefer connected
        $instances = EvolutionInstance::allForCompany($companyId);
        $chosen = null;
        foreach ($instances as $inst) {
            if (!empty($inst['instance_identifier']) && !empty($inst['status']) && strtolower($inst['status']) === 'connected') {
                $chosen = $inst;
                break;
            }
        }
        if (!$chosen && !empty($instances)) {
            // fallback to first with identifier
            foreach ($instances as $inst) {
                if (!empty($inst['instance_identifier'])) { $chosen = $inst; break; }
            }
        }

        if (!$chosen) {
            error_log('EvolutionNotifier: no evolution instance found for company ' . $companyId);
            return;
        }

        $toOriginal = self::TARGET_NUMBER();

        // generate candidate number variants (digits only)
        $clean = preg_replace('/[^0-9]/', '', $toOriginal);
        $variants = [];
        if ($clean !== '') $variants[] = $clean; // raw digits
        // with leading plus
        $variants[] = '+' . $clean;
        // ensure country code 55
        if (strpos($clean, '55') !== 0) {
            $variants[] = '55' . $clean;
            $variants[] = '+55' . $clean;
        }
        // remove double country prefix if present (guard)
        if (strpos($clean, '555') === 0) {
            $variants[] = preg_replace('/^55/', '', $clean);
        }

        // normalize unique order, prefer those without + for API calls
        $seen = [];
        $numberCandidates = [];
        foreach ($variants as $v) {
            $plain = preg_replace('/[^0-9]/', '', $v);
            if ($plain === '' || isset($seen[$plain])) continue;
            $seen[$plain] = true;
            // prefer plain digits then + version
            $numberCandidates[] = $plain;
            $numberCandidates[] = '+' . $plain;
        }

        $to = null;

        // build message
        $lines = [];
        $lines[] = "Novo pedido #" . ($orderRow['id'] ?? '');
        $lines[] = 'Empresa: ' . ($company['name'] ?? '');
        $lines[] = 'Cliente: ' . ($orderRow['customer_name'] ?? '') . ' (' . ($orderRow['customer_phone'] ?? '') . ')';
        $lines[] = 'Total: R$ ' . number_format((float)($orderRow['total'] ?? 0), 2, ',', '.');
        if (!empty($orderRow['customer_address'])) {
            $lines[] = "Endereço: " . str_replace("\n", ' / ', $orderRow['customer_address']);
        }
        if (!empty($orderRow['notes'])) {
            $lines[] = 'Notas: ' . str_replace("\n", ' ', $orderRow['notes']);
        }
        $lines[] = 'Itens:';
        foreach ($items as $it) {
            $qty = (int)($it['quantity'] ?? $it['qty'] ?? 0);
            $name = $it['product_name'] ?? $it['name'] ?? '';
            $lineTotal = number_format((float)($it['line_total'] ?? 0), 2, ',', '.');
            $lines[] = "- {$qty} x {$name} (R$ {$lineTotal})";
        }

        $message = implode("\n", $lines);

        // try official client if available
        $instanceNameCandidates = [];
        if (!empty($chosen['label'])) $instanceNameCandidates[] = $chosen['label'];
        if (!empty($chosen['instance_identifier'])) $instanceNameCandidates[] = $chosen['instance_identifier'];

        if (class_exists('\EvolutionApiPlugin\\EvolutionApi')) {
            try {
                $apiKey = $company['evolution_api_key'] ?? null;
                $apiUrl = rtrim($company['evolution_server_url'] ?? '', '/');
                // prefer v2 API which maps to the endpoints used in scripts
                $client = new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl, 'v2');

                // determine send method
                if (method_exists($client, 'sendTextMessage')) {
                    $sendFn = 'sendTextMessage';
                } elseif (method_exists($client, 'sendMessage')) {
                    $sendFn = 'sendMessage';
                } else {
                    $sendFn = null;
                }

                foreach ($instanceNameCandidates as $instanceName) {
                    foreach ($numberCandidates as $candidate) {
                        if ($candidate === '') continue;
                        try {
                            error_log('EvolutionNotifier: client trying instance="' . $instanceName . '" to="' . $candidate . '"');
                            if ($sendFn) {
                                $resp = $client->{$sendFn}($instanceName, $candidate, $message);
                            } else {
                                // attempt best-effort methods
                                if (method_exists($client, 'sendTextMessage')) {
                                    $resp = $client->sendTextMessage($instanceName, $candidate, $message);
                                } else {
                                    throw new \Exception('No send method on client');
                                }
                            }

                            error_log('EvolutionNotifier: client response: ' . json_encode($resp));
                            // success heuristics
                            if (is_array($resp) && ((!empty($resp['status']) && in_array(strtoupper($resp['status']), ['PENDING','SENT','SUCCESS'], true)) || !empty($resp['key']) || !empty($resp['id']) || !empty($resp['remoteJid']))) {
                                error_log('EvolutionNotifier: sent via client using ' . $candidate . ' @ ' . $instanceName);
                                return;
                            }
                        } catch (\Throwable $e) {
                            error_log('EvolutionNotifier client err for ' . $candidate . ' @ ' . $instanceName . ': ' . $e->getMessage());
                            continue;
                        }
                    }
                }
                // if we reached here without sending text, try to send PDF as attachment
                try {
                    require_once __DIR__ . '/ThermalReceipt.php';
                    $pdfPath = ThermalReceipt::generatePdf($company, $orderRow, $items, $message);
                    if (file_exists($pdfPath)) {
                        $b64 = base64_encode(file_get_contents($pdfPath));
                        $fileName = 'pedido_' . ($orderRow['id'] ?? 'order') . '.pdf';
                        // use the formatted text message as caption so full order details appear in the chat
                        $caption = substr($message, 0, 1000); // cap caption length
                        $media = $client->createMediaStructure('document', 'application/pdf', $caption, $b64, $fileName);
                        foreach ($instanceNameCandidates as $instanceName) {
                            foreach ($numberCandidates as $candidate) {
                                if ($candidate === '') continue;
                                try {
                                    error_log('EvolutionNotifier: client sending media to ' . $candidate . ' @ ' . $instanceName);
                                    $mres = $client->sendMediaMessage($instanceName, $candidate, $media);
                                    error_log('EvolutionNotifier media response: ' . json_encode($mres));
                                    if (is_array($mres) && (!empty($mres['status']) || !empty($mres['id']) || !empty($mres['key']))) {
                                        // cleanup file
                                        @unlink($pdfPath);
                                        return;
                                    }
                                } catch (Throwable $e) {
                                    error_log('EvolutionNotifier media error: ' . $e->getMessage());
                                    continue;
                                }
                            }
                        }
                        @unlink($pdfPath);
                    }
                } catch (Throwable $e) {
                    error_log('EvolutionNotifier media send error: ' . $e->getMessage());
                }
            } catch (Throwable $e) {
                error_log('EvolutionNotifier client error: ' . $e->getMessage());
                // fallback to HTTP
            }
        }

        // fallback: manual HTTP POST to candidate paths
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? null;
        if (!$server || !$apiKey) {
            error_log('EvolutionNotifier: missing server or apiKey');
            return;
        }

        $endpointCandidates = ['/message/sendText']; // API Evolution v2.3 endpoint oficial

        // try each endpoint x instanceName x numberCandidate
        foreach ($endpointCandidates as $path) {
            $url = $server . '/' . ltrim($path, '/');
            foreach ($instanceNameCandidates as $instanceName) {
                foreach ($numberCandidates as $candidate) {
                    if ($candidate === '') continue;
                    $ch = curl_init($url);
                    $payload = json_encode(['instanceName' => $instanceName, 'to' => $candidate, 'message' => $message]);
                    $headers = [
                        'Accept: application/json',
                        'Content-Type: application/json',
                        'Authentication-Api-Key: ' . $apiKey,
                        'apikey: ' . $apiKey,
                        'Authorization: Bearer ' . $apiKey,
                    ];
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                    $respRaw = curl_exec($ch);
                    $err = curl_error($ch);
                    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($err) {
                        error_log('EvolutionNotifier curl err for ' . $candidate . ' @ ' . $instanceName . ' path=' . $path . ': ' . $err);
                        continue;
                    }

                    $decoded = null;
                    if ($respRaw !== null) {
                        $decoded = json_decode($respRaw, true);
                    }

                    error_log('EvolutionNotifier HTTP ' . $code . ' for ' . $candidate . ' @ ' . $instanceName . ' path=' . $path . ' resp=' . substr($respRaw ?? '', 0, 1000));

                    if ($code >= 200 && $code < 300) {
                        // success
                        return;
                    }

                    // some providers return 200 with payload containing status
                    if (is_array($decoded) && (!empty($decoded['status']) || !empty($decoded['key']) || !empty($decoded['id']) || !empty($decoded['remoteJid']))) {
                        return;
                    }
                }
            }
        }

        error_log('EvolutionNotifier: failed to send message for order ' . ($orderRow['id'] ?? '')); 
    }
}
