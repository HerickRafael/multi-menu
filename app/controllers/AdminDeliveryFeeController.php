<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/DeliveryCity.php';
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

  private function renderPage(array $company, array $cityErrors = [], array $zoneErrors = [], array $oldCity = [], array $oldZone = []) {
    $companyId = (int)$company['id'];

    $cities = DeliveryCity::allByCompany($companyId);
    $zones  = DeliveryZone::allByCompany($companyId);

    $oldCity = $oldCity + ['name' => ''];
    $oldZone = $oldZone + ['city_id' => '', 'neighborhood' => '', 'fee' => ''];

    return $this->view('admin/delivery-fees/index', [
      'company'     => $company,
      'cities'      => $cities,
      'zones'       => $zones,
      'cityErrors'  => $cityErrors,
      'zoneErrors'  => $zoneErrors,
      'oldCity'     => $oldCity,
      'oldZone'     => $oldZone,
    ]);
  }

  public function index($params) {
    [, $company] = $this->guard($params['slug']);
    return $this->renderPage($company);
  }

  public function storeCity($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $name = trim((string)($_POST['name'] ?? ''));
    $errors = [];

    if ($name === '') {
      $errors[] = 'Informe o nome da cidade.';
    } elseif (DeliveryCity::existsByName($companyId, $name)) {
      $errors[] = 'Esta cidade já está cadastrada.';
    }

    if ($errors) {
      return $this->renderPage($company, $errors, [], ['name' => $name]);
    }

    DeliveryCity::create([
      'company_id' => $companyId,
      'name'       => $name,
    ]);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees'));
    exit;
  }

  public function destroyCity($params) {
    [, $company] = $this->guard($params['slug']);
    $id = (int)($params['id'] ?? 0);

    if ($id > 0) {
      DeliveryCity::delete($id, (int)$company['id']);
    }

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees'));
    exit;
  }

  public function storeZone($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $cityId       = (int)($_POST['city_id'] ?? 0);
    $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
    $feeRaw       = trim((string)($_POST['fee'] ?? ''));

    $errors = [];
    $feeNormalized = str_replace(',', '.', $feeRaw);

    $city = $cityId > 0 ? DeliveryCity::findForCompany($cityId, $companyId) : null;
    if (!$city) {
      $errors[] = 'Selecione uma cidade válida.';
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

    if (!$errors && DeliveryZone::existsForCity($companyId, $cityId, $neighborhood)) {
      $errors[] = 'Este bairro já está cadastrado para a cidade selecionada.';
    }

    if ($errors) {
      return $this->renderPage(
        $company,
        [],
        $errors,
        [],
        [
          'city_id'      => $cityId ?: '',
          'neighborhood' => $neighborhood,
          'fee'          => $feeRaw,
        ]
      );
    }

    $feeValue = number_format((float)$feeNormalized, 2, '.', '');

    DeliveryZone::create([
      'company_id'   => $companyId,
      'city_id'      => $cityId,
      'neighborhood' => $neighborhood,
      'fee'          => $feeValue,
    ]);

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees'));
    exit;
  }

  public function destroyZone($params) {
    [, $company] = $this->guard($params['slug']);
    $id = (int)($params['id'] ?? 0);

    if ($id > 0) {
      DeliveryZone::delete($id, (int)$company['id']);
    }

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees'));
    exit;
  }
}
