<?php

namespace App\Middleware;

/**
 * CSRF Protection Middleware
 * 
 * Protege contra ataques Cross-Site Request Forgery (CSRF) gerando e validando tokens únicos.
 * Implementação enterprise-level com tokens de uso único e expiração configurável.
 * 
 * Uso:
 * 
 * // Gerar token no formulário
 * <input type="hidden" name="csrf_token" value="<?= CsrfProtection::generateToken() ?>">
 * 
 * // Validar token no processamento
 * if (!CsrfProtection::validateToken($_POST['csrf_token'] ?? null)) {
 *     die('Token CSRF inválido');
 * }
 * 
 * @link https://owasp.org/www-community/attacks/csrf
 */
class CsrfProtection
{
    /** @var int Tamanho do token em bytes (32 bytes = 64 caracteres hex) */
    private const TOKEN_LENGTH = 32;
    
    /** @var int Tempo de vida do token em segundos (1 hora) */
    private const TOKEN_LIFETIME = 3600;
    
    /** @var int Número máximo de tokens armazenados por sessão */
    private const MAX_TOKENS = 10;
    
    /** @var string Nome do campo do token */
    private const TOKEN_FIELD = 'csrf_token';
    
    /** @var string Nome da meta tag para SPA/AJAX */
    private const META_TAG_NAME = 'csrf-token';
    
    /**
     * Inicia o sistema de CSRF protection
     * Deve ser chamado no início da aplicação
     */
    public static function init(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        // Limpar tokens expirados
        self::cleanExpiredTokens();
    }
    
    /**
     * Gera um novo token CSRF
     * 
     * @param bool $singleUse Se true, token será invalidado após uso (padrão: true)
     * @return string Token gerado
     */
    public static function generateToken(bool $singleUse = true): string
    {
        self::init();
        
        // Gerar token criptograficamente seguro
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Armazenar token com metadados
        $_SESSION['csrf_tokens'][$token] = [
            'created_at' => time(),
            'single_use' => $singleUse,
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        // Limitar número de tokens (prevenir memory exhaustion)
        self::limitTokens();
        
        \Logger::debug("CSRF token generated", [
            'token' => substr($token, 0, 8) . '...',
            'single_use' => $singleUse
        ]);
        
        return $token;
    }
    
    /**
     * Valida um token CSRF
     * 
     * @param string|null $token Token a ser validado
     * @param bool $checkIp Validar IP (padrão: true, desabilitar se usar load balancer)
     * @param bool $checkUserAgent Validar User Agent (padrão: true)
     * @return bool True se válido, False caso contrário
     */
    public static function validateToken(
        ?string $token, 
        bool $checkIp = true, 
        bool $checkUserAgent = true
    ): bool {
        self::init();
        
        // Token vazio
        if (empty($token)) {
            \Logger::warning("CSRF validation failed: empty token");
            return false;
        }
        
        // Token não existe
        if (!isset($_SESSION['csrf_tokens'][$token])) {
            \Logger::warning("CSRF validation failed: token not found", [
                'token' => substr($token, 0, 8) . '...'
            ]);
            return false;
        }
        
        $tokenData = $_SESSION['csrf_tokens'][$token];
        
        // Verificar expiração
        if (time() - $tokenData['created_at'] > self::TOKEN_LIFETIME) {
            unset($_SESSION['csrf_tokens'][$token]);
            \Logger::warning("CSRF validation failed: token expired", [
                'token' => substr($token, 0, 8) . '...',
                'age' => time() - $tokenData['created_at']
            ]);
            return false;
        }
        
        // Verificar IP (se habilitado)
        if ($checkIp && $tokenData['ip'] !== self::getClientIp()) {
            \Logger::warning("CSRF validation failed: IP mismatch", [
                'token' => substr($token, 0, 8) . '...',
                'expected_ip' => $tokenData['ip'],
                'actual_ip' => self::getClientIp()
            ]);
            return false;
        }
        
        // Verificar User Agent (se habilitado)
        if ($checkUserAgent) {
            $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            if ($tokenData['user_agent'] !== $currentUA) {
                \Logger::warning("CSRF validation failed: User Agent mismatch", [
                    'token' => substr($token, 0, 8) . '...'
                ]);
                return false;
            }
        }
        
        // Token de uso único - remover após validação
        if ($tokenData['single_use']) {
            unset($_SESSION['csrf_tokens'][$token]);
        }
        
        \Logger::debug("CSRF token validated successfully", [
            'token' => substr($token, 0, 8) . '...'
        ]);
        
        return true;
    }
    
    /**
     * Valida token do request atual (POST, PUT, DELETE)
     * 
     * @param bool $dieOnFailure Se true, mata execução em caso de falha (padrão: true)
     * @return bool True se válido, False ou exit se inválido
     */
    public static function validate(bool $dieOnFailure = true): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Apenas validar em métodos que modificam dados
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return true;
        }
        
