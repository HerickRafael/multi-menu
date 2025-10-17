<?php

declare(strict_types=1);
// app/controllers/PublicCartController.php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/AuthCustomer.php';
require_once __DIR__ . '/../services/CartStorage.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../models/DeliveryCity.php';
require_once __DIR__ . '/../models/DeliveryZone.php';
require_once __DIR__ . '/../models/PaymentMethod.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../services/OrderNotificationService.php';

class PublicCartController extends Controller
{
    /** @var CartStorage */
    private $storage;

    public function __construct()
    {
        $this->storage = CartStorage::instance();
    }

    /** Sanitiza e copia dados de personalização salvos na sessão */
    private function snapshotCustomization(int $productId): ?array
    {
        $raw = $this->storage->getCustomization($productId);

        if (!$raw || !is_array($raw)) {
            return null;
        }
        $result = [
            'single' => [],
            'choice' => [],
            'qty'    => [],
        ];

        if (isset($raw['single']) && is_array($raw['single'])) {
            foreach ($raw['single'] as $g => $idx) {
                $result['single'][(int)$g] = (int)$idx;
            }
        }

        if (isset($raw['choice']) && is_array($raw['choice'])) {
            foreach ($raw['choice'] as $g => $vals) {
                if (!is_array($vals)) {
                    continue;
                }
                $clean = [];

                foreach ($vals as $val) {
                    $clean[] = (int)$val;
                }
                $result['choice'][(int)$g] = array_values(array_unique($clean));
            }
        }

        if (isset($raw['qty']) && is_array($raw['qty'])) {
            foreach ($raw['qty'] as $g => $items) {
                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $i => $qty) {
                    $qtyInt = (int)$qty;

                    if ($qtyInt <= 0) {
                        continue;
                    }
                    $result['qty'][(int)$g][(int)$i] = $qtyInt;
                }
            }
        }

        if (isset($raw['quantity'])) {
            $quantity = max(1, (int)$raw['quantity']);
            $result['quantity'] = $quantity;
        }

        // Remove se nada foi preenchido
        if (!$result['single'] && !$result['choice'] && !$result['qty'] && empty($result['quantity'])) {
            return null;
        }

