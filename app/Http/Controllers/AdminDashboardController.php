<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\CategoryService;
use App\Application\Services\CompanyService;
use App\Application\Services\IngredientService;
use App\Application\Services\OrderService;
use App\Application\Services\ProductService;
use App\Core\Auth;
use App\Core\Controller;

class AdminDashboardController extends Controller
{
    private CompanyService $companies;

    private OrderService $orders;

    private ProductService $products;

    private CategoryService $categories;

    private IngredientService $ingredients;

    public function __construct()
    {
        $this->companies = new CompanyService();
        $this->orders = new OrderService();
        $this->products = new ProductService();
        $this->categories = new CategoryService();
        $this->ingredients = new IngredientService();
    }

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
        $company = $this->companies->findBySlug($slug);

        if (!$company || empty($company['id'])) {
            http_response_code(404);
            echo 'Empresa inválida';
            exit;
        }

        // autorização: root pode tudo; demais só a própria empresa
        $u = Auth::user();
        $isRoot = ($u['role'] === 'root');

        if (!$isRoot && (int)$u['company_id'] !== (int)$company['id']) {
            http_response_code(403);
            echo 'Acesso negado';
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
            echo 'Slug inválido';

            return;
        }

        [ $u, $company ] = $this->guard($slug);

        $companyId = (int)$company['id'];
        $categories = $this->categories->listActiveByCompany($companyId);
        $products   = $this->products->listByCompany($companyId);
        $ingredientsCount = $this->ingredients->countByCompany($companyId);
        $ordersCount = $this->orders->countByCompany($companyId);
        $recentIngredients = $this->ingredients->listRecentByCompany($companyId, 8);
        $recentOrders = $this->orders->listRecentByCompany($companyId, 8);

        foreach ($recentIngredients as &$ing) {
            $assigned = $this->ingredients->assignedProducts((int)$ing['id']);
            $ing['product_names'] = array_column($assigned, 'name');
        }
        unset($ing);

        // slug efetivo do contexto (usado para montar URLs no dashboard, ex.: botão Pedidos)
        $activeSlug = $this->currentCompanySlug() ?? $slug;

        return $this->view('admin/dashboard/index', compact(
            'company',
            'u',
            'categories',
            'products',
            'ingredientsCount',
            'ordersCount',
            'recentIngredients',
            'recentOrders',
            'activeSlug'
        ));
    }
}
