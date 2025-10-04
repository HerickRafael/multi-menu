<?php
// app/controllers/PublicProductController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductCustomization.php';
require_once __DIR__ . '/../core/AuthCustomer.php';
require_once __DIR__ . '/../services/CartStorage.php';

class PublicProductController extends Controller
{
    /**
     * GET /{slug}/produto/{id}
     * Mostra a página pública do produto.
     * - Carrega empresa por slug
     * - Valida que o produto pertence à empresa e está ativo
     * - Carrega grupos de opções (combo) + itens (se o produto for do tipo != 'simple')
     */
    public function show($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        // Empresa
        $company = Company::findBySlug($slug);
        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        // Produto
        $product = Product::find($id);
        if (
            !$product ||
            (int)$product['company_id'] !== (int)$company['id'] ||
            (int)($product['active'] ?? 0) !== 1
        ) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        // Grupos de opções (combo) — somente se tipo != 'simple'
        $comboGroups = [];
        $type = $product['type'] ?? 'simple';
        if ($type !== 'simple' && method_exists('Product', 'getComboGroupsWithItems')) {
            $comboGroups = Product::getComboGroupsWithItems($id);
        }

        $mods = ProductCustomization::loadForPublic($id);
        $hasCustomization = !empty($mods);

        $requireLogin = (bool)(config('login_required') ?? false);
        $isLogged = AuthCustomer::current($slug) !== null;
        $forceLoginModal = $requireLogin && !$isLogged && !empty($_GET['login']);

        // Renderiza a view pública
        // A view espera: $company, $product, $comboGroups, $mods
        return $this->view('public/product', [
            'company'          => $company,
            'product'          => $product,
            'comboGroups'      => $comboGroups,
            'mods'             => $mods,
            'hasCustomization' => $hasCustomization,
            'forceLoginModal'  => $forceLoginModal,
        ]);
    }

