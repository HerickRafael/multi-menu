<?php

declare(strict_types=1);
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Category.php';

class AdminCategoryController extends Controller
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

    public function index($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $cats = Category::allByCompany((int)$company['id']);

        return $this->view('admin/categories/index', compact('company', 'u', 'cats'));
    }

    public function create($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $cat = ['name' => '','sort_order' => 0,'active' => 1];

        return $this->view('admin/categories/form', compact('company', 'cat'));
    }

    public function store($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        Category::create([
          'company_id' => $company['id'],
          'name' => trim($_POST['name']),
          'sort_order' => (int)$_POST['sort_order'],
          'active' => isset($_POST['active']) ? 1 : 0
        ]);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }

    public function edit($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $cat = Category::find((int)$params['id']);

        return $this->view('admin/categories/form', compact('company', 'cat'));
    }

    public function update($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        Category::update((int)$params['id'], [
          'name' => trim($_POST['name']),
          'sort_order' => (int)$_POST['sort_order'],
          'active' => isset($_POST['active']) ? 1 : 0
        ]);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }

    public function destroy($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        Category::delete((int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }
}
