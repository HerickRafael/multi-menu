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
        
        // CARREGAR A VIEW DE INSTÂNCIAS - DADOS VÊM VIA AJAX DO instancesData()
        // Não precisamos buscar dados aqui, a view usa JavaScript para carregar via AJAX
        
        return $this->view('admin/evolution/instances', compact('company'));
    }
    
    /**
     * Sincroniza uma nova instância com a API para obter o UUID correto
     */
    private function syncNewInstanceWithApi($company, $instanceName, &$instance_identifier)
    {
        try {
            // Aguardar um pouco para a instância ser processada na API
            sleep(2);
            
            // Buscar todas as instâncias da API
            $res = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
            if (!isset($res['error']) && isset($res['data'])) {
                $data = $res['data'];
                $remote = [];
                
                if (isset($data['instances']) && is_array($data['instances'])) {
                    $remote = $data['instances'];
                } elseif (is_array($data)) {
                    $remote = $data;
                }
                
                // Procurar a instância pelo instanceName
                foreach ($remote as $remoteInst) {
                    $remoteName = $remoteInst['instanceName'] ?? $remoteInst['name'] ?? null;
                    $remoteId = $remoteInst['id'] ?? $remoteInst['instance_identifier'] ?? null;
                    
                    if ($remoteName === $instanceName && $remoteId && $remoteId !== $instanceName) {
                        // Encontrou! Atualizar com o UUID real
                        $instance_identifier = $remoteId;
                        error_log("Nova instância '$instanceName' sincronizada com UUID: $remoteId");
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Erro ao sincronizar nova instância '$instanceName': " . $e->getMessage());
        }
    }
    
    /**
     * Formata um número de telefone brasileiro de forma consistente
     */
    private function formatBrazilianPhone($rawNumber)
    {
        if (empty($rawNumber)) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $cleanNumber = preg_replace('/\D/', '', $rawNumber);
        
        // Se começa com 55 e tem pelo menos 13 dígitos (55 + DDD + 9 dígitos)
        if (strlen($cleanNumber) >= 13 && substr($cleanNumber, 0, 2) === '55') {
            $ddd = substr($cleanNumber, 2, 2);
            $numero = substr($cleanNumber, 4);
            
            if (strlen($numero) === 9) {
                // Celular com 9 dígitos: +55 (11) 9 1234-5678
                return '+55 (' . $ddd . ') ' . substr($numero, 0, 1) . ' ' . substr($numero, 1, 4) . '-' . substr($numero, 5);
            } elseif (strlen($numero) === 8) {
                // Fixo com 8 dígitos: +55 (11) 1234-5678
                return '+55 (' . $ddd . ') ' . substr($numero, 0, 4) . '-' . substr($numero, 4);
            }
        }
        
        // Se não conseguiu formatar, retorna o número limpo com + na frente
        return '+' . $cleanNumber;
    }

    /**
     * Atualiza o instance_identifier de uma instância após criação
     */
    private function updateInstanceIdentifier($instanceId, $newIdentifier)
    {
        try {
            $db = db();
            $stmt = $db->prepare('UPDATE evolution_instances SET instance_identifier = ? WHERE id = ?');
            $stmt->execute([$newIdentifier, $instanceId]);
            error_log("Instance ID $instanceId atualizado para identifier: $newIdentifier");
        } catch (Exception $e) {
            error_log("Erro ao atualizar identifier da instância $instanceId: " . $e->getMessage());
        }
    }

    /**
     * Atualiza o número de telefone de uma instância no banco de dados
     */
    private function updateInstanceNumber($instanceId, $number)
    {
        try {
            $db = db();
            $stmt = $db->prepare('UPDATE evolution_instances SET number = ? WHERE id = ?');
            $stmt->execute([$number, $instanceId]);
        } catch (Exception $e) {
            // Log do erro mas não interrompe o fluxo
            error_log("Erro ao atualizar número da instância $instanceId: " . $e->getMessage());
        }
    }

    public function instancesData($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        
        // BUSCAR INSTÂNCIAS DIRETAMENTE DA API EVOLUTION - SEM BANCO LOCAL
        $remoteInstances = [];
        $res = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
        
        if (isset($res['error'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $res['error'], 'instances' => []]);
            return;
        }
        
        if (isset($res['data'])) {
            $data = $res['data'];
            if (isset($data['instances']) && is_array($data['instances'])) {
                $remoteInstances = $data['instances'];
            } elseif (is_array($data)) {
                $remoteInstances = $data;
            }
        }
        
        // Processar instâncias remotas para o formato esperado pelo frontend
        $processedInstances = array_map(function($remoteInst) {
            // Nome da instancia - priorizar 'name' da API
            $instanceName = $remoteInst['name'] ?? $remoteInst['instanceName'] ?? 'Instance';
            
            // Status da conexão
            $connectionStatus = $remoteInst['connectionStatus'] ?? $remoteInst['status'] ?? 'disconnected';
            
            // Contadores de mensagens e chats
            $chatCount = 0;
            $messageCount = 0;
            if (isset($remoteInst['_count'])) {
                $chatCount = $remoteInst['_count']['Chat'] ?? 0;
                $messageCount = $remoteInst['_count']['Message'] ?? 0;
            }
            
            // Número do WhatsApp - tentar múltiplas fontes
            $rawNumber = '';
            if (isset($remoteInst['number']) && $remoteInst['number']) {
                $rawNumber = $remoteInst['number'];
            } elseif (isset($remoteInst['ownerJid']) && $remoteInst['ownerJid']) {
                // Extrair número do ownerJid (formato: 555194035717@s.whatsapp.net)
                if (preg_match('/^(\d+)@/', $remoteInst['ownerJid'], $matches)) {
                    $rawNumber = $matches[1];
                }
            }
            
            $formattedPhone = $this->formatBrazilianPhone($rawNumber);
            
            // Nome do perfil
            $profileName = $remoteInst['profileName'] ?? 'Contato WhatsApp';
            
            // Avatar baseado no nome do perfil
            $letters = strtoupper(substr($profileName, 0, 2));
            $colors = ['bg-amber-400', 'bg-sky-400', 'bg-emerald-400', 'bg-purple-400', 'bg-pink-400', 'bg-indigo-400'];
            $color = $colors[abs(crc32($profileName . $rawNumber)) % count($colors)];
            
            // Determinar status processado
            $status = 'disconnected';
            $apiStatus = strtolower(trim($connectionStatus));
            if (in_array($apiStatus, ['open', 'connected', 'ready', 'online'])) {
                $status = 'connected';
            } elseif (in_array($apiStatus, ['connecting', 'qr_code', 'qr', 'pairing', 'scan_qr'])) {
                $status = 'pending';
            } else {
                $status = 'disconnected';
            }
            
            // Determinar se deve mostrar o telefone
            $showPhone = ($status === 'connected') && !empty($rawNumber);
            
            return [
                'id' => $remoteInst['id'] ?? null, // UUID da API Evolution
                'instanceName' => $instanceName, // Nome da instância para DELETE
                'instance_name' => $instanceName,
                'contact_name' => $profileName,
                'phone' => $rawNumber,
                'instance_phone' => $formattedPhone,
                'show_phone' => $showPhone,
                'handle' => $rawNumber ? '@' . $rawNumber : '',
                'instance_identifier' => $remoteInst['id'] ?? null, // UUID único da API
                'users' => (string)$chatCount,
                'messages' => (string)$messageCount,
                'status' => $status,
                'avatar' => ['letters' => $letters, 'color' => $color],
                'profile_pic_url' => $remoteInst['profilePicUrl'] ?? null
            ];
        }, $remoteInstances);
        
        header('Content-Type: application/json');
        echo json_encode(['instances' => $processedInstances]);
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
        $isAjax = $this->isAjax() || 
                 (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                 (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        try {
            [$u,$company] = $this->guard($params['slug']);
        } catch (\Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Acesso negado: ' . $e->getMessage()]);
                return;
            }
            throw $e;
        }

        // COM ARQUITETURA API-FIRST, NÃO PRECISAMOS "SINCRONIZAR"
        // Os dados sempre vêm diretamente da API remota
        
        // Vamos apenas verificar se conseguimos acessar a API
        $res = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
        
        if (isset($res['error'])) {
            if ($isAjax) { 
                header('Content-Type: application/json'); 
                echo json_encode(['error' => 'Erro ao acessar API Evolution: ' . $res['error']]); 
                return; 
            }
            $_SESSION['flash_error'] = 'Erro ao acessar API Evolution: ' . $res['error'];
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            return;
        }
        
        $data = $res['data'] ?? [];
        $instanceCount = 0;
        
        // Contar instâncias retornadas
        if (isset($data['instances']) && is_array($data['instances'])) {
            $instanceCount = count($data['instances']);
        } elseif (is_array($data)) {
            $instanceCount = count($data);
        }

        if ($isAjax) { 
            header('Content-Type: application/json'); 
            echo json_encode([
                'ok' => true, 
                'message' => 'Conexão com API Evolution verificada',
                'instance_count' => $instanceCount
            ]); 
            return; 
        }

        $_SESSION['flash_success'] = "Conexão com API Evolution verificada. {$instanceCount} instâncias encontradas.";
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function create($params)
    {
        [$u,$company] = $this->guard($params['slug']);

        $json = $this->getJsonBody();
        $name = trim($json['name'] ?? $_POST['name'] ?? '');

        if ($name === '') {
            $errorMsg = 'Informe o nome da instância.';
            if ($this->isAjax()) {
                header('Content-Type: application/json');
                echo json_encode(['error' => $errorMsg]);
                return;
            }
            $_SESSION['flash_error'] = $errorMsg;
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            exit;
        }

        // CRIAR DIRETAMENTE NA API EVOLUTION (sem salvar localmente)
        $instanceName = $name;
        
        // Tentar via client oficial primeiro
        $client = $this->makeEvolutionClient($company);
        if ($client) {
            try {
                $resp = $client->createInstance($instanceName, $company['evolution_api_key'] ?? '', true);
                if (is_array($resp)) {
                    $instance_identifier = $resp['instance_identifier'] ?? ($resp['id'] ?? $resp['instanceName'] ?? null);
                    $qr = $resp['qr_code'] ?? $resp['qr'] ?? null;
                    $status = $resp['status'] ?? $resp['state'] ?? 'close';
                }
            } catch (\Throwable $e) {
                $errorMsg = 'Erro API: ' . $e->getMessage();
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => $errorMsg]);
                    return;
                }
                $_SESSION['flash_error'] = $errorMsg;
                header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
                exit;
            }
        } else {
            // API Evolution v2.3 endpoint correto para criar instância
            $res = $this->evolutionApiRequest($company, '/instance/create', 'POST', [
                'instanceName' => $instanceName,
                'integration' => 'WHATSAPP-BAILEYS'  // Campo obrigatório!
            ]);

            if (isset($res['error'])) {
                $errorMsg = 'Erro API: ' . $res['error'];
                if ($this->isAjax()) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => $errorMsg]);
                    return;
                }
                $_SESSION['flash_error'] = $errorMsg;
            } else {
                $data = $res['data'] ?? [];
                $instance_identifier = $data['instance']['instanceName'] ?? $data['instanceName'] ?? $instanceName;
                $qr = null; // QR será gerado em etapa separada
                $status = $data['instance']['status'] ?? $data['status'] ?? 'close';
                
                // IMPORTANTE: Buscar o UUID real da instância na API
                $this->syncNewInstanceWithApi($company, $instanceName, $instance_identifier);
            }
        }

        // NÃO SALVAR LOCALMENTE - Trabalhar apenas com API remota
        
        // Se chegou aqui, a instância foi criada com sucesso na API
        if ($this->isAjax()) {
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => true, 
                'message' => 'Instância criada com sucesso na API Evolution',
                'instance' => [
                    'name' => $instanceName,
                    'instance_identifier' => $instance_identifier,
                    'status' => $status
                ]
            ]);
            return;
        }

        $_SESSION['flash_success'] = 'Instância criada com sucesso na API Evolution';
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function refresh_qr($params)
    {
        $isAjax = $this->isAjax() || 
                 (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                 (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        try {
            [$u,$company] = $this->guard($params['slug']);
        } catch (\Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Acesso negado: ' . $e->getMessage()]);
                return;
            }
            throw $e;
        }
        
        $json = $this->getJsonBody();
        // Aceitar tanto instanceName quanto instanceId (UUID da API)
        $instanceName = $json['instanceName'] ?? $_POST['instanceName'] ?? null;
        $instanceId = $json['instanceId'] ?? $json['id'] ?? $_POST['instanceId'] ?? $_POST['id'] ?? null;

        if (!$instanceName && !$instanceId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Nome ou ID da instância não informado']);
                return;
            }
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            return;
        }

        // Se temos apenas o UUID, buscar o nome na API remota
        if (!$instanceName && $instanceId) {
            $remoteRes = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
            if (!isset($remoteRes['error']) && isset($remoteRes['data'])) {
                foreach ($remoteRes['data'] as $remoteInst) {
                    if (($remoteInst['id'] ?? '') === $instanceId) {
                        $instanceName = $remoteInst['name'] ?? null;
                        break;
                    }
                }
            }
            
            if (!$instanceName) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Instância não encontrada na API Evolution']);
                    return;
                }
                header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
                return;
            }
        }

        // BUSCAR QR CODE DIRETAMENTE NA API EVOLUTION
        $qr = null;
        $apiError = null;
        
        $client = $this->makeEvolutionClient($company);
        if ($client) {
            try {
                $info = $client->fetchInstance($instanceName);
                $qr = $info['qr_code'] ?? $info['qr'] ?? null;
            } catch (\Throwable $e) {
                $apiError = $e->getMessage();
            }
        } else {
            // Usar endpoint direto da API para buscar QR code
            $res = $this->evolutionApiRequest($company, '/instance/fetchInstances?instanceName=' . rawurlencode($instanceName), 'GET');

            if (isset($res['error'])) {
                $apiError = $res['error'];
            } else {
                $data = $res['data'] ?? [];
                if (is_array($data)) {
                    // Se retornou array de instâncias, procurar a específica
                    foreach ($data as $inst) {
                        if (($inst['name'] ?? '') === $instanceName) {
                            $qr = $inst['qr_code'] ?? $inst['qr'] ?? null;
                            break;
                        }
                    }
                } else {
                    // Se retornou instância única
                    $qr = $data['qr_code'] ?? $data['qr'] ?? null;
                }
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            if ($apiError) {
                echo json_encode(['error' => 'Erro ao buscar QR Code: ' . $apiError]);
            } else {
                echo json_encode(['ok' => true, 'qr' => $qr]);
            }
            return;
        }

        if ($apiError) {
            $_SESSION['flash_error'] = 'Erro ao atualizar QR Code: ' . $apiError;
        } else {
            $_SESSION['flash_success'] = 'QR Code atualizado com sucesso';
        }

        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    public function delete($params)
    {
        // Forçar detecção AJAX se houver headers corretos
        $isAjax = $this->isAjax() || 
                 (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
                 (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        
        try {
            [$u,$company] = $this->guard($params['slug']);
        } catch (\Exception $e) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Acesso negado: ' . $e->getMessage()]);
                return;
            }
            throw $e;
        }
        
        $json = $this->getJsonBody();
        
        // Aceitar tanto instanceName quanto instanceId (UUID da API)
        $instanceName = $json['instanceName'] ?? $_POST['instanceName'] ?? null;
        $instanceId = $json['instanceId'] ?? $json['id'] ?? $_POST['instanceId'] ?? $_POST['id'] ?? null;
        
        if (!$instanceName && !$instanceId) {
            if ($isAjax) { 
                header('Content-Type: application/json'); 
                echo json_encode(['error' => 'Nome ou ID da instância não informado']); 
                return; 
            }
            header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
            return;
        }
        
        // Se temos apenas o UUID, buscar o nome na API remota
        if (!$instanceName && $instanceId) {
            $remoteRes = $this->evolutionApiRequest($company, '/instance/fetchInstances', 'GET', null);
            if (!isset($remoteRes['error']) && isset($remoteRes['data'])) {
                foreach ($remoteRes['data'] as $remoteInst) {
                    if (($remoteInst['id'] ?? '') === $instanceId) {
                        $instanceName = $remoteInst['name'] ?? null;
                        break;
                    }
                }
            }
            
            if (!$instanceName) {
                if ($isAjax) { 
                    header('Content-Type: application/json'); 
                    echo json_encode(['error' => 'Instância não encontrada na API Evolution']); 
                    return; 
                }
                header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
                return;
            }
        }
        
        // DELETAR DIRETAMENTE NA API EVOLUTION
        $apiError = null;
        $client = $this->makeEvolutionClient($company);
        if ($client) {
            try {
                $client->deleteInstance($instanceName);
            } catch (\Throwable $e) {
                $apiError = $e->getMessage();
            }
        } else {
            // Usar endpoint direto da API Evolution v2.3
            $result = $this->evolutionApiRequest($company, "/instance/delete/{$instanceName}", 'DELETE', null);
            
            if (isset($result['error'])) {
                $apiError = $result['error'];
            } else if (isset($result['data']) && isset($result['data']['error'])) {
                $apiError = $result['data']['error'];
            }
        }

        if ($isAjax) { 
            header('Content-Type: application/json'); 
            if ($apiError) {
                echo json_encode(['error' => 'Erro ao deletar instância: ' . $apiError]); 
            } else {
                echo json_encode(['ok' => true, 'message' => 'Instância deletada com sucesso da API Evolution']); 
            }
            return; 
        }

        // Para requisições não-AJAX, definir mensagem de feedback
        if ($apiError) {
            $_SESSION['flash_error'] = 'Erro ao deletar instância: ' . $apiError;
        } else {
            $_SESSION['flash_success'] = 'Instância deletada com sucesso';
        }
        
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/evolution'));
    }

    /**
     * Configura webhook para uma instância específica
     */
    public function configure_webhook($companySlug, $instanceName = null)
    {
        $company = Company::findBySlug($companySlug);
        if (!$company) {
            http_response_code(404);
            exit('Company not found');
        }

        if (!$instanceName) {
            http_response_code(400);
            exit('Instance name is required');
        }

        // URL do webhook
        $webhookUrl = base_url('webhook/evolution.php');
        
        // Configurações do webhook seguindo Evolution API v2.3 (formato correto)
        $webhookConfig = [
            'enabled' => true,
            'url' => $webhookUrl,
            'events' => [
                'APPLICATION_STARTUP',
                'QRCODE_UPDATED',
                'MESSAGES_SET',
                'MESSAGES_UPSERT',
                'MESSAGES_UPDATE',
                'MESSAGES_DELETE',
                'SEND_MESSAGE',
                'CONTACTS_SET',
                'CONTACTS_UPSERT',
                'CONTACTS_UPDATE',
                'PRESENCE_UPDATE',
                'CHATS_SET',
                'CHATS_UPSERT',
                'CHATS_UPDATE',
                'CHATS_DELETE',
                'GROUPS_UPSERT',
                'GROUP_UPDATE',
                'GROUP_PARTICIPANTS_UPDATE',
                'CONNECTION_UPDATE',
                'LABELS_EDIT',
                'LABELS_ASSOCIATION',
                'CALL'
            ],
            'webhook_by_events' => true,
            'webhook_base64' => false
        ];

        $result = $this->evolutionApiRequest($company, "/webhook/set/{$instanceName}", 'POST', $webhookConfig);
        
        header('Content-Type: application/json');
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Webhook configurado com sucesso',
                'data' => $result['data'] ?? null
            ]);
        }
    }

    /**
     * Remove webhook de uma instância
     */
    public function remove_webhook($companySlug, $instanceName = null)
    {
        $company = Company::findBySlug($companySlug);
        if (!$company) {
            http_response_code(404);
            exit('Company not found');
        }

        if (!$instanceName) {
            http_response_code(400);
            exit('Instance name is required');
        }

        $result = $this->evolutionApiRequest($company, "/webhook/delete/{$instanceName}", 'DELETE', null);
        
        header('Content-Type: application/json');
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Webhook removido com sucesso',
                'data' => $result['data'] ?? null
            ]);
        }
    }

    /**
     * Lista configurações de webhook de uma instância
     */
    public function webhook_status($companySlug, $instanceName = null)
    {
        $company = Company::findBySlug($companySlug);
        if (!$company) {
            http_response_code(404);
            exit('Company not found');
        }

        if (!$instanceName) {
            http_response_code(400);
            exit('Instance name is required');
        }

        $result = $this->evolutionApiRequest($company, "/webhook/find/{$instanceName}", 'GET', null);
        
        header('Content-Type: application/json');
        if (isset($result['error'])) {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => $result['data'] ?? null
            ]);
        }
    }
}
