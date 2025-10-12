<?php

declare(strict_types=1);
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/EvolutionInstance.php';

class AdminEvolutionController extends Controller
{
    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }
        $company = Company::findBySlug($slug);

        if (!$company) {
            echo 'Empresa inválida';
            exit;
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            echo 'Acesso negado';
            exit;
        }

        return [$u,$company];
    }

    private function evolutionApiRequest(array $company, string $path, string $method = 'GET', ?array $body = null): array
    {
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? null;

        if (!$server || !$apiKey) {
            return ['error' => 'Configuração Evolution ausente (SERVER_URL e AUTHENTICATION_API_KEY).'];
        }

        // internal helper to do a single request
        $doRequest = function(string $fullUrl) use ($method, $body, $apiKey) {
            $ch = curl_init($fullUrl);
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                // alguns provedores esperam 'apikey' ou 'Authorization: Bearer'
                'Authentication-Api-Key: ' . $apiKey,
                'apikey: ' . $apiKey,
                'Authorization: Bearer ' . $apiKey,
            ];

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $resp = curl_exec($ch);
            $err  = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                return ['err' => true, 'message' => $err];
            }

            $data = json_decode($resp, true);
            return ['err' => false, 'code' => $code, 'raw' => $resp, 'data' => $data];
        };

        // try saved prefix first (fast path)
        $savedPrefix = $this->getDetectedPrefix((int)($company['id'] ?? 0));
        $candidates = [''];
        if ($savedPrefix) $candidates = array_merge([$savedPrefix], $candidates);

    // common prefixes to try if not found
    $common = ['api','api/v2','v2','api/v1','evolution','api/evolution','api/v2/evolution','whatsapp','api/whatsapp','wa','api/wa'];
        foreach ($common as $p) {
            if (!in_array($p, $candidates, true)) $candidates[] = $p;
        }

        foreach ($candidates as $prefix) {
            $prefix = trim((string)$prefix, '/');
            $full = $server;
            if ($prefix !== '') $full .= '/' . $prefix;
            $full .= '/' . ltrim($path, '/');

            $res = $doRequest($full);
            if ($res['err']) {
                // network/curl error -> return immediately
                return ['error' => 'cURL error: ' . $res['message']];
            }

            // if not found, try next candidate
            if ($res['code'] === 404) {
                continue;
            }

            if ($res['code'] >= 400) {
                $msg = $res['raw'] ?? ($res['data']['message'] ?? '');
                return ['error' => 'HTTP ' . $res['code'] . ' - ' . ($msg ?: 'error')];
            }

            // success -> save detected prefix (if any) and return data
            if ($prefix !== '') {
                $this->saveDetectedPrefix((int)($company['id'] ?? 0), $prefix);
            }

            return ['data' => $res['data']];
        }

        return ['error' => 'Nenhum endpoint válido encontrado (tente ajustar o base URL / prefix nas configurações).'];
    }

    private function makeEvolutionClient(array $company)
    {
        // preferir usar client oficial se disponível
        if (!class_exists('\EvolutionApiPlugin\\EvolutionApi')) {
            return null;
        }

        $apiKey = $company['evolution_api_key'] ?? null;
        $apiUrl = $company['evolution_server_url'] ?? null;
        try {
            return new \EvolutionApiPlugin\EvolutionApi($apiKey, $apiUrl);
        } catch (Throwable $e) {
            return null;
        }
    }

    private function getDetectedPrefix(int $companyId): ?string
    {
        if ($companyId <= 0) return null;
        $f = sys_get_temp_dir() . "/evolution_prefix_{$companyId}.txt";
        if (!file_exists($f)) return null;
        $v = trim((string)@file_get_contents($f));
        return $v === '' ? null : $v;
    }

    private function isAjax(): bool
    {
        $h = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        if (strtolower($h) === 'xmlhttprequest') return true;
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) return true;
        return false;
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function saveDetectedPrefix(int $companyId, string $prefix): void
    {
        if ($companyId <= 0) return;
        $f = sys_get_temp_dir() . "/evolution_prefix_{$companyId}.txt";
        @file_put_contents($f, $prefix);
    }

    public function index($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $instances = EvolutionInstance::allForCompany((int)$company['id']);

        // tentar buscar instâncias remotas se configuração presente
        $remote = [];
    // prefer v2 endpoint used by official Evolution API
    $res = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
        if (!isset($res['error']) && isset($res['data'])) {
            // normaliza formatos: aceita lista direta ou data->instances
            $data = $res['data'];
            if (isset($data['instances']) && is_array($data['instances'])) {
                $remote = $data['instances'];
            } elseif (is_array($data)) {
                $remote = $data;
            }
        }

        return $this->view('admin/evolution/index', compact('company','instances','remote'));
    }

    public function instances($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $instances = EvolutionInstance::allForCompany((int)$company['id']);
        // também expõe instâncias remotas para listagem (useful for AJAX)
        $remote = [];
    $res = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
        if (!isset($res['error']) && isset($res['data'])) {
            $data = $res['data'];
            if (isset($data['instances']) && is_array($data['instances'])) {
                $remote = $data['instances'];
            } elseif (is_array($data)) {
                $remote = $data;
            }
        }

        return $this->view('admin/evolution/instances', compact('company','instances','remote'));
    }

    public function import_remote($params)
    {
        [$u,$company] = $this->guard($params['slug']);

        $json = $this->getJsonBody();
        $instance_identifier = trim($json['instance_identifier'] ?? $_POST['instance_identifier'] ?? '');
        $number = trim($json['number'] ?? $_POST['number'] ?? '');
        $label = trim($json['label'] ?? $_POST['label'] ?? '');
        $qr_code = trim($json['qr_code'] ?? $_POST['qr_code'] ?? null);
        $status = trim($json['status'] ?? $_POST['status'] ?? 'pending');

        if ($instance_identifier === '') {
            if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => 'Identificador da instância ausente']); return; }
            $_SESSION['flash_error'] = 'Identificador da instância ausente';
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }

        // evita duplicatas: verifica se já existe
        $existing = EvolutionInstance::allForCompany((int)$company['id']);
        foreach ($existing as $e) {
            if ($e['instance_identifier'] && $e['instance_identifier'] === $instance_identifier) {
                if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => 'Instância já importada']); return; }
                $_SESSION['flash_error'] = 'Instância já importada';
                header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
                exit;
            }
        }

        EvolutionInstance::create((int)$company['id'], [
            'label' => $label,
            'number' => $number,
            'instance_identifier' => $instance_identifier,
            'qr_code' => $qr_code,
            'status' => $status,
        ]);

        if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['ok' => true, 'instance_identifier' => $instance_identifier]); return; }
        $_SESSION['flash_success'] = 'Instância importada com sucesso';
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    /**
     * Busca uma instância remota pelo identificador/nome e importa para o DB.
     * Método reutilizável para testes (aceita company array e instance_identifier)
     */
    private function importInstanceByIdentifier(array $company, string $instance_identifier): array
    {
        if (!$instance_identifier) return ['error' => 'instance_identifier vazio'];

        $client = $this->makeEvolutionClient($company);
        if (!$client) {
            // tentar via request manual
            $res = $this->evolutionApiRequest($company, '/instance/fetchInstances?instanceName=' . rawurlencode($instance_identifier), 'GET', null);
            if (isset($res['error'])) return ['error' => $res['error']];
            $data = $res['data'] ?? null;
        } else {
            try {
                $data = $client->fetchInstance($instance_identifier);
            } catch (\Throwable $e) {
                return ['error' => 'Client error: ' . $e->getMessage()];
            }
        }

        if (!$data) return ['error' => 'Nenhum dado retornado'];

        // extrair campos conhecidos
        $instance_identifier_res = $data['instance_identifier'] ?? ($data['id'] ?? ($data['instanceName'] ?? $instance_identifier));
        $number = $data['number'] ?? $data['phone'] ?? null;
        $qr = $data['qr_code'] ?? $data['qr'] ?? null;
        $status = $data['status'] ?? $data['state'] ?? 'pending';

        // evita duplicatas
        $existing = EvolutionInstance::allForCompany((int)$company['id']);
        foreach ($existing as $e) {
            if ($e['instance_identifier'] && $e['instance_identifier'] === $instance_identifier_res) {
                return ['error' => 'Instância já existe localmente'];
            }
        }

        EvolutionInstance::create((int)$company['id'], [
            'label' => $data['label'] ?? $data['name'] ?? $number,
            'number' => $number,
            'instance_identifier' => $instance_identifier_res,
            'qr_code' => $qr,
            'status' => $status,
        ]);

        return ['ok' => true, 'instance_identifier' => $instance_identifier_res];
    }

    public function fetch_and_import($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $json = $this->getJsonBody();
        $instance_identifier = trim($json['instance_identifier'] ?? $_POST['instance_identifier'] ?? '');
        if ($instance_identifier === '') {
            if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => 'Informe o identificador da instância']); return; }
            $_SESSION['flash_error'] = 'Informe o identificador da instância';
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }

        $res = $this->importInstanceByIdentifier($company, $instance_identifier);
        if (isset($res['error'])) {
            if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => $res['error']]); return; }
            $_SESSION['flash_error'] = 'Erro: ' . $res['error'];
        } else {
            if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['ok' => true, 'instance_identifier' => ($res['instance_identifier'] ?? '')]); return; }
            $_SESSION['flash_success'] = 'Instância importada: ' . ($res['instance_identifier'] ?? '');
        }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function sync($params)
    {
        [$u,$company] = $this->guard($params['slug']);

        // candidate paths to list instances; prioritize v2-style endpoints
        $candidates = [
            '/instance/fetchInstances',
            '/instance/getAll',
            '/instances',
            '/api/instances',
            '/api/v2/instances',
            '/instance/list',
            '/v2/instances',
        ];

        $all = null;
        foreach ($candidates as $p) {
            $res = $this->evolutionApiRequest($company, $p, 'GET', null);
            if (!isset($res['error']) && isset($res['data'])) {
                $data = $res['data'];
                // normalize: if response contains an object with key 'instances' or 'data'
                if (isset($data['instances']) && is_array($data['instances'])) {
                    $all = $data['instances'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $all = $data['data'];
                } elseif (is_array($data)) {
                    $all = $data;
                }
                if (is_array($all)) break;
            }
        }

        if (!is_array($all)) {
            if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => ($res['error'] ?? 'sem resposta')]); return; }
            $_SESSION['flash_error'] = 'Não foi possível listar instâncias remotas: ' . ($res['error'] ?? 'sem resposta');
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }

        $imported = 0;
        $skipped = 0;
        $existing = EvolutionInstance::allForCompany((int)$company['id']);
        $existingIds = array_column($existing, 'instance_identifier');

        foreach ($all as $item) {
            // normalize item to associative array
            if (!is_array($item)) continue;
            $instance_identifier = $item['instance_identifier'] ?? ($item['id'] ?? ($item['instanceName'] ?? null));
            if (!$instance_identifier) continue;
            if (in_array($instance_identifier, $existingIds, true)) { $skipped++; continue; }

            $number = $item['number'] ?? $item['phone'] ?? null;
            $qr = $item['qr_code'] ?? $item['qr'] ?? null;
            $label = $item['label'] ?? $item['name'] ?? $number;
            $status = $item['status'] ?? $item['state'] ?? 'pending';

            EvolutionInstance::create((int)$company['id'], [
                'label' => $label,
                'number' => $number,
                'instance_identifier' => $instance_identifier,
                'qr_code' => $qr,
                'status' => $status,
            ]);
            $imported++;
        }

        $_SESSION['flash_success'] = "Sincronização concluída: importadas={$imported}, puladas={$skipped}";
        if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['ok' => true, 'imported' => $imported, 'skipped' => $skipped]); return; }
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function create($params)
    {
        [$u,$company] = $this->guard($params['slug']);

        $json = $this->getJsonBody();
        $label = trim($json['label'] ?? $_POST['label'] ?? '');
        $number = trim($json['number'] ?? $_POST['number'] ?? '');

        if ($number === '') {
            $_SESSION['flash_error'] = 'Informe o número/identificador da instância.';
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }
        $instance_identifier = null;
        $qr = null;
        $status = 'pending';

        $client = $this->makeEvolutionClient($company);
        if ($client) {
            try {
                // try creating instance using number as instanceName and api key as token
                $resp = $client->createInstance($number, $company['evolution_api_key'] ?? '', true);
                if (is_array($resp)) {
                    $instance_identifier = $resp['instance_identifier'] ?? ($resp['id'] ?? $resp['instanceName'] ?? null);
                    $qr = $resp['qr_code'] ?? $resp['qr'] ?? null;
                    $status = $resp['status'] ?? $resp['state'] ?? 'pending';
                }
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Erro API: ' . $e->getMessage();
            }
        } else {
            // Postman/official endpoints use /instance/createInstance for v2
            $res = $this->evolutionApiRequest($company, '/instance/createInstance', 'POST', ['instanceName' => $number, 'token' => ($company['evolution_api_key'] ?? '')]);

            if (isset($res['error'])) {
                $_SESSION['flash_error'] = 'Erro API: ' . $res['error'];
            } else {
                $data = $res['data'] ?? [];
                $instance_identifier = $data['instance_identifier'] ?? ($data['id'] ?? null);
                $qr = $data['qr_code'] ?? $data['qr'] ?? null;
                $status = $data['status'] ?? 'pending';
            }
        }

        EvolutionInstance::create((int)$company['id'], [
            'label' => $label,
            'number' => $number,
            'instance_identifier' => $instance_identifier,
            'qr_code' => $qr,
            'status' => $status,
        ]);

        // If AJAX, return json with created instance (or error if any)
        if ($this->isAjax()) {
            $error = $_SESSION['flash_error'] ?? null;
            if ($error) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $error]);
                return;
            }

            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'instance' => ['instance_identifier' => $instance_identifier, 'qr' => $qr, 'status' => $status, 'label' => $label, 'number' => $number]]);
            return;
        }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function refresh_qr($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $json = $this->getJsonBody();
        $id = (int)($json['id'] ?? $_POST['id'] ?? 0);

        $inst = EvolutionInstance::find($id);
        if (!$inst) {
            $_SESSION['flash_error'] = 'Instância não encontrada';
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }

        if (!$inst['instance_identifier']) {
            $_SESSION['flash_error'] = 'Instância não possui identificador remoto';
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }

        $client = $this->makeEvolutionClient($company);
        if ($client) {
            try {
                $info = $client->fetchInstance($inst['instance_identifier']);
                $qr = $info['qr_code'] ?? $info['qr'] ?? null;
                EvolutionInstance::update($id, ['qr_code' => $qr, 'status' => $info['status'] ?? $inst['status']]);
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Erro API: ' . $e->getMessage();
                if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => $e->getMessage()]); return; }
            }
        } else {
            // v2 endpoint to fetch instance info/qr
            $res = $this->evolutionApiRequest($company, '/instance/fetchInstances?instanceName=' . rawurlencode($inst['instance_identifier']), 'GET');

            if (isset($res['error'])) {
                $_SESSION['flash_error'] = 'Erro API: ' . $res['error'];
                if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => $res['error']]); return; }
            } else {
                $data = $res['data'] ?? [];
                $qr = $data['qr_code'] ?? $data['qr'] ?? null;
                EvolutionInstance::update($id, ['qr_code' => $qr, 'status' => $data['status'] ?? $inst['status']]);
            }
        }

        if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['ok' => true, 'qr' => $qr ?? null]); return; }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function delete($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $json = $this->getJsonBody();
        $id = (int)($json['id'] ?? $_POST['id'] ?? 0);
        $inst = EvolutionInstance::find($id);
        if ($inst) {
            if ($inst['instance_identifier']) {
                $client = $this->makeEvolutionClient($company);
                if ($client) {
                    try {
                        $client->deleteInstance($inst['instance_identifier']);
                    } catch (\Throwable $e) {
                        if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['error' => $e->getMessage()]); return; }
                        // swallow - we still delete local record
                    }
                } else {
                    // v2 endpoint to delete instance
                    $this->evolutionApiRequest($company, '/instance/deleteInstance', 'POST', ['instanceName' => $inst['instance_identifier']]);
                }
            }
            EvolutionInstance::delete($id);
        }

        if ($this->isAjax()) { header('Content-Type: application/json'); echo json_encode(['ok' => true]); return; }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }
}
