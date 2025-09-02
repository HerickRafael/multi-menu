<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Product.php';

class AdminProductController extends Controller {
  private function guard($slug) {
    Auth::start();
    $u = Auth::user();
    if (!$u) { header('Location: ' . base_url("admin/$slug/login")); exit; }
    $company = Company::findBySlug($slug);
    if (!$company) { echo "Empresa inválida"; exit; }
    if ($u['role'] !== 'root' && (int)$u['company_id'] !== (int)$company['id']) { echo "Acesso negado"; exit; }
    return [$u,$company];
  }

  public function index($params){
    [$u,$company] = $this->guard($params['slug']);
    $cats = Category::allByCompany((int)$company['id']);
    $items = Product::listByCompany((int)$company['id'], $_GET['q'] ?? null);
    return $this->view('admin/products/index', compact('company','cats','items'));
  }

  public function create($params){
    [$u,$company] = $this->guard($params['slug']);
    $cats = Category::allByCompany((int)$company['id']);
    $p = ['name'=>'','description'=>'','price'=>0,'promo_price'=>null,'sku'=>'','sort_order'=>0,'active'=>1,'category_id'=>null,'image'=>null];
    return $this->view('admin/products/form', compact('company','cats','p'));
  }

  private function handleUpload(?array $file): ?string {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
    $name = 'p_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    $dest = __DIR__ . '/../../public/uploads/' . $name;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
      return 'uploads/' . $name;
    }
    return null;
  }

  public function store($params){
    [$u,$company] = $this->guard($params['slug']);
    $img = $this->handleUpload($_FILES['image'] ?? null);
    $data = [
      'company_id'=>$company['id'],
      'category_id'=>$_POST['category_id'] ?: null,
      'name'=>trim($_POST['name']),
      'description'=>trim($_POST['description'] ?? ''),
      'price'=>(float)$_POST['price'],
      'promo_price'=>($_POST['promo_price'] === '' ? null : (float)$_POST['promo_price']),
      'sku'=>trim($_POST['sku'] ?? ''),
      'image'=>$img,
      'active'=>isset($_POST['active'])?1:0,
      'sort_order'=>(int)$_POST['sort_order'],
    ];
    Product::create($data);
    header('Location: ' . base_url("admin/{$company['slug']}/products"));
  }

  public function edit($params){
    [$u,$company] = $this->guard($params['slug']);
    $cats = Category::allByCompany((int)$company['id']);
    $p = Product::find((int)$params['id']);
    return $this->view('admin/products/form', compact('company','cats','p'));
  }

  public function update($params){
    [$u,$company] = $this->guard($params['slug']);
    $p = Product::find((int)$params['id']);
    $img = $this->handleUpload($_FILES['image'] ?? null) ?: $p['image'];
    $data = [
      'category_id'=>$_POST['category_id'] ?: null,
      'name'=>trim($_POST['name']),
      'description'=>trim($_POST['description'] ?? ''),
      'price'=>(float)$_POST['price'],
      'promo_price'=>($_POST['promo_price'] === '' ? null : (float)$_POST['promo_price']),
      'sku'=>trim($_POST['sku'] ?? ''),
      'image'=>$img,
      'active'=>isset($_POST['active'])?1:0,
      'sort_order'=>(int)$_POST['sort_order'],
    ];
    Product::update((int)$params['id'], $data);
    header('Location: ' . base_url("admin/{$company['slug']}/products"));
  }

  public function destroy($params){
    [$u,$company] = $this->guard($params['slug']);
    Product::delete((int)$params['id']);
    header('Location: ' . base_url("admin/{$company['slug']}/products"));
  }
}
