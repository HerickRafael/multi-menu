<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\CategoryService;
use App\Application\Services\CompanyService;
use App\Application\Services\IngredientService;
use App\Application\Services\ProductCustomizationService;
use App\Application\Services\ProductService;
use App\Core\Auth;
use App\Core\Controller;

class AdminProductController extends Controller
{
    private CompanyService $companies;

    private CategoryService $categories;

    private ProductService $products;

    private IngredientService $ingredients;

    private ProductCustomizationService $customizations;

    public function __construct()
    {
        $this->companies = new CompanyService();
        $this->categories = new CategoryService();
        $this->products = new ProductService();
        $this->ingredients = new IngredientService();
        $this->customizations = new ProductCustomizationService();
    }



    /**
     * Normaliza o preço promocional garantindo que só valores válidos sejam usados.
     */
    private function sanitizePromoPrice($input, float $basePrice): ?float
    {
        if ($input === null) {
            return null;
        }

        if (is_array($input)) {
            $input = reset($input);
        }

        $raw = trim((string)$input);

        if ($raw === '') {
            return null;
        }

        $raw = str_replace(' ', '', $raw);

        if (strpos($raw, ',') !== false && strpos($raw, '.') !== false) {
            $raw = str_replace('.', '', $raw);
        }
        $raw = str_replace(',', '.', $raw);

        if (!is_numeric($raw)) {
            return null;
        }

        $promo = (float)$raw;

        if ($promo <= 0) {
            return null;
        }

        $price = (float)$basePrice;

        if ($price <= 0 || $promo >= $price) {
            return null;
        }

        return $promo;
    }

    /** Protege rotas e valida empresa/usuário */
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

