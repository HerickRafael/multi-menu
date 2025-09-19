<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Ingredient.php';

class AdminIngredientController extends Controller
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
    if (!$company) { echo "Empresa inválida"; exit; }

    if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) {
      echo "Acesso negado"; exit;
    }

    return [$u, $company];
  }

  private function consumeFlash(string $key)
  {
    $value = $_SESSION[$key] ?? null;
    unset($_SESSION[$key]);
    return $value;
  }

  public function index($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $productId = isset($_GET['product_id']) && $_GET['product_id'] !== '' ? (int)$_GET['product_id'] : null;
    $q = isset($_GET['q']) ? trim($_GET['q']) : null;

    $items = Ingredient::listByCompany($companyId, $productId, $q !== '' ? $q : null);
    $products = Product::allForCompany($companyId);
    $error = $this->consumeFlash('flash_error');

    return $this->view('admin/ingredients/index', compact('company', 'items', 'products', 'error', 'productId', 'q'));
  }

  public function create($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $error = $this->consumeFlash('flash_error');
    $old = $this->consumeFlash('flash_old_ingredient');

    $ingredient = [
      'id' => null,
      'product_id' => $old['product_id'] ?? '',
      'name' => $old['name'] ?? '',
    ];

    $products = Product::allForCompany($companyId);

    return $this->view('admin/ingredients/form', compact('company', 'ingredient', 'products', 'error'));
  }

  public function store($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = trim($_POST['name'] ?? '');

    $product = $productId ? Product::findByCompanyAndId($companyId, $productId) : null;

    if (!$product) {
      $_SESSION['flash_error'] = 'Selecione um produto válido.';
      $_SESSION['flash_old_ingredient'] = ['product_id' => $productId, 'name' => $name];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
      exit;
    }

    if ($name === '') {
      $_SESSION['flash_error'] = 'Informe o nome do ingrediente.';
      $_SESSION['flash_old_ingredient'] = ['product_id' => $productId, 'name' => $name];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
      exit;
    }

    Ingredient::create([
      'product_id' => $productId,
      'name' => $name,
    ]);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
    exit;
  }

  public function edit($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $ingredient = Ingredient::findForCompany($companyId, (int)$params['id']);
    if (!$ingredient) { echo "Ingrediente não encontrado."; exit; }

    $error = $this->consumeFlash('flash_error');
    $old = $this->consumeFlash('flash_old_ingredient');

    if ($old) {
      $ingredient['product_id'] = $old['product_id'];
      $ingredient['name'] = $old['name'];
    }

    $products = Product::allForCompany($companyId);

    return $this->view('admin/ingredients/form', compact('company', 'ingredient', 'products', 'error'));
  }

  public function update($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];
    $ingredientId = (int)$params['id'];

    $ingredient = Ingredient::findForCompany($companyId, $ingredientId);
    if (!$ingredient) { echo "Ingrediente não encontrado."; exit; }

    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $name = trim($_POST['name'] ?? '');

    $product = $productId ? Product::findByCompanyAndId($companyId, $productId) : null;
    if (!$product) {
      $_SESSION['flash_error'] = 'Selecione um produto válido.';
      $_SESSION['flash_old_ingredient'] = ['product_id' => $productId, 'name' => $name];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
      exit;
    }

    if ($name === '') {
      $_SESSION['flash_error'] = 'Informe o nome do ingrediente.';
      $_SESSION['flash_old_ingredient'] = ['product_id' => $productId, 'name' => $name];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
      exit;
    }

    Ingredient::update($ingredientId, [
      'product_id' => $productId,
      'name' => $name,
    ]);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
    exit;
  }

  public function destroy($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];
    $ingredientId = (int)$params['id'];

    $ingredient = Ingredient::findForCompany($companyId, $ingredientId);
    if (!$ingredient) { echo "Ingrediente não encontrado."; exit; }

    Ingredient::delete($ingredientId);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
    exit;
  }
}
