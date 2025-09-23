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

  private function redirectToIndex(array $company, string $citySearch = '', string $zoneSearch = '', array $extra = []): void {
    $query = [];

    if ($citySearch !== '') {
      $query['city_search'] = $citySearch;
    }

    if ($zoneSearch !== '') {
      $query['zone_search'] = $zoneSearch;
    }

    foreach ($extra as $key => $value) {
      if ($value === '' || $value === null) {
        continue;
      }
      $query[$key] = $value;
    }

    $suffix = $query ? ('?' . http_build_query($query)) : '';

    header('Location: ' . base_url('admin/' . rawurlencode($company['slug']) . '/delivery-fees' . $suffix));
    exit;
  }

  private function renderPage(
    array $company,
    array $cityErrors = [],
    array $zoneErrors = [],
    array $oldCity = [],
    array $oldZone = [],
    array $settingsErrors = [],
    string $citySearch = '',
    string $zoneSearch = '',
    int $editCityId = 0,
    int $editZoneId = 0
  ) {
    $companyId = (int)$company['id'];

    $cities = DeliveryCity::allByCompany($companyId, $citySearch);
    $zones  = DeliveryZone::allByCompany($companyId, $zoneSearch);

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
      'settingsErrors' => $settingsErrors,
      'citySearch'  => $citySearch,
      'zoneSearch'  => $zoneSearch,
      'editCityId'  => $editCityId,
      'editZoneId'  => $editZoneId,
      'afterHoursFee' => $company['delivery_after_hours_fee'] ?? '0.00',
      'isFreeDelivery' => (int)($company['delivery_free_enabled'] ?? 0) === 1,
    ]);
  }

  public function index($params) {
    [, $company] = $this->guard($params['slug']);

    $citySearch = trim((string)($_GET['city_search'] ?? ''));
    $zoneSearch = trim((string)($_GET['zone_search'] ?? ''));
    $editCityId = (int)($_GET['edit_city'] ?? 0);
    $editZoneId = (int)($_GET['edit_zone'] ?? 0);

    return $this->renderPage($company, [], [], [], [], [], $citySearch, $zoneSearch, $editCityId, $editZoneId);
  }

  public function storeCity($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $name = trim((string)($_POST['name'] ?? ''));
    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));
    $errors = [];

    if ($name === '') {
      $errors[] = 'Informe o nome da cidade.';
    } elseif (DeliveryCity::existsByName($companyId, $name)) {
      $errors[] = 'Esta cidade já está cadastrada.';
    }

    if ($errors) {
      return $this->renderPage($company, $errors, [], ['name' => $name], [], [], $citySearch, $zoneSearch);
    }

    DeliveryCity::create([
      'company_id' => $companyId,
      'name'       => $name,
    ]);

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function destroyCity($params) {
    [, $company] = $this->guard($params['slug']);
    $id = (int)($params['id'] ?? 0);

    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));

    if ($id > 0) {
      DeliveryCity::delete($id, (int)$company['id']);
    }

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function storeZone($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $cityId       = (int)($_POST['city_id'] ?? 0);
    $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
    $feeRaw       = trim((string)($_POST['fee'] ?? ''));
    $citySearch   = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch   = trim((string)($_POST['zone_search'] ?? ''));

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
        ],
        [],
        $citySearch,
        $zoneSearch
      );
    }

    $feeValue = number_format((float)$feeNormalized, 2, '.', '');

    DeliveryZone::create([
      'company_id'   => $companyId,
      'city_id'      => $cityId,
      'neighborhood' => $neighborhood,
      'fee'          => $feeValue,
    ]);

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function destroyZone($params) {
    [, $company] = $this->guard($params['slug']);
    $id = (int)($params['id'] ?? 0);

    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));

    if ($id > 0) {
      DeliveryZone::delete($id, (int)$company['id']);
    }

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function updateCity($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];
    $id = (int)($params['id'] ?? 0);

    $name = trim((string)($_POST['name'] ?? ''));
    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));

    $errors = [];
    $city = $id > 0 ? DeliveryCity::findForCompany($id, $companyId) : null;

    if (!$city) {
      $errors[] = 'Cidade inválida.';
    }

    if ($name === '') {
      $errors[] = 'Informe o nome da cidade.';
    } elseif (!$errors && DeliveryCity::existsByName($companyId, $name, $id)) {
      $errors[] = 'Esta cidade já está cadastrada.';
    }

    if ($errors) {
      return $this->renderPage(
        $company,
        $errors,
        [],
        ['name' => $name],
        [],
        [],
        $citySearch,
        $zoneSearch,
        $id
      );
    }

    DeliveryCity::update($id, $companyId, ['name' => $name]);

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function updateZone($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];
    $id = (int)($params['id'] ?? 0);

    $cityId       = (int)($_POST['city_id'] ?? 0);
    $neighborhood = trim((string)($_POST['neighborhood'] ?? ''));
    $feeRaw       = trim((string)($_POST['fee'] ?? ''));
    $citySearch   = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch   = trim((string)($_POST['zone_search'] ?? ''));

    $errors = [];
    $zone = $id > 0 ? DeliveryZone::findForCompany($id, $companyId) : null;

    if (!$zone) {
      $errors[] = 'Taxa de entrega inválida.';
    }

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

    if (!$errors && DeliveryZone::existsForCity($companyId, $cityId, $neighborhood, $id)) {
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
        ],
        [],
        $citySearch,
        $zoneSearch,
        0,
        $id
      );
    }

    $feeValue = number_format((float)$feeNormalized, 2, '.', '');

    DeliveryZone::update($id, $companyId, [
      'city_id'      => $cityId,
      'neighborhood' => $neighborhood,
      'fee'          => $feeValue,
    ]);

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function adjustZones($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $amountRaw  = trim((string)($_POST['amount'] ?? ''));
    $operation  = $_POST['operation'] ?? 'increase';
    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));

    $errors = [];
    $normalized = str_replace(',', '.', $amountRaw);

    if ($amountRaw === '') {
      $errors[] = 'Informe o valor em reais para ajustar as taxas.';
    } elseif (!is_numeric($normalized)) {
      $errors[] = 'Valor para ajuste inválido.';
    } elseif ((float)$normalized <= 0) {
      $errors[] = 'O ajuste deve ser maior que zero.';
    }

    if ($errors) {
      return $this->renderPage($company, [], $errors, [], [], [], $citySearch, $zoneSearch);
    }

    $operation = $operation === 'decrease' ? 'decrease' : 'increase';
    DeliveryZone::adjustAll($companyId, (float)$normalized, $operation);

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function updateAfterHoursFee($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $feeRaw     = trim((string)($_POST['after_hours_fee'] ?? ''));
    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));

    $errors = [];
    $normalized = str_replace(',', '.', $feeRaw);

    if ($feeRaw === '') {
      $errors[] = 'Informe o adicional após as 18h.';
    } elseif (!is_numeric($normalized)) {
      $errors[] = 'Valor do adicional inválido.';
    } elseif ((float)$normalized < 0) {
      $errors[] = 'O adicional não pode ser negativo.';
    }

    if ($errors) {
      $company['delivery_after_hours_fee'] = $feeRaw;
      return $this->renderPage($company, [], [], [], [], $errors, $citySearch, $zoneSearch);
    }

    $feeValue = number_format((float)$normalized, 2, '.', '');
    Company::updateDeliveryFeeSettings($companyId, ['delivery_after_hours_fee' => $feeValue]);

    $company = Company::find($companyId) ?: $company;

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }

  public function toggleFreeDelivery($params) {
    [, $company] = $this->guard($params['slug']);
    $companyId = (int)$company['id'];

    $citySearch = trim((string)($_POST['city_search'] ?? ''));
    $zoneSearch = trim((string)($_POST['zone_search'] ?? ''));
    $value      = (int)($_POST['free_delivery'] ?? 0) === 1 ? 1 : 0;

    Company::updateDeliveryFeeSettings($companyId, ['delivery_free_enabled' => $value]);

    $company = Company::find($companyId) ?: $company;

    $this->redirectToIndex($company, $citySearch, $zoneSearch);
  }
}
