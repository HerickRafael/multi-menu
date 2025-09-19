<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

class AdminProductController extends Controller {

  /** Protege rotas e valida empresa/usuário */
  private function guard($slug) {
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

  /** Lista de produtos */
  public function index($params){
    [$u, $company] = $this->guard($params['slug']);
    $error = $_SESSION['flash_error'] ?? null;
    unset($_SESSION['flash_error']);

    $cats  = Category::allByCompany((int)$company['id']);
    $items = Product::listByCompany((int)$company['id'], $_GET['q'] ?? null);

    return $this->view('admin/products/index', compact('company','cats','items','error'));
  }

  /** Form de criação */
  public function create($params){
    [$u, $company] = $this->guard($params['slug']);
    $cats = Category::allByCompany((int)$company['id']);

    $p = [
      'id'          => null,
      'name'        => '',
      'description' => '',
      'price'       => 0.0,
      'promo_price' => null,
      'sku'         => '',
      'sort_order'  => 0,
      'active'      => 1,
      'category_id' => null,
      'image'       => null,
    ];

    return $this->view('admin/products/form', compact('company','cats','p'));
  }

  /**
   * Faz upload de imagem e retorna o caminho relativo (ex.: "uploads/arquivo.jpg").
   * Em caso de erro, preenche $error (e retorna null).
   */
  private function handleUpload(?array $file, ?string &$error = null): ?string {
    $error = null;
    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) return null;

    if ($file['error'] !== UPLOAD_ERR_OK) {
      $error = 'Erro no upload (código ' . $file['error'] . ')';
      error_log($error . ' para ' . ($file['tmp_name'] ?? 'temp'));
      return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
      $error = 'Formato de arquivo inválido. Use JPG, PNG ou WEBP.';
      return null;
    }

    $name = 'p_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $dest = __DIR__ . '/../../public/uploads/' . $name;
    $dir  = dirname($dest);

    if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
      $error = 'Falha ao criar diretório de upload';
      error_log($error . ': ' . $dir);
      return null;
    }

    if (!is_writable($dir)) {
      $error = 'Diretório de upload não gravável';
      error_log($error . ': ' . $dir);
      return null;
    }

    if (!is_uploaded_file($file['tmp_name'] ?? '')) {
      $error = 'Arquivo temporário inexistente';
      error_log($error . ': ' . ($file['tmp_name'] ?? ''));
      return null;
    }

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
      $error = 'Falha ao salvar o arquivo enviado.';
      $lastError = error_get_last();
      error_log("move_uploaded_file falhou: {$file['tmp_name']} -> {$dest} - " . ($lastError['message'] ?? 'sem detalhes'));
      return null;
    }

    return 'uploads/' . $name;
  }

  /** Persistência da criação */
  public function store($params){
    [$u, $company] = $this->guard($params['slug']);

    $imgError = null;
    $img = $this->handleUpload($_FILES['image'] ?? null, $imgError);
    if ($imgError) $_SESSION['flash_error'] = $imgError;

    $data = [
      'company_id'  => (int)$company['id'],
      'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
      'name'        => trim($_POST['name'] ?? ''),
      'description' => trim($_POST['description'] ?? ''),
      'price'       => (float)($_POST['price'] ?? 0),
      'promo_price' => ($_POST['promo_price'] === '' ? null : (float)$_POST['promo_price']),
      'sku'         => trim($_POST['sku'] ?? ''),
      'image'       => $img, // pode ser null
      'active'      => isset($_POST['active']) ? 1 : 0,
      'sort_order'  => (int)($_POST['sort_order'] ?? 0),
    ];

    Product::create($data);
    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
    exit;
  }

  /** Form de edição */
  public function edit($params){
    [$u, $company] = $this->guard($params['slug']);
    $cats = Category::allByCompany((int)$company['id']);
    $p = Product::find((int)$params['id']);

    if (!$p) { echo "Produto não encontrado."; exit; }

    return $this->view('admin/products/form', compact('company','cats','p'));
  } // <-- ESTA CHAVE FALTAVA

  /** Persistência da edição */
  public function update($params){
    [$u, $company] = $this->guard($params['slug']);
    $p = Product::find((int)$params['id']);
    if (!$p) { echo "Produto não encontrado."; exit; }

    $imgError = null;
    $uploaded = $this->handleUpload($_FILES['image'] ?? null, $imgError);
    $img = $uploaded ?: ($p['image'] ?? null);
    if ($imgError) $_SESSION['flash_error'] = $imgError;

    $data = [
      'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
      'name'        => trim($_POST['name'] ?? ''),
      'description' => trim($_POST['description'] ?? ''),
      'price'       => (float)($_POST['price'] ?? 0),
      'promo_price' => ($_POST['promo_price'] === '' ? null : (float)$_POST['promo_price']),
      'sku'         => trim($_POST['sku'] ?? ''),
      'image'       => $img,
      'active'      => isset($_POST['active']) ? 1 : 0,
      'sort_order'  => (int)($_POST['sort_order'] ?? 0),
    ];

    Product::update((int)$params['id'], $data);
    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
    exit;
  }

  /** Exclusão */
  public function destroy($params){
    [$u, $company] = $this->guard($params['slug']);
    Product::delete((int)$params['id']);
    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
    exit;
  }
}