        return [$u, $company];
    }

    /** Lista de produtos */
    public function index($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $cats  = $this->categories->allByCompany((int)$company['id']);
        $items = $this->products->listByCompany((int)$company['id'], $_GET['q'] ?? null, false);

        return $this->view('admin/products/index', compact('company', 'cats', 'items', 'error'));
    }

    /** Form de criação */
    public function create($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $cats = $this->categories->allByCompany((int)$company['id']);

        $p = [
          'id'          => null,
          'name'        => '',
          'description' => '',
          'price'       => 0.0,
          'promo_price' => null,
          'sku'         => $this->products->nextSkuForCompany((int)$company['id']),
          'sort_order'  => 0,
          'active'      => 1,
          'category_id' => null,
          'image'       => null,
        ];

        $customization = ['enabled' => false, 'groups' => []];
        $ingredients = $this->ingredients->allForCompany((int)$company['id']);
        $simpleProducts = $this->products->simpleProductsForCompany((int)$company['id']);
        $groups = [];

        return $this->view('admin/products/form', compact('company', 'cats', 'p', 'customization', 'ingredients', 'simpleProducts', 'groups'));
    }

    /**
     * Faz upload de imagem e retorna o caminho relativo (ex.: "uploads/arquivo.jpg").
     * Em caso de erro, preenche $error (e retorna null).
     */
    private function handleUpload(?array $file, ?string &$error = null): ?string
    {
        $error = null;

        if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

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

        $name = 'p_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
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
    public function store($params)
    {
        [$u, $company] = $this->guard($params['slug']);

        $imgError = null;
        $img = $this->handleUpload($_FILES['image'] ?? null, $imgError);

        if ($imgError) {
            $_SESSION['flash_error'] = $imgError;
        }

        $custPayload = $_POST['customization'] ?? [];
        $custData    = $this->customizations->sanitizePayload(is_array($custPayload) ? $custPayload : [], (int)$company['id']);

        $ptype = ($_POST['type'] ?? 'simple') === 'combo' ? 'combo' : 'simple';
        $priceMode = ($_POST['price_mode'] ?? 'fixed') === 'sum' ? 'sum' : 'fixed';

        $useGroups = $ptype === 'combo' && (!empty($_POST['use_groups']) || !empty($_POST['groups']));
        $groupsPayload = $useGroups && isset($_POST['groups']) && is_array($_POST['groups'])
          ? $this->products->sanitizeComboGroupsPayload($_POST['groups'], (int)$company['id'])
          : [];

        $price = (float)($_POST['price'] ?? 0);
        $promo = $this->sanitizePromoPrice($_POST['promo_price'] ?? null, $price);

        $data = [
          'company_id'  => (int)$company['id'],
          'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
          'name'        => trim($_POST['name'] ?? ''),
          'description' => trim($_POST['description'] ?? ''),
          'price'       => $price,
          'promo_price' => $promo,
          'sku'         => $this->products->nextSkuForCompany((int)$company['id']),
          'image'       => $img, // pode ser null
          'active'      => isset($_POST['active']) ? 1 : 0,
          'sort_order'  => (int)($_POST['sort_order'] ?? 0),
          'allow_customize' => $ptype === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
          'type'        => $ptype,
          'price_mode'  => $priceMode,
        ];

        $productId = $this->products->create($data);
        $this->customizations->save($productId, $custData);
        $this->products->saveComboGroupsAndItems($productId, $ptype === 'combo' ? $groupsPayload : []);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
        exit;
    }

    /** Form de edição */
    public function edit($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $cats = $this->categories->allByCompany((int)$company['id']);
        $p = $this->products->find((int)$params['id']);

        if (!$p) {
            echo 'Produto não encontrado.';
            exit;
        }

        $customGroups = $this->customizations->loadForAdmin((int)$p['id']);
        $customization = [
          'enabled' => !empty($p['allow_customize']) || !empty($customGroups),
          'groups'  => $customGroups,
        ];
        $ingredients = $this->ingredients->allForCompany((int)$company['id']);
        $simpleProducts = $this->products->simpleProductsForCompany((int)$company['id']);
        $groups = $this->products->getComboGroupsWithItems((int)$p['id']);

        return $this->view('admin/products/form', compact('company', 'cats', 'p', 'customization', 'ingredients', 'simpleProducts', 'groups'));
    } // <-- ESTA CHAVE FALTAVA

    /** Persistência da edição */
    public function update($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $p = $this->products->find((int)$params['id']);

        if (!$p) {
            echo 'Produto não encontrado.';
            exit;
        }

        $imgError = null;
        $uploaded = $this->handleUpload($_FILES['image'] ?? null, $imgError);
        $img = $uploaded ?: ($p['image'] ?? null);

        if ($imgError) {
            $_SESSION['flash_error'] = $imgError;
        }

        $custPayload = $_POST['customization'] ?? [];
        $custData    = $this->customizations->sanitizePayload(is_array($custPayload) ? $custPayload : [], (int)$company['id']);

        $ptype = ($_POST['type'] ?? 'simple') === 'combo' ? 'combo' : 'simple';
        $priceMode = ($_POST['price_mode'] ?? 'fixed') === 'sum' ? 'sum' : 'fixed';

        $useGroups = $ptype === 'combo' && (!empty($_POST['use_groups']) || !empty($_POST['groups']));
        $groupsPayload = $useGroups && isset($_POST['groups']) && is_array($_POST['groups'])
          ? $this->products->sanitizeComboGroupsPayload($_POST['groups'], (int)$company['id'])
          : [];

        $price = (float)($_POST['price'] ?? 0);
        $promo = $this->sanitizePromoPrice($_POST['promo_price'] ?? null, $price);

        $data = [
          'category_id' => $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null,
          'name'        => trim($_POST['name'] ?? ''),
          'description' => trim($_POST['description'] ?? ''),
          'price'       => $price,
          'promo_price' => $promo,
          'sku'         => isset($p['sku']) && $p['sku'] !== '' ? $p['sku'] : $this->products->nextSkuForCompany((int)$company['id']),
          'image'       => $img,
          'active'      => isset($_POST['active']) ? 1 : 0,
          'sort_order'  => (int)($_POST['sort_order'] ?? 0),
          'allow_customize' => $ptype === 'simple' && !empty($custData['enabled']) && !empty($custData['groups']) ? 1 : 0,
          'type'        => $ptype,
          'price_mode'  => $priceMode,
        ];

        $productId = (int)$params['id'];
        $this->products->update($productId, $data);
        $this->customizations->save($productId, $custData);
        $this->products->saveComboGroupsAndItems($productId, $ptype === 'combo' ? $groupsPayload : []);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
        exit;
    }

    /** Exclusão */
    public function destroy($params)
    {
        [$u, $company] = $this->guard($params['slug']);
        $this->products->delete((int)$params['id']);
        header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/products'));
        exit;
    }
}
