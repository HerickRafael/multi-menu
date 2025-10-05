<?php

declare(strict_types=1);
// app/models/ProductCustomization.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/Ingredient.php';

class ProductCustomization
{
    /**
     * Normaliza os dados vindos do formulário do admin.
     * Retorna um array no formato ['enabled'=>bool,'groups'=>[...]].
     */
    public static function sanitizePayload(array $payload, int $companyId): array
    {
        $enabled = !empty($payload['enabled']);
        $groups  = [];

        if (!empty($payload['groups']) && is_array($payload['groups'])) {
            $groups = self::normalizeGroups($payload['groups'], $companyId);
        }

        if (!$groups) {
            $enabled = false;
        }

        return [
            'enabled' => $enabled,
            'groups'  => $groups,
        ];
    }

    /**
     * Persiste os grupos/itens de personalização de um produto.
     * Espera receber os dados já normalizados via sanitizePayload().
     */
    public static function save(int $productId, array $customization): void
    {
        $enabled = !empty($customization['enabled']) && !empty($customization['groups']);
        $groups  = $enabled ? $customization['groups'] : [];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Limpa vínculos e grupos anteriores
            $pdo->prepare('DELETE pci FROM product_custom_items pci
                              INNER JOIN product_custom_groups pcg ON pcg.id = pci.group_id
                             WHERE pcg.product_id = ?')
                ->execute([$productId]);

            $pdo->prepare('DELETE FROM product_custom_groups WHERE product_id = ?')
                ->execute([$productId]);

            if ($groups) {
                $insGroup = $pdo->prepare(
                    'INSERT INTO product_custom_groups (product_id, name, type, min_qty, max_qty, sort_order)
                     VALUES (?,?,?,?,?,?)'
                );
                $insItem = $pdo->prepare(
                    'INSERT INTO product_custom_items (group_id, ingredient_id, label, delta, is_default, default_qty, min_qty, max_qty, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?)'
                );

                foreach ($groups as $gIndex => $group) {
                    $insGroup->execute([
                        $productId,
                        $group['name'],
                        $group['type'],
                        $group['min'],
                        $group['max'],
                        $group['sort_order'] ?? $gIndex,
                    ]);
                    $groupId = (int)$pdo->lastInsertId();

                    $items = $group['items'] ?? [];

                    foreach ($items as $iIndex => $item) {
                        $insItem->execute([
                            $groupId,
                            $item['ingredient_id'] ?? null,
                            $item['label'],
                            isset($item['delta']) ? (float)$item['delta'] : 0.00,
                            !empty($item['default']) ? 1 : 0,
                            (int)($item['default_qty'] ?? 1),
                            (int)($item['min_qty'] ?? 0),
                            (int)($item['max_qty'] ?? 1),
                            $item['sort_order'] ?? $iIndex,
                        ]);
                    }
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Carrega grupos/itens para uso no formulário do admin.
     */
    public static function loadForAdmin(int $productId): array
    {
        return self::fetchGroups($productId);
    }

    /**
     * Carrega grupos/itens para uso no front público (product/customization).
     */
    public static function loadForPublic(int $productId): array
    {
        $groups = self::fetchGroups($productId);

        foreach ($groups as &$group) {
            $items = $group['items'] ?? [];

            if (!is_array($items) || !$items) {
                $group['items'] = [];
                $group['type']  = 'extra';
                continue;
            }

            $gType = $group['type'] ?? 'extra';

            if ($gType === 'single' || $gType === 'addon') {
                $minSel = isset($group['min']) ? max(0, (int)$group['min']) : 0;
                $maxSel = isset($group['max']) ? (int)$group['max'] : ($gType === 'single' ? 1 : count($items));

                if ($gType === 'single' || $maxSel < 1) {
                    $maxSel = 1;
                }

                if ($maxSel < $minSel) {
                    $maxSel = $minSel;
                }
                $group['min'] = $minSel;
                $group['max'] = $maxSel;

                foreach ($items as &$item) {
                    $item['name']       = $item['label'];
                    $item['img']        = $item['img'] ?? ($item['image_path'] ?? null);
                    $item['sale_price'] = isset($item['sale_price']) ? (float)$item['sale_price'] : 0.0;
                    $item['selected']   = !empty($item['default']);
                }
                unset($item);

                $group['items'] = $items;
                continue;
            }

            $isSingle = true;

            foreach ($items as &$item) {
                $item['name']  = $item['label'];
                $item['delta'] = isset($item['delta']) ? (float)$item['delta'] : 0.0;
                $item['img']   = $item['img'] ?? ($item['image_path'] ?? null);

                // Normalização robusta de min/max/qty
                $min = isset($item['min_qty']) ? (int)$item['min_qty'] : 0;
                $max = isset($item['max_qty']) ? (int)$item['max_qty'] : $min;

                if ($max < $min) {
                    $max = $min;
                }

                if ($max <= 0) {
                    $max = max($min, 99);
                }

                $defaultQty = !empty($item['default']) ? (int)($item['default_qty'] ?? $min) : $min;

                if ($defaultQty < $min) {
                    $defaultQty = $min;
                }

                if ($max > 0 && $defaultQty > $max) {
                    $defaultQty = $max;
                }

                $item['min']         = $min;
                $item['max']         = $max;
                $item['qty']         = $defaultQty;
                $item['default_qty'] = $defaultQty;

                // Disponibiliza o preço de venda do ingrediente para a UI pública
                $item['sale_price'] = isset($item['sale_price']) ? (float)$item['sale_price'] : 0.0;

                if ($item['min'] !== 1 || $item['max'] !== 1) {
                    $isSingle = false;
                }
            }
            unset($item);

            $group['items'] = $items;
            // Se todos os itens do grupo são 1..1, tratamos como 'single'; caso contrário preserva tipo original
            $group['type'] = $isSingle ? 'single' : 'extra';
        }
        unset($group);

        return $groups;
    }

    /**
     * Normaliza grupos vindos do formulário do admin.
     */
    private static function normalizeGroups(array $groups, int $companyId): array
    {
        $normalized = [];
        $gSort = 0;

        $orderedGroups = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $group['_order'] = isset($group['sort_order']) ? (int)$group['sort_order'] : count($orderedGroups);
            $orderedGroups[] = $group;
        }

        usort($orderedGroups, function ($a, $b) {
            return ($a['_order'] ?? 0) <=> ($b['_order'] ?? 0);
        });

        foreach ($orderedGroups as $group) {
            if (!is_array($group)) {
                continue;
            }
            unset($group['_order']);

            $name = trim((string)($group['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $itemsRaw = $group['items'] ?? [];

            if (!is_array($itemsRaw)) {
                $itemsRaw = [];
            }

            $items = [];
            $seenIngredients = [];
            $iSort = 0;

            $modeRaw = $group['mode'] ?? 'extra';
            $mode = $modeRaw === 'choice' ? 'choice' : 'extra';
            $choiceCfg = is_array($group['choice'] ?? null) ? $group['choice'] : [];
            $choiceMin = isset($choiceCfg['min']) ? max(0, (int)$choiceCfg['min']) : 0;
            $choiceMax = isset($choiceCfg['max']) ? (int)$choiceCfg['max'] : 1;

            if ($choiceMax < 1) {
                $choiceMax = 1;
            }

            if ($choiceMax < $choiceMin) {
                $choiceMax = $choiceMin;
            }

            foreach ($itemsRaw as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $ingredientId = isset($item['ingredient_id']) ? (int)$item['ingredient_id'] : 0;

                if ($ingredientId <= 0) {
                    continue;
                }

                $ingredient = Ingredient::findForCompany($companyId, $ingredientId);

                if (!$ingredient) {
                    continue;
                }

                // Evita duplicar o mesmo ingrediente no mesmo grupo
                if (isset($seenIngredients[$ingredientId])) {
                    continue;
                }
                $seenIngredients[$ingredientId] = true;

                // Usa min/max vindos do formulário para o item (com saneamento)
                $minQty = isset($item['min_qty']) ? max(0, (int)$item['min_qty']) : 0;
                $maxQty = isset($item['max_qty']) ? (int)$item['max_qty'] : $minQty;

                if ($mode === 'choice') {
                    $minQty = 0;
                    $maxQty = 1;
                }

                if ($maxQty < $minQty) {
                    $maxQty = $minQty;
                }

                $isDefault  = !empty($item['default']) && (string)$item['default'] !== '0';
                $defaultQty = isset($item['default_qty']) ? (int)$item['default_qty'] : $minQty;

                if ($mode === 'choice') {
                    $defaultQty = $isDefault ? 1 : 0;
                }

                if ($defaultQty < $minQty) {
                    $defaultQty = $minQty;
                }

                if ($defaultQty > $maxQty) {
                    $defaultQty = $maxQty;
                }

                $items[] = [
                    'ingredient_id' => $ingredientId,
                    'label'         => $ingredient['name'],
                    'delta'         => 0.0, // ajuste aqui se a UI enviar delta
                    'default'       => $isDefault,
                    'default_qty'   => $isDefault ? $defaultQty : $minQty,
                    'min_qty'       => $minQty,
                    'max_qty'       => $maxQty,
                    'image_path'    => $ingredient['image_path'] ?? null,
                    'sort_order'    => $iSort++,
                ];
            }

            if (!$items) {
                continue;
            }

            $groupType = 'extra';
            $groupMin  = 0;
            $groupMax  = 99;

            if ($mode === 'choice') {
                if ($choiceMax <= 1) {
                    $groupType = 'single';
                    $groupMin  = min($choiceMin, 1);
                    $groupMax  = 1;
                } else {
                    $groupType = 'addon';
                    $groupMin  = min($choiceMin, $choiceMax);
                    $groupMax  = $choiceMax;
                }
            }

            $normalized[] = [
                'name'       => $name,
                'type'       => $groupType,
                'min'        => $groupMin,
                'max'        => $groupMax,
                'sort_order' => $gSort++,
                'items'      => $items,
            ];
        }

        return $normalized;
    }

    /**
     * Consulta grupos/itens no banco.
     */
    private static function fetchGroups(int $productId): array
    {
        $pdo = db();
        $sql = 'SELECT pcg.id            AS group_id,
                       pcg.name          AS group_name,
                       pcg.type          AS group_type,
                       pcg.min_qty       AS group_min,
                       pcg.max_qty       AS group_max,
                       pcg.sort_order    AS group_sort,
                       pci.id            AS item_id,
                       pci.label         AS item_label,
                       pci.delta         AS item_delta,
                       pci.is_default    AS item_default,
                       pci.default_qty   AS item_default_qty,
                       pci.min_qty       AS item_min_qty,
                       pci.max_qty       AS item_max_qty,
                       pci.sort_order    AS item_sort,
                       pci.ingredient_id AS item_ingredient_id,
                       ing.image_path    AS ingredient_image,
                       ing.sale_price    AS ingredient_sale_price
                  FROM product_custom_groups pcg
             LEFT JOIN product_custom_items  pci ON pci.group_id = pcg.id
             LEFT JOIN ingredients ing          ON ing.id = pci.ingredient_id
                 WHERE pcg.product_id = ?
              ORDER BY pcg.sort_order ASC, pcg.id ASC, pci.sort_order ASC, pci.id ASC';

        $st = $pdo->prepare($sql);
        $st->execute([$productId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }

        $groups = [];

        foreach ($rows as $row) {
            $gid = (int)$row['group_id'];

            if (!isset($groups[$gid])) {
                $groups[$gid] = [
                    'id'         => $gid,
                    'name'       => $row['group_name'],
                    'type'       => $row['group_type'] ?: 'extra',
                    'min'        => (int)$row['group_min'],
                    'max'        => (int)$row['group_max'],
                    'sort_order' => (int)$row['group_sort'],
                    'items'      => [],
                ];
            }

            if (!empty($row['item_id'])) {
                $groups[$gid]['items'][] = [
                    'id'            => (int)$row['item_id'],
                    'label'         => $row['item_label'],
                    'delta'         => (float)$row['item_delta'],
                    'default'       => (bool)$row['item_default'],
                    'default_qty'   => (int)$row['item_default_qty'],
                    'min_qty'       => (int)$row['item_min_qty'],
                    'max_qty'       => (int)$row['item_max_qty'],
                    'ingredient_id' => $row['item_ingredient_id'] ? (int)$row['item_ingredient_id'] : null,
                    'image_path'    => $row['ingredient_image'] ?? null,
                    // Disponibiliza o preço de venda do ingrediente para a UI pública
                    'sale_price'    => isset($row['ingredient_sale_price']) ? (float)$row['ingredient_sale_price'] : 0.0,
                    'sort_order'    => (int)$row['item_sort'],
                ];
            }
        }

        // Ordena itens por sort_order (defensivo)
        foreach ($groups as &$group) {
            if (isset($group['items'])) {
                usort($group['items'], function ($a, $b) {
                    return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
                });
            }
        }
        unset($group);

        return array_values($groups);
    }
}
