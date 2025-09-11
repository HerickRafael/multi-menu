<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

class AdminDashboardController extends Controller
{
  /**
   * Garante autenticação e contexto de empresa pelo slug.
   * Retorna [ $user, $company ].
   */
  private function guard(string $slug): array
  {
    Auth::start();

    // precisa estar logado (admin)
    if (!Auth::checkAdmin()) {
        header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
      exit;
    }

    // empresa pelo slug
    $company = Company::findBySlug($slug);
    if (!$company || empty($company['id'])) {
      http_response_code(404);
      echo "Empresa inválida";
      exit;
    }

    // autorização: root pode tudo; demais só a própria empresa
    $u = Auth::user();
    $isRoot = ($u['role'] === 'root');
    if (!$isRoot && (int)$u['company_id'] !== (int)$company['id']) {
      http_response_code(403);
      echo "Acesso negado";
      exit;
    }

    // garante que o contexto ativo siga o slug acessado
    $this->ensureCompanyContext((int)$company['id'], $slug);

    return [ $u, $company ];
  }

  /** GET /admin/{slug}/dashboard */
  public function index(array $params)
  {
    $slug = trim((string)($params['slug'] ?? ''));
    if ($slug === '') {
      http_response_code(400);
      echo "Slug inválido";
      return;
    }

    [ $u, $company ] = $this->guard($slug);

    $companyId = (int)$company['id'];
    $categories = Category::listByCompany($companyId);
    $products   = Product::listByCompany($companyId);

    // slug efetivo do contexto (usado para montar URLs no dashboard, ex.: botão Pedidos)
    $activeSlug = $this->currentCompanySlug() ?? $slug;

    return $this->view('admin/dashboard/index', compact(
      'company',
      'u',
      'categories',
      'products',
      'activeSlug'
    ));
  }
}