        return $result;
    }

    /** Monta snapshot com os itens padrões configurados no produto */
    private function defaultCustomizationSnapshot(int $productId): ?array
    {
        $mods = ProductCustomization::loadForPublic($productId);

        if (!$mods) {
            return null;
        }

        $snapshot = [
            'single' => [],
            'choice' => [],
            'qty'    => [],
        ];

        $hasData = false;

        foreach ($mods as $gi => $group) {
            $type = $group['type'] ?? 'extra';
            $items = $group['items'] ?? [];

            if (!$items) {
                continue;
            }

            if ($type === 'single') {
                $selectedIndex = null;

                foreach ($items as $ii => $item) {
                    if (!empty($item['default'])) {
                        $selectedIndex = $ii;
                        break;
                    }
                }

                if ($selectedIndex === null) {
                    $selectedIndex = 0;
                }
                $snapshot['single'][(int)$gi] = (int)$selectedIndex;
                $hasData = true;
            } elseif ($type === 'addon') {
                $selected = [];

                foreach ($items as $ii => $item) {
                    if (!empty($item['default']) || !empty($item['selected'])) {
                        $selected[] = (int)$ii;
                    }
                }

                if ($selected) {
                    $snapshot['choice'][(int)$gi] = array_values(array_unique($selected));
                    $hasData = true;
                }
            } else {
                $qtyItems = [];

                foreach ($items as $ii => $item) {
                    $qty = isset($item['qty']) ? (int)$item['qty'] : 0;

                    if ($qty <= 0 && !empty($item['default_qty'])) {
                        $qty = (int)$item['default_qty'];
                    }

                    if ($qty <= 0) {
                        continue;
                    }
                    $qtyItems[(int)$ii] = $qty;
                }

                if ($qtyItems) {
                    $snapshot['qty'][(int)$gi] = $qtyItems;
                    $hasData = true;
                }
            }
        }

        return $hasData ? $snapshot : null;
    }

    /**
     * Resolve seleção postada dos grupos do combo (ids dos produtos simples)
     * Retorna mapa group_id => simple_id|array
     */
    private function resolveComboSelection(array $product, array $postData): array
    {
        $selection = [];

        if (($product['type'] ?? 'simple') !== 'combo') {
            return $selection;
        }

        $groups = Product::getComboGroupsWithItems((int)$product['id']);

        foreach ($groups as $index => $group) {
            $groupId = (int)($group['id'] ?? 0);

            if ($groupId <= 0) {
                continue;
            }

            $items = $group['items'] ?? [];

            if (!$items) {
                continue;
            }

            $minQty = isset($group['min']) ? (int)$group['min'] : (int)($group['min_qty'] ?? 0);
            $rawValue = $postData[$index] ?? null;
            $selectedIds = [];

            if (is_array($rawValue)) {
                foreach ($rawValue as $val) {
                    $selectedIds[] = (int)$val;
                }
            } elseif ($rawValue !== null && $rawValue !== '') {
                $selectedIds[] = (int)$rawValue;
            }

            if (!$selectedIds) {
                if ($minQty > 0) {
                    foreach ($items as $item) {
                        if (!empty($item['default'])) {
                            $selectedIds[] = (int)$item['id'];
                        }
                    }

                    if (!$selectedIds && $items) {
                        $selectedIds[] = (int)$items[0]['id'];
                    }
                } else {
                    continue;
                }
            }

            $simpleChosen = [];

            foreach ($items as $item) {
                $comboItemId = (int)$item['id'];

                if (!in_array($comboItemId, $selectedIds, true)) {
                    continue;
                }
                $simpleId = isset($item['simple_id']) ? (int)$item['simple_id'] : (int)($item['simple_product_id'] ?? 0);

                if ($simpleId <= 0) {
                    continue;
                }
                $simpleChosen[] = $simpleId;
            }

            if (!$simpleChosen) {
                continue;
            }

            $max = isset($group['max']) ? (int)$group['max'] : (int)($group['max_qty'] ?? 1);

            if ($max > 0 && count($simpleChosen) > $max) {
                $simpleChosen = array_slice($simpleChosen, 0, $max);
            }

            $selection[$groupId] = count($simpleChosen) === 1 ? $simpleChosen[0] : array_values($simpleChosen);
        }

        return $selection;
    }

    /** Gera identificador curto para o item da sacola */
    private function generateUid(): string
    {
        return bin2hex(random_bytes(6));
    }

    /** Formata endereço completo em linhas para exibir no pedido */
    private function formatOrderAddress(array $address): string
    {
        $parts = [];

        $line1 = trim((string)($address['street'] ?? ''));
        $number = trim((string)($address['number'] ?? ''));

        if ($number !== '') {
            $line1 = $line1 !== '' ? $line1 . ', ' . $number : $number;
        }
        $complement = trim((string)($address['complement'] ?? ''));

        if ($complement !== '') {
            $line1 = $line1 !== '' ? $line1 . ' - ' . $complement : $complement;
        }

        if ($line1 !== '') {
            $parts[] = $line1;
        }

        $line2Segments = [];

        if (!empty($address['neighborhood'])) {
            $line2Segments[] = trim($address['neighborhood']);
        }
        $city = trim((string)($address['city'] ?? ''));

        if ($city !== '') {
            $line2Segments[] = $city;
        }

        if ($line2Segments) {
            $parts[] = implode(' - ', $line2Segments);
        }

        $reference = trim((string)($address['reference'] ?? ''));

        if ($reference !== '') {
            $parts[] = 'Referência: ' . $reference;
        }

        return implode("\n", array_filter($parts, static fn ($line) => $line !== ''));
    }

    /** Persiste endereço no pedido, ignorando caso a coluna não exista */
    private function persistOrderAddress(PDO $db, int $orderId, ?string $address): void
    {
        $address = $address !== null ? trim($address) : '';

        if ($address === '') {
            return;
        }

        try {
            $stmt = $db->prepare('UPDATE orders SET customer_address = :addr WHERE id = :id');
            $stmt->execute([
                ':addr' => $address,
                ':id'   => $orderId,
            ]);
        } catch (Throwable $e) {
            // Coluna opcional não existe; segue sem interromper fluxo
        }
    }

    /** Monta estrutura pronta para renderização e cálculo */
    private function hydrateCartItems(array $rawItems, array $company): array
    {
        $hydrated = [];

        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            if ((int)($item['company_id'] ?? 0) !== (int)($company['id'] ?? 0)) {
                continue;
            }

            $productId = (int)($item['product_id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }

            $product = Product::find($productId);

            if (!$product || (int)($product['company_id'] ?? 0) !== (int)($company['id'] ?? 0)) {
                continue;
            }

            if (!empty($product['active']) && (int)$product['active'] !== 1) {
                continue;
            }

            $qty = max(1, min(99, (int)($item['qty'] ?? 1)));

            $comboMap = isset($item['combo']) && is_array($item['combo']) ? $item['combo'] : [];
            $comboData = $this->expandComboData($product, $comboMap);

            $customData = isset($item['customization']) && is_array($item['customization'])
                ? $item['customization']
                : $this->defaultCustomizationSnapshot($productId);
            $baseCustomization = $this->expandCustomization($productId, $customData);

            $componentCustomizations = [];
            $componentExtra = 0.0;

            if ($comboData['selected_items']) {
                foreach ($comboData['selected_items'] as $selected) {
                    $simpleId = (int)($selected['simple_id'] ?? 0);

                    if ($simpleId <= 0) {
                        continue;
                    }
                    $rawCustom = null;

                    if (isset($item['combo_customizations']) && is_array($item['combo_customizations']) && array_key_exists($simpleId, $item['combo_customizations'])) {
                        $rawCustom = $item['combo_customizations'][$simpleId];
                    }

                    if ($rawCustom === null) {
                        $rawCustom = $this->defaultCustomizationSnapshot($simpleId);
                    }
                    $expanded = $this->expandCustomization($simpleId, $rawCustom);

                    if ($expanded['has_customization']) {
                        $componentCustomizations[$simpleId] = [
                            'component' => $selected,
                            'customization' => $expanded,
                        ];
                        $componentExtra += $expanded['total_delta'];
                    }
                }
            }

            $pricing = $comboData['pricing'];
            $unitPrice = ($pricing['total'] ?? $pricing['base'] ?? 0) + $baseCustomization['total_delta'] + $componentExtra;

            if ($unitPrice < 0) {
                $unitPrice = 0.0;
            }

            $hydrated[] = [
                'uid' => (string)($item['uid'] ?? ''),
                'product' => [
                    'id' => $productId,
                    'name' => $product['name'] ?? 'Produto',
                    'image' => $product['image'] ?? null,
                    'type' => $product['type'] ?? 'simple',
                ],
                'qty' => $qty,
                'combo' => $comboData,
                'customization' => $baseCustomization,
                'component_customizations' => $componentCustomizations,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $qty,
            ];
        }

        return $hydrated;
    }

    /** Expande dados de combo para visualização */
    private function expandComboData(array $product, array $comboMap): array
    {
        $result = [
            'groups' => [],
            'selected_items' => [],
            'pricing_map' => [],
            'pricing' => ['base' => $this->baseProductPrice($product), 'sum_delta' => 0, 'total' => $this->baseProductPrice($product)],
        ];

        if (($product['type'] ?? 'simple') !== 'combo') {
            return $result;
        }

        $groups = Product::getComboGroupsWithItems((int)$product['id']);

        foreach ($groups as $group) {
            $gid = (int)($group['id'] ?? 0);

            if ($gid <= 0) {
                continue;
            }
            $items = $group['items'] ?? [];

            if (!$items) {
                continue;
            }

            $minQty = isset($group['min']) ? (int)$group['min'] : (int)($group['min_qty'] ?? 0);
            $wanted = $comboMap[$gid] ?? null;
            $selectedSimpleIds = [];

            if (is_array($wanted)) {
                foreach ($wanted as $sid) {
                    $selectedSimpleIds[] = (int)$sid;
                }
            } elseif ($wanted !== null) {
                $selectedSimpleIds[] = (int)$wanted;
            } else {
                if ($minQty > 0) {
                    foreach ($items as $opt) {
                        if (!empty($opt['default'])) {
                            $selectedSimpleIds[] = (int)$opt['simple_id'];
                        }
                    }

                    if (!$selectedSimpleIds && $items) {
                        $selectedSimpleIds[] = (int)$items[0]['simple_id'];
                    }
                }
            }

            if (!$selectedSimpleIds) {
                continue;
            }

            $groupDetails = [];

            foreach ($items as $opt) {
                $simpleId = isset($opt['simple_id']) ? (int)$opt['simple_id'] : (int)($opt['simple_product_id'] ?? 0);

                if ($simpleId <= 0) {
                    continue;
                }

                if (!in_array($simpleId, $selectedSimpleIds, true)) {
                    continue;
                }
                $delta = isset($opt['delta']) ? (float)$opt['delta'] : (float)($opt['delta_price'] ?? 0);
                $basePrice = null;

                if (array_key_exists('base_price', $opt) && $opt['base_price'] !== null) {
                    $basePrice = (float)$opt['base_price'];
                } elseif (array_key_exists('price', $opt) && $opt['price'] !== null) {
                    $basePrice = (float)$opt['price'];
                }
                $isDefault = !empty($opt['default']) || !empty($opt['is_default']);
                $itemData = [
                    'simple_id' => $simpleId,
                    'combo_item_id' => isset($opt['id']) ? (int)$opt['id'] : $simpleId,
                    'name' => (string)($opt['name'] ?? ''),
                    'delta' => $delta,
                    'image' => $opt['image'] ?? null,
                    'customizable' => !empty($opt['customizable']) || !empty($opt['allow_customize']),
                    'base_price' => $basePrice,
                    'is_default' => $isDefault,
                    'default' => $isDefault,
                ];
                $groupDetails[] = $itemData;
                $result['selected_items'][] = $itemData;
            }

            if (!$groupDetails) {
                continue;
            }

            $result['groups'][] = [
                'id' => $gid,
                'name' => (string)($group['name'] ?? ''),
                'items' => $groupDetails,
            ];

            $result['pricing_map'][$gid] = count($groupDetails) === 1
                ? $groupDetails[0]['simple_id']
                : array_map(static function ($row) {
                    return $row['simple_id'];
                }, $groupDetails);
        }

        $result['pricing'] = Product::calculateComboTotal($product, $result['pricing_map']);

        return $result;
    }

    /** Retorna preço base do produto (considerando promocional) */
    private function baseProductPrice(array $product): float
    {
        $price = (float)($product['price'] ?? 0);
        $promo = (float)($product['promo_price'] ?? 0);

        if ($promo > 0 && $promo < $price) {
            return $promo;
        }

        return $price;
    }

    /** Expande personalização para renderização e totalização */
    private function expandCustomization(int $productId, ?array $customData): array
    {
        $result = [
            'groups' => [],
            'total_delta' => 0.0,
            'has_customization' => false,
        ];
        $mods = ProductCustomization::loadForPublic($productId);

        if (!$mods) {
            return $result;
        }

        if (!$customData) {
            return $result;
        }

        foreach ($mods as $gi => $group) {
            $type = $group['type'] ?? 'extra';
            $items = $group['items'] ?? [];

            if (!$items) {
                continue;
            }

            if ($type === 'single') {
                if (!isset($customData['single'][$gi])) {
                    continue;
                }
                $index = (int)$customData['single'][$gi];

                if (!isset($items[$index])) {
                    continue;
                }
                $item = $items[$index];
                $price = isset($item['sale_price']) ? (float)$item['sale_price'] : (float)($item['delta'] ?? 0);
                $result['groups'][] = [
                    'name' => (string)($group['name'] ?? ''),
                    'type' => 'single',
                    'items' => [[
                        'name' => (string)($item['name'] ?? ''),
                        'price' => $price,
                    ]],
                ];

                if ($price > 0) {
                    $result['total_delta'] += $price;
                }
                $result['has_customization'] = true;
            } elseif ($type === 'addon') {
                if (empty($customData['choice'][$gi]) || !is_array($customData['choice'][$gi])) {
                    continue;
                }
                $selected = [];

                foreach ($customData['choice'][$gi] as $idx) {
                    $idx = (int)$idx;

                    if (!isset($items[$idx])) {
                        continue;
                    }
                    $item = $items[$idx];
                    $price = isset($item['sale_price']) ? (float)$item['sale_price'] : (float)($item['delta'] ?? 0);
                    $selected[] = [
                        'name' => (string)($item['name'] ?? ''),
                        'price' => $price,
                    ];

                    if ($price > 0) {
                        $result['total_delta'] += $price;
                    }
                }

                if ($selected) {
                    $result['groups'][] = [
                        'name' => (string)($group['name'] ?? ''),
                        'type' => 'addon',
                        'items' => $selected,
                    ];
                    $result['has_customization'] = true;
                }
            } else {
                if (empty($customData['qty'][$gi]) || !is_array($customData['qty'][$gi])) {
                    continue;
                }
                $selected = [];

                foreach ($customData['qty'][$gi] as $idx => $qty) {
                    $idx = (int)$idx;
                    $qty = (int)$qty;

                    if ($qty <= 0 || !isset($items[$idx])) {
                        continue;
                    }
                    $item = $items[$idx];
                    $priceUnit = isset($item['sale_price']) ? (float)$item['sale_price'] : (float)($item['delta'] ?? 0);
                    $defaultQty = isset($item['default_qty']) ? (int)$item['default_qty'] : (isset($item['qty']) ? (int)$item['qty'] : null);
                    $deltaQty = $qty;

                    if ($defaultQty !== null) {
                        $deltaQty = $qty - $defaultQty;
                    }
                    $linePrice = $priceUnit * $deltaQty;
                    $line = [
                        'name' => (string)($item['name'] ?? ''),
                        'qty' => $qty,
                        'unit_price' => $priceUnit,
                        'price' => $linePrice,
                        'default_qty' => $defaultQty,
                        'delta_qty' => $deltaQty,
                    ];

                    if ($linePrice !== 0.0) {
                        $result['total_delta'] += $linePrice;
                    }
                    $selected[] = $line;
                }

                if ($selected) {
                    $result['groups'][] = [
                        'name' => (string)($group['name'] ?? ''),
                        'type' => 'qty',
                        'items' => $selected,
                    ];
                    $result['has_customization'] = true;
                }
            }
        }

        return $result;
    }

    /** GET /{slug}/cart */
    public function index($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $customer = AuthCustomer::current($slug);

        if ($requireLogin && !$customer) {
            $redirect = base_url($slug . '?login=1');
            header('Location: ' . $redirect);
            exit;
        }

        $cartRef = $this->storage->getCart();
        $items = $this->hydrateCartItems($cartRef, $company);

        if (!is_array($items)) {
            $items = [];
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float)$item['line_total'];
        }

        return $this->view('public/cart', [
            'company' => $company,
            'items'   => $items,
            'totals'  => [
                'subtotal' => $subtotal,
                'total'    => $subtotal,
            ],
            'slug' => $slug,
            'updateUrl' => base_url($slug . '/cart/update'),
            'customer' => $customer,
            'requireLogin' => $requireLogin,
        ]);
    }

    /** GET /{slug}/checkout */
    public function checkout($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $customer = AuthCustomer::current($slug);

        if ($requireLogin && !$customer) {
            $redirect = base_url($slug . '?login=1');
            header('Location: ' . $redirect);
            exit;
        }

        $cartRef = $this->storage->getCart();
        $items = $this->hydrateCartItems($cartRef, $company);

        if (!$items) {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += (float)$item['line_total'];
        }

        $deliveryAddress = $_SESSION['checkout_address'] ?? [];

        if (!is_array($deliveryAddress)) {
            $deliveryAddress = [];
        }

        $deliveryAddress = array_merge([
            'name'              => $customer['name'] ?? '',
            'phone'             => $customer['whatsapp'] ?? '',
            'street'            => '',
            'number'            => '',
            'neighborhood'      => '',
            'city'              => '',
            'complement'        => '',
            'reference'         => '',
            'notes'             => '',
            'city_id'           => 0,
            'zone_id'           => 0,
            'payment_method_id' => 0,
        ], $deliveryAddress);

        $companyId = (int)($company['id'] ?? 0);
        $cities = DeliveryCity::allByCompany($companyId);
        $zonesRaw = DeliveryZone::allByCompany($companyId);

        $selectedCityId = (int)($deliveryAddress['city_id'] ?? 0);
        $selectedZoneId = (int)($deliveryAddress['zone_id'] ?? 0);

        $zonesByCity = [];
        $selectedZone = null;

        foreach ($zonesRaw as $zone) {
            $cityId = (int)($zone['city_id'] ?? 0);
            $mapped = [
                'id'         => (int)($zone['id'] ?? 0),
                'city_id'    => $cityId,
                'name'       => (string)($zone['neighborhood'] ?? ''),
                'fee'        => (float)($zone['fee'] ?? 0),
                'city_name'  => (string)($zone['city_name'] ?? ''),
            ];

            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = $mapped;

            if ($mapped['id'] === $selectedZoneId) {
                $selectedZone = $mapped;
            }
        }

        if (!$selectedCityId && count($cities) === 1) {
            $selectedCityId = (int)($cities[0]['id'] ?? 0);
        }

        if ($selectedZoneId && !$selectedZone) {
            $selectedZoneId = 0;
            $deliveryAddress['zone_id'] = 0;
        }

        if ($selectedZone && !$selectedCityId) {
            $selectedCityId = $selectedZone['city_id'];
        }

        if ($selectedCityId && empty($deliveryAddress['city'])) {
            foreach ($cities as $cityRow) {
                if ((int)($cityRow['id'] ?? 0) === $selectedCityId) {
                    $deliveryAddress['city'] = (string)($cityRow['name'] ?? '');
                    break;
                }
            }
        }

        if ($selectedZone) {
            $deliveryAddress['neighborhood'] = $selectedZone['name'];

            if (!empty($selectedZone['city_name'])) {
                $deliveryAddress['city'] = $selectedZone['city_name'];
            }
        }

        $deliveryFee = $selectedZone ? (float)$selectedZone['fee'] : 0.0;
        $total = $subtotal + $deliveryFee;

        $paymentMethods = PaymentMethod::activeByCompany($companyId);
        // construir icon_url absoluto para cada método, para evitar problemas com caminhos relativos
        $baseUrlFull = function_exists('base_url') ? rtrim((string)base_url(), '/') : '';
        foreach ($paymentMethods as &$pm) {
            $pm = is_array($pm) ? $pm : [];
            $metaRaw = $pm['meta'] ?? null;
            if (is_string($metaRaw)) {
                $decoded = json_decode($metaRaw, true);
                $meta = is_array($decoded) ? $decoded : [];
            } elseif (is_array($metaRaw)) {
                $meta = $metaRaw;
            } else {
                $meta = [];
            }
            $pm['meta'] = $meta;
            $icon = '';
            // Preferir coluna `icon` quando disponível (migração incremental)
            if (!empty($pm['icon']) && is_string($pm['icon'])) {
                $icon = trim((string)$pm['icon']);
            } elseif (!empty($meta['icon']) && is_string($meta['icon'])) {
                $icon = trim((string)$meta['icon']);
            }
            $iconUrl = '';
            if ($icon !== '') {
                if (preg_match('#^https?://#i', $icon)) {
                    $iconUrl = $icon;
                } else {
                    if (str_starts_with($icon, '/')) {
                        $iconUrl = $baseUrlFull !== '' ? ($baseUrlFull . $icon) : $icon;
                    } else {
                        $iconUrl = $baseUrlFull !== '' ? base_url($icon) : ('/' . ltrim($icon, '/'));
                    }
                }
            } elseif (($pm['type'] ?? '') === 'pix') {
                // ícone padrão do pix quando não definido
                $pixPath = '/assets/card-brands/pix.svg';
                $iconUrl = $baseUrlFull !== '' ? ($baseUrlFull . $pixPath) : $pixPath;
            } elseif (($pm['type'] ?? '') === 'cash') {
                // ícone padrão do dinheiro quando não definido
                $cashPath = '/assets/card-brands/cash.svg';
                $iconUrl = $baseUrlFull !== '' ? ($baseUrlFull . $cashPath) : $cashPath;
            }
            $pm['icon_url'] = $iconUrl;
        }
        unset($pm);
        $selectedPaymentId = (int)($deliveryAddress['payment_method_id'] ?? 0);

        if (!$selectedPaymentId && $paymentMethods) {
            $selectedPaymentId = (int)$paymentMethods[0]['id'];
        }
        $deliveryAddress['city_id'] = $selectedCityId;
        $deliveryAddress['zone_id'] = $selectedZone ? $selectedZone['id'] : 0;
        $deliveryAddress['payment_method_id'] = $selectedPaymentId;

        $flash = $_SESSION['checkout_flash'] ?? null;
        unset($_SESSION['checkout_flash']);

        return $this->view('public/checkout', [
            'company'           => $company,
            'items'             => $items,
            'totals'            => [
                'subtotal' => $subtotal,
                'delivery' => $deliveryFee,
                'total'    => $total,
            ],
            'slug'              => $slug,
            'customer'          => $customer,
            'deliveryAddress'   => $deliveryAddress,
            'cities'            => $cities,
            'zonesByCity'       => $zonesByCity,
            'selectedCityId'    => $selectedCityId,
            'selectedZoneId'    => $selectedZone ? $selectedZone['id'] : 0,
            'paymentMethods'    => $paymentMethods,
            'selectedPaymentId' => $selectedPaymentId,
            'flash'             => $flash,
        ]);
    }

    /** GET /{slug}/checkout/success */
    public function checkoutSuccess($params)
    {
        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $success = $_SESSION['checkout_success'] ?? null;

        if (!$success || !is_array($success)) {
            header('Location: ' . base_url($slug));
            exit;
        }

        unset($_SESSION['checkout_success']);

        return $this->view('public/checkout_success', [
            'company' => $company,
            'slug'    => $slug,
            'order'   => $success,
        ]);
    }

    /** POST /{slug}/checkout */
    public function submitCheckout($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';

            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        $customer = AuthCustomer::current($slug);

        if ($requireLogin && !$customer) {
            $redirect = base_url($slug . '?login=1');
            header('Location: ' . $redirect);
            exit;
        }

        $cartRef = $this->storage->getCart();

        if (!$cartRef) {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $items = $this->hydrateCartItems($cartRef, $company);

        if (!$items) {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $companyId = (int)$company['id'];
        $cities = DeliveryCity::allByCompany($companyId);
        $zonesRaw = DeliveryZone::allByCompany($companyId);

        $cityMap = [];

        foreach ($cities as $cityRow) {
            $cityMap[(int)($cityRow['id'] ?? 0)] = (string)($cityRow['name'] ?? '');
        }

        $zoneMap = [];
        $zonesByCity = [];

        foreach ($zonesRaw as $zoneRow) {
            $zoneId = (int)($zoneRow['id'] ?? 0);
            $zoneMap[$zoneId] = $zoneRow;
            $cityId = (int)($zoneRow['city_id'] ?? 0);

            if (!isset($zonesByCity[$cityId])) {
                $zonesByCity[$cityId] = [];
            }
            $zonesByCity[$cityId][] = $zoneRow;
        }

        $activePaymentMethods = PaymentMethod::activeByCompany($companyId);

        $addressInput = isset($_POST['address']) && is_array($_POST['address']) ? $_POST['address'] : [];

        $clean = [
            'name'        => trim($addressInput['name'] ?? ''),
            'phone'       => trim($addressInput['phone'] ?? ''),
            'street'      => trim($addressInput['street'] ?? ''),
            'number'      => trim($addressInput['number'] ?? ''),
            'complement'  => trim($addressInput['complement'] ?? ''),
            'reference'   => trim($addressInput['reference'] ?? ''),
            'city_id'     => (int)($addressInput['city_id'] ?? 0),
            'zone_id'     => (int)($addressInput['zone_id'] ?? 0),
            'city'        => '',
            'neighborhood' => '',
            'notes'       => trim($_POST['order']['notes'] ?? ''),
        ];

        $deliveryFee = 0.0;

        if (!isset($cityMap[$clean['city_id']])) {
            $clean['city_id'] = 0;
        } else {
            $clean['city'] = $cityMap[$clean['city_id']];
        }

        if ($clean['zone_id'] && isset($zoneMap[$clean['zone_id']])) {
            $zone = $zoneMap[$clean['zone_id']];
            $zoneCityId = (int)($zone['city_id'] ?? 0);

            if (!$clean['city_id'] || $clean['city_id'] !== $zoneCityId) {
                $clean['city_id'] = $zoneCityId;
                $clean['city'] = $cityMap[$zoneCityId] ?? '';
            }
            $clean['neighborhood'] = (string)($zone['neighborhood'] ?? '');
            $deliveryFee = (float)($zone['fee'] ?? 0.0);
        } else {
            $clean['zone_id'] = 0;
            $clean['neighborhood'] = '';
            $deliveryFee = 0.0;
        }

        $paymentInput = isset($_POST['payment']) && is_array($_POST['payment']) ? $_POST['payment'] : [];
        $paymentMethodId = (int)($paymentInput['method_id'] ?? 0);
        $paymentMethod = $paymentMethodId ? PaymentMethod::findForCompany($paymentMethodId, $companyId) : null;

        if (!$paymentMethod || (int)($paymentMethod['active'] ?? 0) !== 1) {
            $paymentMethodId = 0;
        }
        $clean['payment_method_id'] = $paymentMethodId;

        // Processar valor em dinheiro se for método cash
        $cashAmount = 0.0;
        if ($paymentMethod && ($paymentMethod['type'] ?? '') === 'cash') {
            $cashAmount = (float)($_POST['cash_amount'] ?? 0);
        }

        $errors = [];

        if ($clean['name'] === '') {
            $errors[] = 'Informe o nome do destinatário.';
        }

        if ($clean['phone'] === '') {
            $errors[] = 'Informe o telefone para contato.';
        }
        $zonesForSelectedCity = $clean['city_id'] > 0 && isset($zonesByCity[$clean['city_id']])
            ? $zonesByCity[$clean['city_id']] : [];

        if ($clean['city_id'] <= 0 && !empty($cityMap)) {
            $errors[] = 'Selecione uma cidade atendida.';
        }

        if ($clean['zone_id'] <= 0 && !empty($zonesForSelectedCity)) {
            $errors[] = 'Selecione um bairro atendido.';
        }

        if ($clean['street'] === '') {
            $errors[] = 'Informe a rua/avenida.';
        }

        if ($clean['number'] === '') {
            $errors[] = 'Informe o número do endereço.';
        }

        // Validar se o número contém apenas dígitos
        if ($clean['number'] !== '' && !preg_match('/^\d+$/', $clean['number'])) {
            $errors[] = 'O número do endereço deve conter apenas números.';
        }

        if ($activePaymentMethods && $paymentMethodId <= 0) {
            $errors[] = 'Escolha um método de pagamento disponível.';
        }

        if ($errors) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => implode(' ', $errors),
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        $orderItemsPayload = [];
        $subtotal = 0.0;
        $itemsSummary = [];

        foreach ($items as $item) {
            $productId = (int)($item['product']['id'] ?? 0);

            if ($productId <= 0) {
                continue;
            }
            $quantity = max(1, (int)($item['qty'] ?? 1));
            $unitPrice = (float)($item['unit_price'] ?? 0.0);
            $lineTotal = (float)($item['line_total'] ?? ($unitPrice * $quantity));

            $orderItemsPayload[] = [
                'product_id'          => $productId,
                'quantity'            => $quantity,
                'unit_price'          => $unitPrice,
                'line_total'          => $lineTotal,
                'combo_data'          => $item['combo'] ?? null,
                'customization_data'  => $item['customization'] ?? null,
                'notes'               => $item['notes'] ?? null,
            ];
            $subtotal += $lineTotal;

            $itemsSummary[] = [
                'name'       => (string)($item['product']['name'] ?? 'Produto'),
                'quantity'   => $quantity,
                'line_total' => $lineTotal,
            ];
        }

        if (!$orderItemsPayload) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => 'Seu carrinho está vazio.',
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        $discount = 0.0;
        $total = max(0.0, $subtotal + $deliveryFee - $discount);

        // Validação específica para pagamento em dinheiro
        if ($paymentMethod && ($paymentMethod['type'] ?? '') === 'cash') {
            // Se cashAmount for 0, significa que não precisa de troco (pagamento exato)
            if ($cashAmount > 0 && $cashAmount < $total) {
                $deficit = $total - $cashAmount;
                $errors[] = 'Valor insuficiente. Falta R$ ' . number_format($deficit, 2, ',', '.') . ' para completar o pagamento.';
            }
            // Se cashAmount for 0, assumimos pagamento exato sem troco
        }

        // Verificar erros antes de prosseguir
        if ($errors) {
            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => implode(' ', $errors),
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        $paymentMethodName = $paymentMethod ? trim((string)($paymentMethod['name'] ?? '')) : '';
        $paymentInstructions = $paymentMethod ? trim((string)($paymentMethod['instructions'] ?? '')) : '';

        $orderNotesParts = [];

        if ($clean['notes'] !== '') {
            $orderNotesParts[] = 'Observações: ' . $clean['notes'];
        }

        if ($paymentMethodName !== '') {
            $paymentLine = 'Pagamento: ' . $paymentMethodName;

            if ($paymentInstructions !== '') {
                $paymentLine .= ' — ' . $paymentInstructions;
            }

            // Adicionar informações de troco para pagamento em dinheiro
            if (($paymentMethod['type'] ?? '') === 'cash') {
                if ($cashAmount > 0) {
                    $change = $cashAmount - $total;
                    $paymentLine .= ' — Valor informado: R$ ' . number_format($cashAmount, 2, ',', '.');
                    if ($change > 0) {
                        $paymentLine .= ' (Troco: R$ ' . number_format($change, 2, ',', '.') . ')';
                    }
                } else {
                    $paymentLine .= ' — Pagamento exato (sem troco)';
                }
            }

            $orderNotesParts[] = $paymentLine;
        }
        $orderNotes = $orderNotesParts ? implode("\n\n", $orderNotesParts) : null;

        $formattedAddress = $this->formatOrderAddress($clean);

        $db = $this->db();
        try {
            $db->beginTransaction();
            $orderId = Order::create($db, [
                'company_id'       => $companyId,
                'customer_name'    => $clean['name'],
                'customer_phone'   => $clean['phone'],
                'subtotal'         => $subtotal,
                'delivery_fee'     => $deliveryFee,
                'discount'         => $discount,
                'total'            => $total,
                'status'           => 'pending',
                'notes'            => $orderNotes,
                'customer_address' => $formattedAddress,
                'payment_method_id' => $paymentMethodId,
            ]);

            foreach ($orderItemsPayload as $payload) {
                Order::addItem($db, $orderId, $payload);
            }

            $this->persistOrderAddress($db, $orderId, $formattedAddress);

            Order::emitOrderEvent($db, $orderId, $companyId, 'order.created');

            // Enviar notificação de novo pedido para grupos configurados
            try {
                $orderData = [
                    'id' => $orderId,
                    'customer_name' => $clean['name'],
                    'customer_phone' => $clean['phone'],
                    'total' => $total,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'discount' => $discount,
                    'payment_method' => $paymentMethod ? $paymentMethod['name'] : 'Não informado',
                    'items' => array_map(function($item) use ($db) {
                        $product = Product::find($item['product']['id'] ?? 0);
                        $itemData = [
                            'name' => $product['name'] ?? 'Produto',
                            'quantity' => $item['qty'] ?? 1,
                            'price' => $item['unit_price'] ?? 0,
                            'combo' => '',
                            'customization' => ''
                        ];
                        
                        // Processar dados de combo
                        if (isset($item['combo']) && is_array($item['combo'])) {
                            $comboParts = [];
                            if (!empty($item['combo']['selected_items'])) {
                                foreach ($item['combo']['selected_items'] as $comboItem) {
                                    $comboItemName = $comboItem['simple_name'] ?? $comboItem['name'] ?? '';
                                    if ($comboItemName) {
                                        $comboParts[] = $comboItemName;
                                    }
                                }
                            }
                            if ($comboParts) {
                                $itemData['combo'] = implode(', ', $comboParts);
                            }
                        }
                        
                        // Processar dados de personalização (mostrar apenas adições/remoções)
                        if (isset($item['customization']) && is_array($item['customization'])) {
                            $customParts = [];
                            if (!empty($item['customization']['groups'])) {
                                foreach ($item['customization']['groups'] as $group) {
                                    $groupName = $group['name'] ?? '';
                                    $groupType = $group['type'] ?? 'qty';
                                    
                                    if (!empty($group['items'])) {
                                        foreach ($group['items'] as $customItem) {
                                            $itemName = $customItem['name'] ?? '';
                                            $qty = $customItem['qty'] ?? 1;
                                            $deltaQty = $customItem['delta_qty'] ?? null;
                                            $price = $customItem['price'] ?? 0;
                                            
                                            // Mostrar apenas se:
                                            // 1. Tem delta_qty diferente de 0 (adicionado ou removido)
                                            // 2. OU se tem preço (custo extra)
                                            // 3. OU se é tipo 'addon' ou 'single' (sempre customização)
                                            if ($itemName) {
                                                $shouldShow = false;
                                                
                                                // Para tipos addon/single: sempre mostrar
                                                if (in_array($groupType, ['addon', 'single'])) {
                                                    $shouldShow = true;
                                                }
                                                // Para tipo qty: mostrar apenas se delta_qty != 0 ou tem preço
                                                elseif ($groupType === 'qty') {
                                                    if ($deltaQty !== null && $deltaQty != 0) {
                                                        $shouldShow = true;
                                                    } elseif ($price != 0) {
                                                        $shouldShow = true;
                                                    }
                                                }
                                                
                                                if ($shouldShow) {
                                                    // Formatar quantidade
                                                    if ($deltaQty !== null && $deltaQty > 0) {
                                                        // Item adicionado
                                                        $customParts[] = "+{$deltaQty}x {$itemName}";
                                                    } elseif ($deltaQty !== null && $deltaQty < 0) {
                                                        // Item removido
                                                        $customParts[] = "Sem {$itemName}";
                                                    } elseif ($qty > 1) {
                                                        $customParts[] = "{$qty}x {$itemName}";
                                                    } else {
                                                        $customParts[] = $itemName;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if ($customParts) {
                                $itemData['customization'] = implode(', ', $customParts);
                            }
                        }
                        
                        return $itemData;
                    }, $items),
                    'notes' => $orderNotes,
                    'customer_address' => $formattedAddress,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                OrderNotificationService::sendOrderNotification($companyId, $orderData);
            } catch (Exception $e) {
                // Log do erro mas não interrompe o fluxo do pedido
                error_log("Erro ao enviar notificação de pedido: " . $e->getMessage());
            }

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Checkout error: ' . $e->getMessage());

            $_SESSION['checkout_flash'] = [
                'type' => 'error',
                'message' => 'Não foi possível finalizar o pedido. Tente novamente em instantes.',
            ];
            $_SESSION['checkout_address'] = $clean;
            header('Location: ' . base_url($slug . '/checkout'));
            exit;
        }

        $_SESSION['checkout_address'] = $clean;
        $_SESSION['checkout_address']['delivery_fee'] = $deliveryFee;

        $this->storage->clearCart();

        unset($_SESSION['checkout_flash']);

        $_SESSION['checkout_success'] = [
            'order_id'           => $orderId,
            'customer_name'      => $clean['name'],
            'customer_phone'     => $clean['phone'],
            'total'              => $total,
            'subtotal'           => $subtotal,
            'delivery_fee'       => $deliveryFee,
            'payment_method'     => $paymentMethodName,
            'payment_instructions' => $paymentInstructions,
            'address'            => $formattedAddress,
            'notes'              => $clean['notes'],
            'items'              => $itemsSummary,
        ];

        header('Location: ' . base_url($slug . '/checkout/success'));
        exit;
    }

    /** POST /{slug}/cart/add */
    public function add($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';

            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $requireLogin = (bool)(config('login_required') ?? false);

        if ($requireLogin && !AuthCustomer::current()) {
            $redirect = $productId > 0
                ? base_url($slug . '/produto/' . $productId . '?login=1')
                : base_url($slug . '?login=1');
            header('Location: ' . $redirect);
            exit;
        }

        $product = $productId > 0 ? Product::find($productId) : null;

        if (!$product || (int)($product['company_id'] ?? 0) !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Produto não encontrado';

            return;
        }

        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
        $qty = max(1, min(99, $qty));

        $postCombo = isset($_POST['combo']) && is_array($_POST['combo']) ? $_POST['combo'] : [];
        $comboSelection = $this->resolveComboSelection($product, $postCombo);

        $baseCustomization = $this->snapshotCustomization($productId);

        if ($baseCustomization === null) {
            $baseCustomization = $this->defaultCustomizationSnapshot($productId);
        }
        $componentCustomizations = [];

        if ($comboSelection) {
            foreach ($comboSelection as $value) {
                $ids = is_array($value) ? $value : [$value];

                foreach ($ids as $sid) {
                    $sid = (int)$sid;

                    if ($sid <= 0) {
                        continue;
                    }
                    $snap = $this->snapshotCustomization($sid);

                    if ($snap === null) {
                        $snap = $this->defaultCustomizationSnapshot($sid);
                    }

                    if ($snap) {
                        $componentCustomizations[$sid] = $snap;
                    }
                }
            }
        }

        $cartRef = $this->storage->getCart();
        $cartRef[] = [
            'uid' => $this->generateUid(),
            'company_id' => (int)$company['id'],
            'product_id' => $productId,
            'qty' => $qty,
            'combo' => $comboSelection,
            'customization' => $baseCustomization,
            'combo_customizations' => $componentCustomizations,
            'added_at' => time(),
        ];

        $this->storage->setCart($cartRef);

        header('Location: ' . base_url($slug . '/cart'));
        exit;
    }

    /** POST /{slug}/cart/update */
    public function update($params)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método não permitido';

            return;
        }

        $slug = $params['slug'] ?? null;
        $company = Company::findBySlug($slug);

        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo 'Empresa não encontrada';

            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);

        if ($requireLogin && !AuthCustomer::current()) {
            $redirect = base_url($slug . '?login=1');
            header('Location: ' . $redirect);
            exit;
        }

        $uid = isset($_POST['uid']) ? (string)$_POST['uid'] : '';

        if ($uid === '') {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $action = $_POST['action'] ?? null;
        $qtyParam = isset($_POST['qty']) ? (int)$_POST['qty'] : null;

        $cartRef = $this->storage->getCart();

        foreach ($cartRef as $index => &$item) {
            if (!is_array($item)) {
                continue;
            }

            if ((string)($item['uid'] ?? '') !== $uid) {
                continue;
            }

            if ((int)($item['company_id'] ?? 0) !== (int)$company['id']) {
                continue;
            }

            if ($action === 'inc') {
                $item['qty'] = min(99, max(1, (int)($item['qty'] ?? 1) + 1));
                break;
            }

            if ($action === 'dec') {
                $current = (int)($item['qty'] ?? 1);
                $newQty = max(0, $current - 1);

                if ($newQty <= 0) {
                    unset($cartRef[$index]);
                } else {
                    $item['qty'] = $newQty;
                }
                break;
            }

            if ($qtyParam !== null) {
                if ($qtyParam <= 0) {
                    unset($cartRef[$index]);
                } else {
                    $item['qty'] = min(99, max(1, $qtyParam));
                }
                break;
            }
        }
        unset($item);

        // Reindexa e persiste
        $cartRef = array_values($cartRef);
        $this->storage->setCart($cartRef);

        if (!$cartRef) {
            unset($_SESSION['checkout_address'], $_SESSION['checkout_flash']);
        }

        header('Location: ' . base_url($slug . '/cart'));
        exit;
    }
}
