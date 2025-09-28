<?php
// app/controllers/PublicCartController.php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductCustomization.php';

class PublicCartController extends Controller
{
    /** Garantir sessão com o nome configurado */
    private function bootSession(): void
    {
        if (class_exists('Auth')) {
            Auth::start();
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    /** Retorna referência ao array da sacola na sessão */
    private function &sessionCart(): array
    {
        $this->bootSession();
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        return $_SESSION['cart'];
    }

    /** Sanitiza e copia dados de personalização salvos na sessão */
    private function snapshotCustomization(int $productId): ?array
    {
        $this->bootSession();
        if (empty($_SESSION['customizations'][$productId]) || !is_array($_SESSION['customizations'][$productId])) {
            return null;
        }
        $raw = $_SESSION['customizations'][$productId];
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
                foreach ($items as $item) {
                    if (!empty($item['default'])) {
                        $selectedIds[] = (int)$item['id'];
                    }
                }
                if (!$selectedIds && $items) {
                    $selectedIds[] = (int)$items[0]['id'];
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

            $wanted = $comboMap[$gid] ?? null;
            $selectedSimpleIds = [];
            if (is_array($wanted)) {
                foreach ($wanted as $sid) {
                    $selectedSimpleIds[] = (int)$sid;
                }
            } elseif ($wanted !== null) {
                $selectedSimpleIds[] = (int)$wanted;
            } else {
                foreach ($items as $opt) {
                    if (!empty($opt['default'])) {
                        $selectedSimpleIds[] = (int)$opt['simple_id'];
                    }
                }
                if (!$selectedSimpleIds && $items) {
                    $selectedSimpleIds[] = (int)$items[0]['simple_id'];
                }
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
                $itemData = [
                    'simple_id' => $simpleId,
                    'combo_item_id' => isset($opt['id']) ? (int)$opt['id'] : $simpleId,
                    'name' => (string)($opt['name'] ?? ''),
                    'delta' => $delta,
                    'image' => $opt['image'] ?? null,
                    'customizable' => !empty($opt['customizable']) || !empty($opt['allow_customize']),
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
                    $line = [
                        'name' => (string)($item['name'] ?? ''),
                        'qty' => $qty,
                        'unit_price' => $priceUnit,
                        'price' => $priceUnit * $qty,
                    ];
                    if ($line['price'] > 0) {
                        $result['total_delta'] += $line['price'];
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

        $cartRef =& $this->sessionCart();
        $items = $this->hydrateCartItems($cartRef, $company);

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
        ]);
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

        $cartRef =& $this->sessionCart();
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

        $uid = isset($_POST['uid']) ? (string)$_POST['uid'] : '';
        if ($uid === '') {
            header('Location: ' . base_url($slug . '/cart'));
            exit;
        }

        $action = $_POST['action'] ?? null;
        $qtyParam = isset($_POST['qty']) ? (int)$_POST['qty'] : null;

        $cartRef =& $this->sessionCart();
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

        // Reindexa para evitar buracos
        $cartRef = array_values($cartRef);

        header('Location: ' . base_url($slug . '/cart'));
        exit;
    }
}
