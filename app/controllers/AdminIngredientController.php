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
      'name' => $old['name'] ?? '',
      'min_qty' => $old['min_qty'] ?? 0,
      'max_qty' => $old['max_qty'] ?? 1,
      'image_path' => null,
    ];

    return $this->view('admin/ingredients/form', compact('company', 'ingredient', 'error'));
  }

  public function store($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $name = trim($_POST['name'] ?? '');
    $min = isset($_POST['min_qty']) ? max(0, (int)$_POST['min_qty']) : 0;
    $max = isset($_POST['max_qty']) ? max(0, (int)$_POST['max_qty']) : 1;
    if ($max < $min) {
      $max = $min;
    }

    [$imagePath, $uploadError] = $this->handleUpload($_FILES['image'] ?? null);

    if ($name === '') {
      $_SESSION['flash_error'] = 'Informe o nome do ingrediente.';
      $_SESSION['flash_old_ingredient'] = ['name' => $name, 'min_qty' => $min, 'max_qty' => $max];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
      exit;
    }

    if (Ingredient::existsByName($companyId, $name)) {
      $_SESSION['flash_error'] = 'Já existe um ingrediente com este nome.';
      $_SESSION['flash_old_ingredient'] = ['name' => $name, 'min_qty' => $min, 'max_qty' => $max];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
      exit;
    }

    if ($uploadError) {
      $_SESSION['flash_error'] = $uploadError;
      $_SESSION['flash_old_ingredient'] = ['name' => $name, 'min_qty' => $min, 'max_qty' => $max];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/create'));
      exit;
    }

    Ingredient::create([
      'company_id' => $companyId,
      'name' => $name,
      'min_qty' => $min,
      'max_qty' => $max,
      'image_path' => $imagePath,
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
      $ingredient['name'] = $old['name'];
      $ingredient['min_qty'] = $old['min_qty'];
      $ingredient['max_qty'] = $old['max_qty'];
    }

    return $this->view('admin/ingredients/form', compact('company', 'ingredient', 'error'));
  }

  public function update($params)
  {
    [$u, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];
    $ingredientId = (int)$params['id'];

    $ingredient = Ingredient::findForCompany($companyId, $ingredientId);
    if (!$ingredient) { echo "Ingrediente não encontrado."; exit; }

    $name = trim($_POST['name'] ?? '');
    $min = isset($_POST['min_qty']) ? max(0, (int)$_POST['min_qty']) : 0;
    $max = isset($_POST['max_qty']) ? max(0, (int)$_POST['max_qty']) : 1;
    if ($max < $min) {
      $max = $min;
    }

    [$imagePath, $uploadError] = $this->handleUpload($_FILES['image'] ?? null, $ingredient['image_path'] ?? null);

    if ($name === '') {
      $_SESSION['flash_error'] = 'Informe o nome do ingrediente.';
      $_SESSION['flash_old_ingredient'] = ['name' => $name, 'min_qty' => $min, 'max_qty' => $max];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
      exit;
    }

    if (Ingredient::existsByName($companyId, $name, $ingredientId)) {
      $_SESSION['flash_error'] = 'Já existe um ingrediente com este nome.';
      $_SESSION['flash_old_ingredient'] = ['name' => $name, 'min_qty' => $min, 'max_qty' => $max];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
      exit;
    }

    if ($uploadError) {
      $_SESSION['flash_error'] = $uploadError;
      $_SESSION['flash_old_ingredient'] = ['name' => $name, 'min_qty' => $min, 'max_qty' => $max];
      header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients/' . $ingredientId . '/edit'));
      exit;
    }

    Ingredient::update($ingredientId, [
      'name' => $name,
      'min_qty' => $min,
      'max_qty' => $max,
      'image_path' => $imagePath,
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

    Ingredient::delete($companyId, $ingredientId);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/ingredients'));
    exit;
  }

  private function handleUpload(?array $file, ?string $current = null): array
  {
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      return [$current, null];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
      return [$current, 'Falha ao enviar a imagem (código ' . $file['error'] . ').'];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      return [$current, 'Formato inválido. Use JPG, PNG ou WEBP.'];
    }

    $name = 'ingredient_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $dest = __DIR__ . '/../../public/uploads/' . $name;
    $dir = dirname($dest);

    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
      return [$current, 'Não foi possível criar o diretório de uploads.'];
    }

    if (!is_writable($dir)) {
      return [$current, 'Diretório de uploads sem permissão de escrita.'];
    }

    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
      return [$current, 'Arquivo inválido.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      return [$current, 'Não foi possível salvar o arquivo enviado.'];
    }

    return ['uploads/' . $name, null];
  }
}
