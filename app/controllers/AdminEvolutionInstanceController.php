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

            // Buscar informações da instância na Evolution API usando o nome diretamente
            $instanceData = $this->getInstanceDataByName($instanceName);
            
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
    private function getInstanceDataByName($instanceName)
    {
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/fetchInstances?instanceName=" . rawurlencode($instanceName),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            // Se retornou um array com dados, pegar o primeiro item
            if (is_array($data) && !empty($data)) {
                return is_array($data[0]) ? $data[0] : $data;
            }
            return $data;
        }
        
        // Retornar dados padrão se não conseguir obter da API
        return [
            'instance_identifier' => $instanceName,
            'status' => 'disconnected',
            'token' => null
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
            
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/connectionState/" . rawurlencode($instanceName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            header('Content-Type: application/json');
            
            if ($curlError) {
                echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $curlError]);
                return;
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                echo json_encode(['success' => true, 'data' => $data]);
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['message'] ?? $response ?? 'Erro desconhecido';
                echo json_encode(['success' => false, 'error' => 'Falha ao obter estado: ' . $errorMsg]);
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
            
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/connect/" . rawurlencode($instanceName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            header('Content-Type: application/json');
            
            if ($curlError) {
                echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $curlError]);
                return;
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
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
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['message'] ?? $response ?? 'Erro desconhecido';
                echo json_encode(['success' => false, 'error' => 'Falha ao conectar: ' . $errorMsg]);
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
            
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/restart/" . rawurlencode($instanceName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            header('Content-Type: application/json');
            
            if ($curlError) {
                echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $curlError]);
                return;
            }
            
            if ($httpCode === 200 || $httpCode === 201) {
                $data = json_decode($response, true);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Instância reiniciada com sucesso',
                    'data' => $data
                ]);
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['message'] ?? $response ?? 'Erro desconhecido';
                echo json_encode(['success' => false, 'error' => 'Falha ao reiniciar: ' . $errorMsg]);
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
            
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/logout/" . rawurlencode($instanceName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => [
                    'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            header('Content-Type: application/json');
            
            if ($curlError) {
                echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $curlError]);
                return;
            }
            
            if ($httpCode === 200 || $httpCode === 204) {
                $data = json_decode($response, true);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Instância desconectada com sucesso',
                    'data' => $data
                ]);
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['message'] ?? $response ?? 'Erro desconhecido';
                echo json_encode(['success' => false, 'error' => 'Falha ao desconectar: ' . $errorMsg]);
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
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/connect/" . rawurlencode($instanceName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);

            header('Content-Type: application/json');

            if ($curlError) {
                echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $curlError]);
                return;
            }

            if ($httpCode !== 200) {
                echo json_encode(['success' => false, 'error' => 'API retornou HTTP ' . $httpCode . ': ' . $response]);
                return;
            }

            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo json_encode(['success' => false, 'error' => 'Resposta inválida da API: ' . json_last_error_msg()]);
                return;
            }

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
                error_log("QR Code Debug - Response: " . $response);
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
            
            // Buscar dados da instância (inclui contadores)
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://evolutionvictor.mlojas.com/instance/fetchInstances?instanceName=" . rawurlencode($instanceName),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'apikey: 0cdfec38b34fdae0d7624e8e28debd9f',
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 30
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            header('Content-Type: application/json');
            
            if ($curlError) {
                echo json_encode(['success' => false, 'error' => 'Erro de conexão: ' . $curlError]);
                return;
            }
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                
                if (is_array($data) && !empty($data)) {
                    $instance = is_array($data[0]) ? $data[0] : $data;
                    
                    // Extrair estatísticas
                    $stats = [
                        'status' => $instance['connectionStatus'] ?? 'disconnected',
                        'contacts' => $instance['_count']['Contact'] ?? 0,
                        'chats' => $instance['_count']['Chat'] ?? 0,
                        'messages' => $instance['_count']['Message'] ?? 0,
                        'profileName' => $instance['profileName'] ?? null,
                        'profilePicUrl' => $instance['profilePicUrl'] ?? null,
                        'number' => $instance['number'] ?? null
                    ];
                    
                    echo json_encode(['success' => true, 'data' => $stats]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Instância não encontrada']);
                }
            } else {
                $errorData = json_decode($response, true);
                $errorMsg = $errorData['message'] ?? $response ?? 'Erro desconhecido';
                echo json_encode(['success' => false, 'error' => 'Falha ao buscar estatísticas: ' . $errorMsg]);
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

}