        // Buscar token (POST ou header)
        $token = $_POST[self::TOKEN_FIELD] ?? 
                 $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
                 null;
        
        $isValid = self::validateToken($token);
        
        if (!$isValid && $dieOnFailure) {
            http_response_code(403);
            
            \Logger::warning("CSRF validation failed - request blocked", [
                'method' => $method,
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'ip' => self::getClientIp()
            ]);
            
            // Resposta baseada no Accept header
            if (self::expectsJson()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'CSRF token validation failed',
                    'code' => 'CSRF_TOKEN_INVALID'
                ]);
            } else {
                echo '<h1>403 Forbidden</h1>';
                echo '<p>Token de segurança inválido ou expirado.</p>';
                echo '<p><a href="javascript:history.back()">Voltar</a></p>';
            }
            
            exit;
        }
        
        return $isValid;
    }
    
    /**
     * Gera meta tag para uso em SPAs/AJAX
     * 
     * @return string HTML da meta tag
     */
    public static function metaTag(): string
    {
        $token = self::generateToken(false); // Reusável para AJAX
        return sprintf(
            '<meta name="%s" content="%s">',
            htmlspecialchars(self::META_TAG_NAME),
            htmlspecialchars($token)
        );
    }
    
    /**
     * Gera campo hidden para formulários
     * 
     * @return string HTML do input hidden
     */
    public static function field(): string
    {
        $token = self::generateToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::TOKEN_FIELD),
            htmlspecialchars($token)
        );
    }
    
    /**
     * Obtém o token atual (para JavaScript)
     * 
     * @param bool $singleUse Token de uso único (padrão: false para AJAX)
     * @return string Token
     */
    public static function getToken(bool $singleUse = false): string
    {
        return self::generateToken($singleUse);
    }
    
    /**
     * Limpa todos os tokens da sessão
     */
    public static function clearTokens(): void
    {
        if (isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
    }
    
    /**
     * Obtém estatísticas dos tokens
     * 
     * @return array Estatísticas
     */
    public static function getStats(): array
    {
        self::init();
        
        $tokens = $_SESSION['csrf_tokens'] ?? [];
        $now = time();
        
        $valid = 0;
        $expired = 0;
        
        foreach ($tokens as $tokenData) {
            if ($now - $tokenData['created_at'] > self::TOKEN_LIFETIME) {
                $expired++;
            } else {
                $valid++;
            }
        }
        
        return [
            'total' => count($tokens),
            'valid' => $valid,
            'expired' => $expired,
            'max_allowed' => self::MAX_TOKENS,
            'lifetime' => self::TOKEN_LIFETIME
        ];
    }
    
    /**
     * Limpa tokens expirados
     */
    private static function cleanExpiredTokens(): void
    {
        if (!isset($_SESSION['csrf_tokens'])) {
            return;
        }
        
        $now = time();
        $cleaned = 0;
        
        foreach ($_SESSION['csrf_tokens'] as $token => $data) {
            if ($now - $data['created_at'] > self::TOKEN_LIFETIME) {
                unset($_SESSION['csrf_tokens'][$token]);
                $cleaned++;
            }
        }
        
        if ($cleaned > 0) {
            \Logger::debug("CSRF tokens cleaned", ['count' => $cleaned]);
        }
    }
    
    /**
     * Limita número de tokens armazenados
     */
    private static function limitTokens(): void
    {
        if (!isset($_SESSION['csrf_tokens'])) {
            return;
        }
        
        $tokens = $_SESSION['csrf_tokens'];
        
        if (count($tokens) > self::MAX_TOKENS) {
            // Remover tokens mais antigos
            uasort($tokens, function($a, $b) {
                return $a['created_at'] <=> $b['created_at'];
            });
            
            $_SESSION['csrf_tokens'] = array_slice(
                $tokens, 
                -self::MAX_TOKENS, 
                null, 
                true
            );
            
            \Logger::debug("CSRF tokens limited", [
                'removed' => count($tokens) - self::MAX_TOKENS
            ]);
        }
    }
    
    /**
     * Obtém IP do cliente (considerando proxies)
     */
    private static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_REAL_IP',            // Nginx proxy
            'HTTP_X_FORWARDED_FOR',      // Proxy padrão
            'REMOTE_ADDR'                // Direto
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Se for lista de IPs (proxy chain), pegar o primeiro
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validar IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Verifica se o cliente espera resposta JSON
     */
    private static function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        return strpos($accept, 'application/json') !== false ||
               strpos($contentType, 'application/json') !== false ||
               isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }
}
