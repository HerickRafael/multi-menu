<?php
// app/controllers/PublicProductController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Helpers.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductCustomization.php';

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

        // Renderiza a view pública
        // A view espera: $company, $product, $comboGroups, $mods
        return $this->view('public/product', [
            'company'          => $company,
            'product'          => $product,
            'comboGroups'      => $comboGroups,
            'mods'             => $mods,
            'hasCustomization' => $hasCustomization,
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

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION['customizations']) || !is_array($_SESSION['customizations'])) {
            $_SESSION['customizations'] = [];
        }

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
                if (!isset($mods[$gi]) || ($mods[$gi]['type'] ?? 'extra') === 'single') {
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

        $quantity = isset($_POST['qty']) ? max(1, (int)$_POST['qty']) : null;

        $_SESSION['customizations'][$id] = [
            'single'   => $customSingle,
            'qty'      => $customQty,
            'quantity' => $quantity,
        ];

        $redirect = base_url($slug . '/produto/' . $id);
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

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $saved = $_SESSION['customizations'][$id] ?? null;
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

        return $this->view('public/customization', compact('company', 'product', 'mods'));
    }
}
