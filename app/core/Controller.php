<?php
class Controller {
  protected function view(string $path, array $data = []) {
    // "public/home" → app/views/public/home.php
    $file = __DIR__ . '/../views/' . $path . '.php';
    if (!file_exists($file)) {
      echo "View não encontrada: $path";
      return;
    }
    extract($data);
    include $file;
  }

  /** Retorna conexão PDO usando a função db() definida em app/config/db.php */
  protected function db(): PDO {
    if (!function_exists('db')) {
      throw new RuntimeException('Função db() não encontrada. Verifique app/config/db.php e o bootstrap em public/index.php.');
    }
    $pdo = db(); // a função db() deve retornar um PDO (com cache estático)
    if (!$pdo instanceof PDO) {
      throw new RuntimeException('db() não retornou uma instância de PDO.');
    }
    return $pdo;
  }

  /** Protege rotas admin (inicia a sessão antes de verificar) */
  protected function requireAdmin(): void {
    Auth::start();       // garante que a sessão foi iniciada
    Auth::requireAdmin();
  }

  /**
   * ID da empresa corrente no contexto do admin.
   * - Para root: empresa escolhida via Auth::setActiveCompany()
   * - Para owner/staff: a própria company_id do usuário
   */
  protected function currentCompanyId(): ?int {
    return Auth::activeCompanyId();
  }

  /** Slug corrente do contexto (se definido via Auth::setActiveCompany) */
  protected function currentCompanySlug(): ?string {
    return Auth::activeCompanySlug();
  }

  /**
   * Garante que o contexto ativo bate com o slug da rota.
   * Útil em rotas /admin/{slug}/...
   */
  protected function ensureCompanyContext(int $companyId, string $slug): void {
    $activeId   = Auth::activeCompanyId();
    $activeSlug = Auth::activeCompanySlug();

    if ($activeId !== $companyId || $activeSlug !== $slug) {
      Auth::setActiveCompany($companyId, $slug);
    }
  }
}
