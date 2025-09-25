<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\CategoryService;
use App\Application\Services\CompanyService;
use App\Core\Auth;
use App\Core\Controller;

class AdminCategoryController extends Controller
{
    private CategoryService $categories;

    private CompanyService $companies;

    public function __construct()
    {
        $this->categories = new CategoryService();
        $this->companies = new CompanyService();
    }


    private function guard($slug)
    {
        Auth::start();
        $u = Auth::user();

        if (!$u) {
            header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
            exit;
        }
        $company = $this->companies->findBySlug($slug);

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
        $cats = $this->categories->allByCompany((int)$company['id']);

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
        $this->categories->create([
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
        $cat = $this->categories->find((int)$params['id']);

        return $this->view('admin/categories/form', compact('company', 'cat'));
    }

    public function update($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $this->categories->update((int)$params['id'], [
          'name' => trim($_POST['name']),
          'sort_order' => (int)$_POST['sort_order'],
          'active' => isset($_POST['active']) ? 1 : 0
        ]);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }

    public function destroy($params)
    {
        [$u,$company] = $this->guard($params['slug']);
        $this->categories->delete((int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/categories'));
    }
}
