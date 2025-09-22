<?php
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/../models/Company.php';

class Auth {
  public static function start(): void {
    $cfg = config();
    date_default_timezone_set($cfg['timezone'] ?? 'America/Sao_Paulo');

    // Cookies de sessão mais seguros (quando possível)
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
      'lifetime' => $cookieParams['lifetime'],
      'path'     => $cookieParams['path'],
      'domain'   => $cookieParams['domain'],
      'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);

    session_name($cfg['session_name'] ?? 'mm_session');
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }
  }

  public static function login(array $user): void {
    // Opcional: regenerar id de sessão para evitar fixation
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
    $_SESSION['user'] = [
      'id'         => (int)($user['id'] ?? 0),
      'role'       => $user['role'] ?? '',
      'company_id' => isset($user['company_id']) ? (int)$user['company_id'] : null,
      'name'       => (string)($user['name'] ?? ''),
      'email'      => (string)($user['email'] ?? ''),
    ];
  }

  public static function user(): ?array {
    return $_SESSION['user'] ?? null;
  }

  public static function logout(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_destroy();
    }
    self::clearActiveCompany();
  }

  /** Admin logado? */
  public static function checkAdmin(): bool {
    $u = self::user();
    return $u && in_array(($u['role'] ?? ''), ['root','owner','staff'], true);
  }

  /**
   * Exige admin logado (se não, redireciona para login).
   * Prioriza login por empresa quando houver slug ativo; senão cai em /admin.
   */
  public static function requireAdmin(): void {
    if (self::checkAdmin()) {
      return;
    }

    // Se você quiser login contextual por empresa:
    $slug = self::activeCompanySlug();
    if (!$slug) {
      // tenta obter slug padrão do sistema (se existir)
      if (method_exists('Company', 'defaultSlug')) {
        $slug = Company::defaultSlug();
      }
    }

    if (is_string($slug) && $slug !== '') {
      header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'), true, 302);
      exit;
    }

    // fallback: login padrão
    header('Location: ' . base_url('admin'), true, 302);
    exit;
  }

  /** company_id padrão do usuário (pode ser null para root) */
  public static function companyId(): ?int {
    $u = self::user();
    return isset($u['company_id']) ? (int)$u['company_id'] : null;
  }

  /* ========= Contexto de Empresa Ativa (para root trocar de empresa) ========= */

  /** Define o contexto de empresa ativa (também útil para owner/staff) */
  public static function setActiveCompany(int $companyId, string $slug = null): void {
    $_SESSION['active_company_id'] = $companyId;
    if ($slug !== null) {
      $_SESSION['active_company_slug'] = $slug;
    }
  }

  /** company_id efetivo usado pelo painel admin (prioriza contexto ativo) */
  public static function activeCompanyId(): ?int {
    if (isset($_SESSION['active_company_id'])) {
      return (int)$_SESSION['active_company_id'];
    }
    return self::companyId(); // fallback: company do usuário
  }

  /** slug efetivo do contexto ativo (se disponível) */
  public static function activeCompanySlug(): ?string {
    if (!empty($_SESSION['active_company_slug'])) {
      return (string)$_SESSION['active_company_slug'];
    }
    return null;
  }

  /** Limpa o contexto ativo (ex.: no logout ou troca de empresa) */
  public static function clearActiveCompany(): void {
    unset($_SESSION['active_company_id'], $_SESSION['active_company_slug']);
  }
}
