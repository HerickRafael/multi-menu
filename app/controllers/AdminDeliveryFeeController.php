<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/DeliveryZone.php';

class AdminDeliveryFeeController extends Controller {
  private function guard(string $slug): array {
    Auth::start();
    $user = Auth::user();
    if (!$user) {
      header('Location: ' . base_url('admin/' . rawurlencode($slug) . '/login'));
      exit;
    }

    $company = Company::findBySlug($slug);
    if (!$company) {
      echo 'Empresa inválida';
      exit;
    }

    if ($user['role'] !== 'root' && (int)$user['company_id'] !== (int)$company['id']) {
      echo 'Acesso negado';
      exit;
    }

    return [$user, $company];
  }

  public function index($params) {
    [$user, $company] = $this->guard($params['slug']);
    $zones = DeliveryZone::allByCompany((int)$company['id']);
    $errors = [];
    $old = ['city' => '', 'neighborhood' => '', 'fee' => ''];

    return $this->view('admin/delivery-fees/index', compact('company', 'zones', 'errors', 'old'));
  }

  public function store($params) {
    [$user, $company] = $this->guard($params['slug']);

    $city         = trim((string)($_POST['city'] ?? ''));
    $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
    $feeRaw       = trim((string)($_POST['fee'] ?? ''));

    $errors = [];
    $feeNormalized = str_replace(',', '.', $feeRaw);

    if ($city === '') {
      $errors[] = 'Informe a cidade.';
    }

    if ($neighborhood === '') {
      $errors[] = 'Informe o bairro.';
    }

    if ($feeRaw === '') {
      $errors[] = 'Informe o valor da taxa de entrega.';
    } elseif (!is_numeric($feeNormalized)) {
      $errors[] = 'Valor da taxa inválido.';
    } elseif ((float)$feeNormalized < 0) {
      $errors[] = 'A taxa não pode ser negativa.';
    }

    if ($errors) {
      $zones = DeliveryZone::allByCompany((int)$company['id']);
      $old = [
        'city' => $city,
        'neighborhood' => $neighborhood,
        'fee' => $feeRaw,
      ];

      return $this->view('admin/delivery-fees/index', compact('company', 'zones', 'errors', 'old'));
    }

    $feeValue = number_format((float)$feeNormalized, 2, '.', '');

    DeliveryZone::create([
      'company_id'   => (int)$company['id'],
      'city'         => $city,
      'neighborhood' => $neighborhood,
      'fee'          => $feeValue,
    ]);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees'));
    exit;
  }

  public function destroy($params) {
    [$user, $company] = $this->guard($params['slug']);
    $id = (int)($params['id'] ?? 0);

    if ($id > 0) {
      DeliveryZone::delete($id, (int)$company['id']);
    }

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees'));
    exit;
  }
}
