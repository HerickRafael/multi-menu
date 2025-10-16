<?php

declare(strict_types=1);
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Company.php';

class AdminEvolutionInstanceController extends Controller
{
    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
                 || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
                 || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!$u) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Sessão expirada. Faça login novamente.']);
                exit;
            } else {
                header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
                exit;
            }
        }
        
        $company = Company::findBySlug($slug);

        if (!$company) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Empresa inválida ou inativa']);
                exit;
            } else {
                echo 'Empresa inválida';
                exit;
            }
        }

        if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                exit;
            } else {
                echo 'Acesso negado';
                exit;
            }
        }

        return [$u, $company];
    }

    /**
     * Fazer requisição para Evolution API
     */
    private function evolutionApiRequest(array $company, string $path, string $method = 'GET', ?array $body = null): array
    {
        $server = rtrim($company['evolution_server_url'] ?? '', '/');
        $apiKey = $company['evolution_api_key'] ?? null;

        if (!$server || !$apiKey) {
            return ['error' => 'Configuração Evolution ausente (SERVER_URL e AUTHENTICATION_API_KEY).'];
        }

        $fullUrl = $server . '/' . ltrim($path, '/');
        
        // Log da requisição para debug
        error_log("Evolution API Request: $method $fullUrl");
        if ($body) {
            error_log("Evolution API Body: " . json_encode($body));
        }

        // internal helper to do a single request
        $doRequest = function(string $url) use ($method, $body, $apiKey) {
            $ch = curl_init($url);
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
                'apikey: ' . $apiKey
            ];

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => $method
            ]);

            if ($body && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            // Log da resposta para debug
            error_log("Evolution API Response: HTTP $httpCode");
            error_log("Evolution API Response Body: " . substr($response, 0, 500));
            
            if ($curlError) {
                error_log("Evolution API cURL Error: $curlError");
                return ['error' => 'Falha na conexão cURL: ' . $curlError];
            }

            if ($response === false) {
                return ['error' => 'Falha na conexão cURL'];
            }

            $decoded = json_decode($response, true);
            return [
                'code' => $httpCode,
                'data' => $decoded,
                'error' => $httpCode >= 400 ? ($decoded['message'] ?? 'HTTP Error ' . $httpCode) : null
            ];
        };

        return $doRequest($fullUrl);
    }

    /**
     * Página de configuração de uma instância específica
     */
    public function __construct()
    {
        // Constructor simplificado - a autenticação é feita pelo guard()
    }

    /**
     * Página de configuração da instância
     */
    public function config($params)
    {        
        try {
            // Extrair parâmetros do array
            $slug = $params['slug'] ?? null;
            $instanceName = $params['instanceName'] ?? null;
            
            if (!$slug || !$instanceName) {
                http_response_code(400);
                echo "Parâmetros inválidos";
                return;
            }
            
            // Usar o método guard para autenticação (igual aos outros controllers admin)
            [$user, $company] = $this->guard($slug);

            // Buscar informações da instância na Evolution API usando as configurações da empresa
            $instanceData = $this->getInstanceDataByName($instanceName, $company);
            
            // Renderizar a view
            $data = [
                'company' => $company,
                'instanceName' => $instanceName,
                'instanceData' => $instanceData,
                'slug' => $slug
            ];
            
            $this->render('admin/evolution/instance_config', $data);
            
        } catch (Exception $e) {
            error_log("Erro no AdminEvolutionInstanceController::config(): " . $e->getMessage());
            http_response_code(500);
            echo "Erro interno do servidor";
        }
    }

    /**
     * Buscar dados específicos da instância pelo nome
     */
    private function getInstanceDataByName($instanceName, $company = null)
    {
        // Se a empresa não foi passada, tentar obter do contexto
        if (!$company) {
            $slug = $_GET['slug'] ?? $_POST['slug'] ?? 'wollburger'; // fallback temporário
            $company = \Company::findBySlug($slug);
        }
        
        if (!$company) {
            error_log("Empresa não encontrada");
            return [
                'instance_identifier' => $instanceName,
                'status' => 'disconnected',
                'token' => null
            ];
        }
        
        // Usar o método evolutionApiRequest consistente em vez de URL hardcoded
        $result = $this->evolutionApiRequest($company, '/instance/fetchInstances?instanceName=' . rawurlencode($instanceName), 'GET');
        
        if (!$result['error'] && !empty($result['data'])) {
            $data = $result['data'];
            // Se retornou um array com dados, pegar o primeiro item
            if (is_array($data) && !empty($data)) {
                // Verificar se é um array de arrays (múltiplas instâncias) ou um objeto único
                if (array_key_exists(0, $data) && is_array($data[0])) {
                    return $data[0]; // Primeiro item do array
                }
            }
            return $data; // Retornar os dados como estão
        }
        
        error_log("Erro ao buscar dados da instância $instanceName: " . ($result['error'] ?? 'dados vazios'));
        
        // Retornar dados padrão se não conseguir obter da API
        return [
            'instance_identifier' => $instanceName,
            'status' => 'disconnected',
            'token' => null
        ];
    }

    /**
     * Helper para chamadas consistentes da API Evolution
     */
    private function callEvolutionAPI($url, $method = 'GET', $data = null, $timeout = 15)
    {
        $curl = curl_init();
        
        $headers = [
            'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
            'Content-Type: application/json'
        ];
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => $timeout
        ];
        
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            if ($data) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        }
        
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);
        
        if ($curlError) {
            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $curlError,
                'httpCode' => $httpCode,
                'data' => null,
                'rawResponse' => $response
            ];
        }
        
        if ($httpCode !== 200 && $httpCode !== 201 && $httpCode !== 204) {
            $errorData = json_decode($response, true);
            $errorMsg = is_array($errorData) && isset($errorData['message']) 
                ? $errorData['message'] 
                : ($response ?: 'Erro HTTP ' . $httpCode);
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'httpCode' => $httpCode,
                'data' => $errorData,
                'rawResponse' => $response
            ];
        }
        
        $decodedData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Resposta inválida da API: ' . json_last_error_msg(),
                'httpCode' => $httpCode,
                'data' => null,
                'rawResponse' => $response
            ];
        }
        
        return [
            'success' => true,
            'httpCode' => $httpCode,
            'error' => null,
            'data' => $decodedData,
            'rawResponse' => $response
        ];
    }
    
    private function render($view, $data = [])
    {
        extract($data);
        
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        
        if (file_exists($viewPath)) {
            include $viewPath;
        } else {
            http_response_code(404);
            echo "View não encontrada: {$view}";
        }
    }

    /**
     * API - Obter estado da conexão da instância
     */
    public function connection_state($params)
    {
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Nome da instância é obrigatório']);
            return;
        }
        
        try {
            $slug = $params['slug'] ?? null;
            if (!$slug) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Slug da empresa é obrigatório']);
                return;
            }
            
            [$user, $company] = $this->guard($slug);
            
            $url = "https://evolutionvictor.mlojas.com/instance/connectionState/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'GET', null, 30);
            
            header('Content-Type: application/json');
            
            if ($result['success']) {
                echo json_encode(['success' => true, 'data' => $result['data']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao obter estado: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function connect($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $url = "https://evolutionvictor.mlojas.com/instance/connect/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'GET', null, 30);
            
            header('Content-Type: application/json');
            
            if ($result['success']) {
                $data = $result['data'];
                
                // Se a resposta contém QR code, significa que precisa escanear
                if (isset($data['base64']) || isset($data['qrcode'])) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'QR Code gerado. Escaneie para conectar.',
                        'needsQr' => true,
                        'data' => $data
                    ]);
                } else {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Instância conectada com sucesso!',
                        'data' => $data
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao conectar: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function restart($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $url = "https://evolutionvictor.mlojas.com/instance/restart/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'POST', null, 30);
            
            header('Content-Type: application/json');
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Instância reiniciada com sucesso',
                    'data' => $result['data']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao reiniciar: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function disconnect($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            $url = "https://evolutionvictor.mlojas.com/instance/logout/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'DELETE', null, 30);
            
            header('Content-Type: application/json');
            
            if ($result['success'] || $result['httpCode'] === 204) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Instância desconectada com sucesso',
                    'data' => $result['data']
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Falha ao desconectar: ' . $result['error']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    public function qr_code($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            // Usar o método guard para autenticação
            [$user, $company] = $this->guard($slug);
            
            // Fazer a chamada direta para o endpoint de conexão que retorna o QR code
            $url = "https://evolutionvictor.mlojas.com/instance/connect/" . rawurlencode($instanceName);
            $result = $this->callEvolutionAPI($url, 'GET', null, 30);

            header('Content-Type: application/json');

            if (!$result['success']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
                return;
            }

            $data = $result['data'];

            // Buscar o QR code na resposta
            $qr = $data['base64'] ?? $data['qrcode']['base64'] ?? $data['qr'] ?? null;

            if ($qr) {
                // Garantir que o QR tem o prefixo data:image correto
                if (!str_starts_with($qr, 'data:image/')) {
                    $qr = 'data:image/png;base64,' . $qr;
                }
                echo json_encode(['success' => true, 'qr' => $qr]);
            } else {
                // Log da resposta para debug
                error_log("QR Code Debug - Response: " . json_encode($data));
                echo json_encode(['success' => false, 'error' => 'QR Code não encontrado na resposta da API']);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Buscar estatísticas da instância (contatos, chats, mensagens)
     */
    public function stats($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Usar o método getInstanceDataByName com a empresa correta
            $instanceData = $this->getInstanceDataByName($instanceName, $company);
            
            header('Content-Type: application/json');
            
            if ($instanceData) {
                // Extrair estatísticas dos dados já obtidos
                $stats = [
                    'status' => $instanceData['connectionStatus'] ?? 'disconnected',
                    'contacts' => $instanceData['_count']['Contact'] ?? 0,
                    'chats' => $instanceData['_count']['Chat'] ?? 0,
                    'messages' => $instanceData['_count']['Message'] ?? 0,
                    'profileName' => $instanceData['profileName'] ?? null,
                    'profilePicUrl' => $instanceData['profilePicUrl'] ?? null,
                    'number' => $instanceData['number'] ?? null
                ];
                
                echo json_encode(['success' => true, 'data' => $stats]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Instância não encontrada']);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Salvar configurações da instância
     */
    public function save_settings($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Obter dados POST
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Dados de configuração não fornecidos']);
                return;
            }
            
            // Primeiro, buscar configurações atuais
            $currentResult = $this->evolutionApiRequest(
                $company, 
                "/settings/find/{$instanceName}", 
                'GET'
            );
            
            if ($currentResult['error']) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Erro ao buscar configurações atuais: ' . $currentResult['error']]);
                return;
            }
            
            // Configurações padrão da Evolution API v2
            $defaultSettings = [
                'rejectCall' => false,
                'msgCall' => '',
                'groupsIgnore' => false,
                'alwaysOnline' => false,
                'readMessages' => false,
                'readStatus' => false,
                'syncFullHistory' => false
            ];
            
            // Mesclar configurações: atuais + padrão + novas
            $currentSettings = $currentResult['data'] ?? [];
            $finalSettings = array_merge($defaultSettings, $currentSettings, $input);
            
            // Fazer POST com todas as configurações
            $result = $this->evolutionApiRequest(
                $company, 
                "/settings/set/{$instanceName}", 
                'POST', 
                $finalSettings
            );
            
            header('Content-Type: application/json');
            
            if ($result['error']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                echo json_encode(['success' => true, 'data' => $result['data']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Buscar grupos da instância
     */
    public function groups($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Log das configurações da empresa
            error_log("Company config - Server: " . ($company['evolution_server_url'] ?? 'NOT SET'));
            error_log("Company config - API Key: " . (isset($company['evolution_api_key']) ? 'SET' : 'NOT SET'));
            
            // Buscar grupos usando o endpoint da Evolution API v2 com parâmetro obrigatório
            $path = "/group/fetchAllGroups/{$instanceName}?getParticipants=false";
            error_log("Buscando grupos para instância: {$instanceName} no path: {$path}");
            
            $result = $this->evolutionApiRequest(
                $company, 
                $path, 
                'GET'
            );
            
            error_log("Resultado da busca de grupos: " . json_encode($result));
            
            header('Content-Type: application/json');
            
            if ($result['error']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                // Normalizar dados dos grupos para o formato esperado
                $groups = $result['data'] ?? [];
                if (is_array($groups)) {
                    $formattedGroups = array_map(function($group) {
                        return [
                            'id' => $group['id'] ?? '',
                            'subject' => $group['subject'] ?? 'Grupo sem nome',
                            'description' => $group['description'] ?? '',
                            'participants' => count($group['participants'] ?? []),
                            'creation' => $group['creation'] ?? null,
                            'owner' => $group['owner'] ?? null
                        ];
                    }, $groups);
                    
                    echo json_encode(['success' => true, 'data' => $formattedGroups]);
                } else {
                    echo json_encode(['success' => true, 'data' => []]);
                }
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Buscar configurações da instância
     */
    public function get_settings($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            // Usar o método evolutionApiRequest para fazer a chamada
            $result = $this->evolutionApiRequest(
                $company, 
                "/settings/find/{$instanceName}", 
                'GET'
            );
            
            header('Content-Type: application/json');
            
            if ($result['error']) {
                echo json_encode(['success' => false, 'error' => $result['error']]);
            } else {
                echo json_encode(['success' => true, 'data' => $result['data']]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * API - Configurar notificação de pedido
     */
    public function order_notification($params)
    {
        $slug = $params['slug'] ?? null;
        $instanceName = $params['instanceName'] ?? null;
        
        if (!$slug || !$instanceName) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Parâmetros obrigatórios ausentes']);
            return;
        }
        
        try {
            [$user, $company] = $this->guard($slug);
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Salvar configuração de notificação
                $input = json_decode(file_get_contents('php://input'), true);
                
                $enabled = $input['enabled'] ?? false;
                $primaryNumber = $input['primary_number'] ?? '';
                $secondaryNumber = $input['secondary_number'] ?? '';
                
                // Validar dados
                if ($enabled && empty($primaryNumber)) {
                    echo json_encode(['success' => false, 'error' => 'Número principal é obrigatório quando a notificação está ativada']);
                    return;
                }
                
                // Validar formato dos números
                if ($enabled && $primaryNumber && !preg_match('/^[0-9]{10,15}$/', $primaryNumber)) {
                    echo json_encode(['success' => false, 'error' => 'Formato do número principal inválido. Use apenas números (10-15 dígitos)']);
                    return;
                }
                
                if ($enabled && $secondaryNumber && !preg_match('/^[0-9]{10,15}$/', $secondaryNumber)) {
                    echo json_encode(['success' => false, 'error' => 'Formato do número secundário inválido. Use apenas números (10-15 dígitos)']);
                    return;
                }
                
                // Salvar configuração na tabela de configurações da empresa
                $this->saveInstanceConfig($company['id'], $instanceName, 'order_notification', [
                    'enabled' => $enabled,
                    'primary_number' => $primaryNumber,
                    'secondary_number' => $secondaryNumber,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Configuração salva com sucesso']);
                
            } else {
                // Carregar configuração de notificação
                $config = $this->getInstanceConfig($company['id'], $instanceName, 'order_notification');
                
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'data' => $config]);
            }
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor: ' . $e->getMessage()]);
        }
    }

    /**
     * Salvar configuração da instância
     */
    private function saveInstanceConfig($companyId, $instanceName, $configKey, $configValue)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "INSERT INTO instance_configs (company_id, instance_name, config_key, config_value, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = NOW()";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $instanceName, $configKey, json_encode($configValue)]);
    }

    /**
     * Obter configuração da instância
     */
    private function getInstanceConfig($companyId, $instanceName, $configKey)
    {
        require_once __DIR__ . '/../config/db.php';
        $pdo = db();
        
        $sql = "SELECT config_value FROM instance_configs 
                WHERE company_id = ? AND instance_name = ? AND config_key = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$companyId, $instanceName, $configKey]);
        
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($row) {
            return json_decode($row['config_value'], true);
        }
        
        // Retornar configuração padrão
        return [
            'enabled' => false,
            'group_id' => '',
            'custom_message' => ''
        ];
    }

}