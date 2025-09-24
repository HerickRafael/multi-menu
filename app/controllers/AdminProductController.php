<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Ingredient.php';
require_once __DIR__ . '/../models/ProductCustomization.php';

class AdminProductController extends Controller {

  /**
   * Normaliza o preço promocional garantindo que só valores válidos sejam usados.
   */
  private function sanitizePromoPrice($input, float $basePrice): ?float {
    if ($input === null) return null;

    if (is_array($input)) {
      $input = reset($input);
    }

    $raw = trim((string)$input);
    if ($raw === '') return null;

    $raw = str_replace(' ', '', $raw);
    if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
      $raw = str_replace('.', '', $raw);
    }
    $raw = str_replace(',', '.', $raw);
    if (!is_numeric($raw)) return null;

    $promo = (float)$raw;
    if ($promo <= 0) return null;

    $price = (float)$basePrice;
    if ($price <= 0 || $promo >= $price) return null;

    return $promo;
  }

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
    $items = Product::listByCompany((int)$company['id'], $_GET['q'] ?? null, false);

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
      'sku'         => Product::nextSkuForCompany((int)$company['id']),
      'sort_order'  => 0,
      'active'      => 1,
      'category_id' => null,
      'image'       => null,
      'type'        => 'simple',
      'price_mode'  => 'fixed',
    ];

    $customization  = ['enabled' => false, 'groups' => []];
    $ingredients    = Ingredient::allForCompany((int)$company['id']);
    $groups         = [];
    $simpleProducts = Product::listSimpleForCombo((int)$company['id']);

    return $this->view('admin/products/form', compact('company','cats','p','customization','ingredients','groups','simpleProducts'));
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

    // Customização
    $custPayload = $_POST['customization'] ?? [];
    $custData    = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], (int)$company['id']);

    // Tipo / modo de preço / grupos (combo)
    $type       = (isset($_POST['type']) && $_POST['type'] === 'combo') ? 'combo' : 'simple';
    $priceMode  = (isset($_POST['price_mode']) && $_POST['price_mode'] === 'sum') ? 'sum' : 'fixed';
    $groupPayload = (isset($_POST['groups']) && is_array($_POST['groups'])) ? $_POST['groups'] : [];
    $useGroups  = $type === 'combo' && (!empty($_POST['use_groups']) || !empty($groupPayload));
    $comboGroups = $useGroups ? Product::normalizeComboGroups($groupPayload, (int)$company['id']) : [];
    if ($type === 'combo' && !$comboGroups) {
      // se não há grupos válidos, volta para simples
      $type = 'simple';
      $priceMode = 'fixed';
    }

    $price = (float)($_POST['price'] ?? 0);
    $promo = $this->sanitizePromoPrice($_POST['promo_price'] ?? null, $price);

    $data = [
      'company_id'      => (int)$company['id'],
      'category_id'     => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
      'name'            => trim($_POST['name'] ?? ''),
      'description'     => trim($_POST['description'] ?? ''),
      'price'           => $price,
      'promo_price'     => $promo,
      'sku'             => Product::nextSkuForCompany((int)$company['id']),
      'image'           => $img, // pode ser null
      'active'          => isset($_POST['active']) ? 1 : 0,
      'sort_order'      => (int)($_POST['sort_order'] ?? 0),
      'type'            => $type,
      'price_mode'      => $priceMode,
      'allow_customize' => (!empty($custData['enabled']) && !empty($custData['groups'])) ? 1 : 0,
    ];

    $productId = Product::create($data);
    ProductCustomization::save($productId, $custData);
    Product::saveComboGroupsAndItems($productId, $type === 'combo' && $useGroups ? $comboGroups : []);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
    exit;
  }

  /** Form de edição */
  public function edit($params){
    [$u, $company] = $this->guard($params['slug']);
    $cats = Category::allByCompany((int)$company['id']);
    $p = Product::find((int)$params['id']);

    if (!$p) { echo "Produto não encontrado."; exit; }

    $customization = [
      'enabled' => !empty($p['allow_customize']),
      'groups'  => ProductCustomization::loadForAdmin((int)$p['id']),
    ];
    $ingredients    = Ingredient::allForCompany((int)$company['id']);
    $groups         = Product::loadComboGroupsForAdmin((int)$p['id']);
    $simpleProducts = Product::listSimpleForCombo((int)$company['id'], (int)$p['id']);

    return $this->view('admin/products/form', compact('company','cats','p','customization','ingredients','groups','simpleProducts'));
  } // fim edit

  /** Persistência da edição */
  public function update($params){
    [$u, $company] = $this->guard($params['slug']);
    $p = Product::find((int)$params['id']);
    if (!$p) { echo "Produto não encontrado."; exit; }

    $imgError = null;
    $uploaded = $this->handleUpload($_FILES['image'] ?? null, $imgError);
    $img = $uploaded ?: ($p['image'] ?? null);
    if ($imgError) $_SESSION['flash_error'] = $imgError;

    // Customização
    $custPayload = $_POST['customization'] ?? [];
    $custData    = ProductCustomization::sanitizePayload(is_array($custPayload) ? $custPayload : [], (int)$company['id']);

    // Tipo / modo de preço / grupos (combo)
    $type      = (isset($_POST['type']) && $_POST['type'] === 'combo') ? 'combo' : 'simple';
    $priceMode = (isset($_POST['price_mode']) && $_POST['price_mode'] === 'sum') ? 'sum' : 'fixed';

    $groupPayload = (isset($_POST['groups']) && is_array($_POST['groups'])) ? $_POST['groups'] : [];
    $useGroups    = $type === 'combo' && (!empty($_POST['use_groups']) || !empty($groupPayload));
    $comboGroups  = $useGroups ? Product::normalizeComboGroups($groupPayload, (int)$company['id']) : [];
    if ($type !== 'combo') {
      $comboGroups = [];
    } elseif ($useGroups && !$comboGroups) {
      // se o usuário marcou combo mas não há grupos válidos, força simples
      $type = 'simple';
      $priceMode = 'fixed';
      $comboGroups = [];
    }

    $price = (float)($_POST['price'] ?? 0);
    $promo = $this->sanitizePromoPrice($_POST['promo_price'] ?? null, $price);

    $data = [
      'category_id'     => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
      'name'            => trim($_POST['name'] ?? ''),
      'description'     => trim($_POST['description'] ?? ''),
      'price'           => $price,
      'promo_price'     => $promo,
      'sku'             => isset($p['sku']) && $p['sku'] !== '' ? $p['sku'] : Product::nextSkuForCompany((int)$company['id']),
      'image'           => $img,
      'active'          => isset($_POST['active']) ? 1 : 0,
      'sort_order'      => (int)($_POST['sort_order'] ?? 0),
      'type'            => $type,
      'price_mode'      => $priceMode,
      'allow_customize' => (!empty($custData['enabled']) && !empty($custData['groups'])) ? 1 : 0,
    ];

    $productId = (int)$params['id'];
    Product::update($productId, $data);
    ProductCustomization::save($productId, $custData);
    Product::saveComboGroupsAndItems($productId, $type === 'combo' && $useGroups ? $comboGroups : []);

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