    /**
     * (Opcional) POST /{slug}/produto/{id}/customizar
     * Persiste a customização escolhida pelo cliente (se necessário) ou já encaminha ao carrinho.
     */
    public function saveCustomization($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);
        if (!$company || (int)($company['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        $product = Product::find($id);
        if (
            !$product ||
            (int)$product['company_id'] !== (int)$company['id'] ||
            (int)($product['active'] ?? 0) !== 1
        ) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        $mods = ProductCustomization::loadForPublic($id);
        if (!$mods) {
            http_response_code(400);
            echo "Personalização indisponível para este produto.";
            return;
        }

        $parentId = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
        $redirectTarget = $id;
        if ($parentId && $parentId !== $id) {
            $parentProduct = Product::find($parentId);
            if (
                $parentProduct &&
                (int)$parentProduct['company_id'] === (int)$company['id'] &&
                (int)($parentProduct['active'] ?? 0) === 1
            ) {
                $redirectTarget = $parentId;
            }
        }

        $store = CartStorage::instance();

        $customSingle = [];
        if (isset($_POST['custom_single']) && is_array($_POST['custom_single'])) {
            foreach ($_POST['custom_single'] as $g => $idx) {
                $gi = (int)$g;
                $sel = (int)$idx;
                if (!isset($mods[$gi]['items']) || !is_array($mods[$gi]['items'])) {
                    continue;
                }
                $maxIdx = count($mods[$gi]['items']) - 1;
                if ($sel < 0 || $sel > $maxIdx) {
                    continue;
                }
                $customSingle[$gi] = $sel;
            }
        }

        $customQty = [];
        if (isset($_POST['custom_qty']) && is_array($_POST['custom_qty'])) {
            foreach ($_POST['custom_qty'] as $g => $items) {
                if (!is_array($items)) continue;
                $gi = (int)$g;
                if (!isset($mods[$gi])) {
                    continue;
                }
                $gType = $mods[$gi]['type'] ?? 'extra';
                if ($gType === 'single' || $gType === 'addon') {
                    continue;
                }

                foreach ($items as $i => $qty) {
                    $ii = (int)$i;
                    if (!isset($mods[$gi]['items'][$ii])) {
                        continue;
                    }

                    $item = $mods[$gi]['items'][$ii];
                    $min = isset($item['min']) ? (int)$item['min'] : 0;
                    $max = isset($item['max']) ? (int)$item['max'] : $min;
                    if ($max <= 0) {
                        $max = max($min, 99);
                    }

                    $val = (int)$qty;
                    if ($val < $min) {
                        $val = $min;
                    }
                    if ($max > 0 && $val > $max) {
                        $val = $max;
                    }

                    $customQty[$gi][$ii] = $val;
                }
            }
        }

        $customChoice = [];
        if (isset($_POST['custom_choice']) && is_array($_POST['custom_choice'])) {
            foreach ($_POST['custom_choice'] as $g => $vals) {
                $gi = (int)$g;
                if (!isset($mods[$gi]) || ($mods[$gi]['type'] ?? '') !== 'addon') {
                    continue;
                }
                $items = $mods[$gi]['items'] ?? [];
                if (!$items) {
                    continue;
                }
                $maxIdx = count($items) - 1;
                $minSel = isset($mods[$gi]['min']) ? max(0, (int)$mods[$gi]['min']) : 0;
                $maxSel = isset($mods[$gi]['max']) ? (int)$mods[$gi]['max'] : count($items);
                if ($maxSel < 1) {
                    $maxSel = count($items);
                }
                if ($maxSel < $minSel) {
                    $maxSel = $minSel;
                }

                $selected = [];
                foreach ((array)$vals as $val) {
                    $ii = (int)$val;
                    if ($ii < 0 || $ii > $maxIdx) {
                        continue;
                    }
                    if (!in_array($ii, $selected, true)) {
                        $selected[] = $ii;
                    }
                    if ($maxSel > 0 && count($selected) >= $maxSel) {
                        // não permite exceder o máximo
                        continue;
                    }
                }

                if ($maxSel > 0 && count($selected) > $maxSel) {
                    $selected = array_slice($selected, 0, $maxSel);
                }

                if ($minSel > 0 && count($selected) < $minSel) {
                    // garante o mínimo preenchendo com defaults e depois com os primeiros itens disponíveis
                    foreach ($items as $ii => $item) {
                        if (!empty($item['selected']) && !in_array($ii, $selected, true)) {
                            $selected[] = $ii;
                            if (count($selected) >= $minSel) {
                                break;
                            }
                        }
                    }
                    for ($ii = 0; $ii <= $maxIdx && count($selected) < $minSel; $ii++) {
                        if (!in_array($ii, $selected, true)) {
                            $selected[] = $ii;
                        }
                    }
                }

                $customChoice[$gi] = array_slice($selected, 0, max(0, $maxSel));
            }
        }

        $quantity = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : null;

        $store->setCustomization($id, [
            'single'   => $customSingle,
            'qty'      => $customQty,
            'choice'   => $customChoice,
            'quantity' => $quantity,
        ]);

        $redirect = base_url($slug . '/produto/' . $redirectTarget);
        header('Location: ' . $redirect);
        exit;
    }

    public function customize($params)
    {
        $slug = $params['slug'] ?? null;
        $id   = isset($params['id']) ? (int)$params['id'] : 0;

        $company = Company::findBySlug($slug);
        if (!$company || !$company['active']) {
            http_response_code(404);
            echo "Empresa não encontrada";
            return;
        }

        $requireLogin = (bool)(config('login_required') ?? false);
        if ($requireLogin && !AuthCustomer::require($slug)) {
            $redirect = base_url($slug . '/produto/' . $id . '?login=1');
            header('Location: ' . $redirect);
            exit;
        }

        $product = Product::find($id);
        if (!$product || (int)$product['company_id'] !== (int)$company['id'] || (int)($product['active'] ?? 0) !== 1) {
            http_response_code(404);
            echo "Produto não encontrado";
            return;
        }

        $mods = ProductCustomization::loadForPublic($id);
        if (!$mods) {
            http_response_code(404);
            echo "Personalização indisponível para este produto.";
            return;
        }

        $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
        $parentBackUrl = null;
        if ($parentId && $parentId !== $id) {
            $parentProduct = Product::find($parentId);
            if (
                $parentProduct &&
                (int)$parentProduct['company_id'] === (int)$company['id'] &&
                (int)($parentProduct['active'] ?? 0) === 1
            ) {
                $parentBackUrl = base_url($slug . '/produto/' . $parentId);
            } else {
                $parentId = 0;
            }
        } else {
            $parentId = 0;
        }

        $store = CartStorage::instance();
        $saved = $store->getCustomization($id);
        if ($saved) {
            foreach ($mods as $gi => &$group) {
                $gType = $group['type'] ?? 'extra';
                if ($gType === 'single') {
                    $sel = isset($saved['single'][$gi]) ? (int)$saved['single'][$gi] : null;
                    if ($sel !== null) {
                        $maxIdx = count($group['items']) - 1;
                        if ($sel < 0 || $sel > $maxIdx) {
                            $sel = null;
                        }
                    }
                    if ($sel !== null) {
                        foreach ($group['items'] as $ii => &$item) {
                            $item['default'] = ($ii === $sel);
                        }
                        unset($item);
                    }
                } elseif ($gType === 'addon') {
                    $selected = isset($saved['choice'][$gi]) && is_array($saved['choice'][$gi]) ? $saved['choice'][$gi] : [];
                    $valid = [];
                    $maxIdx = count($group['items']) - 1;
                    foreach ($selected as $idx) {
                        $ii = (int)$idx;
                        if ($ii >= 0 && $ii <= $maxIdx && !in_array($ii, $valid, true)) {
                            $valid[] = $ii;
                        }
                    }
                    foreach ($group['items'] as $ii => &$item) {
                        $isSel = in_array($ii, $valid, true);
                        $item['selected'] = $isSel;
                        $item['default']  = $isSel;
                    }
                    unset($item);
                } elseif (isset($saved['qty'][$gi]) && is_array($saved['qty'][$gi])) {
                    foreach ($group['items'] as $ii => &$item) {
                        if (!isset($saved['qty'][$gi][$ii])) {
                            continue;
                        }
                        $min = isset($item['min']) ? (int)$item['min'] : 0;
                        $max = isset($item['max']) ? (int)$item['max'] : $min;
                        if ($max <= 0) {
                            $max = max($min, 99);
                        }
                        $val = (int)$saved['qty'][$gi][$ii];
                        if ($val < $min) {
                            $val = $min;
                        }
                        if ($max > 0 && $val > $max) {
                            $val = $max;
                        }
                        $item['qty'] = $val;
                    }
                    unset($item);
                }
            }
            unset($group);
        }

        return $this->view('public/customization', [
            'company'        => $company,
            'product'        => $product,
            'mods'           => $mods,
            'parentBackUrl'  => $parentBackUrl,
            'parentProductId'=> $parentId,
        ]);
    }
}
