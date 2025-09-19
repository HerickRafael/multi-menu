<?php
// app/models/ProductCustomization.php
require_once __DIR__ . '/../config/db.php';

class ProductCustomization
{
    /**
     * Normaliza os dados vindos do formulário do admin.
     * Retorna um array no formato ['enabled'=>bool,'groups'=>[...]].
     */
    public static function sanitizePayload(array $payload): array
    {
        $enabled = !empty($payload['enabled']);
        $groups  = [];

        if (!empty($payload['groups']) && is_array($payload['groups'])) {
            $groups = self::normalizeGroups($payload['groups']);
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
            $pdo->prepare("DELETE pci FROM product_custom_items pci
                              INNER JOIN product_custom_groups pcg ON pcg.id = pci.group_id
                             WHERE pcg.product_id = ?")
                ->execute([$productId]);

            $pdo->prepare("DELETE FROM product_custom_groups WHERE product_id = ?")
                ->execute([$productId]);

            if ($groups) {
                $insGroup = $pdo->prepare(
                    "INSERT INTO product_custom_groups (product_id, name, type, min_qty, max_qty, sort_order)
                     VALUES (?,?,?,?,?,?)"
                );
                $insItem = $pdo->prepare(
                    "INSERT INTO product_custom_items (group_id, label, delta, is_default, sort_order)
                     VALUES (?,?,?,?,?)"
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
                            $item['label'],
                            isset($item['delta']) ? (float)$item['delta'] : 0.00,
                            !empty($item['default']) ? 1 : 0,
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
            foreach ($group['items'] as &$item) {
                $item['name'] = $item['label'];
                if (!array_key_exists('delta', $item)) {
                    $item['delta'] = 0.0;
                }
                $item['img'] = $item['img'] ?? null;
            }
            unset($item);
        }
        unset($group);
        return $groups;
    }

    /**
     * Normaliza grupos vindos do formulário do admin.
     */
    private static function normalizeGroups(array $groups): array
    {
        $normalized = [];
        $gSort = 0;
        foreach ($groups as $group) {
            if (!is_array($group)) continue;

            $name = trim((string)($group['name'] ?? ''));
            if ($name === '') continue;

            $min = isset($group['min']) ? max(0, (int)$group['min']) : 0;
            $max = isset($group['max']) ? max(0, (int)$group['max']) : 99;
            if ($max < $min) {
                $max = $min;
            }

            $itemsRaw = $group['items'] ?? [];
            if (!is_array($itemsRaw)) {
                $itemsRaw = [];
            }

            $items = [];
            $iSort = 0;
            foreach ($itemsRaw as $item) {
                if (!is_array($item)) continue;
                $label = trim((string)($item['label'] ?? ''));
                if ($label === '') continue;

                $items[] = [
                    'label'      => $label,
                    'delta'      => isset($item['delta']) ? (float)$item['delta'] : 0.0,
                    'default'    => !empty($item['default']),
                    'sort_order' => $iSort++,
                ];
            }

            if (!$items) continue;

            $type = ($min === 1 && $max === 1) ? 'single' : 'extra';
            if ($type === 'single') {
                $defaultApplied = false;
                foreach ($items as &$it) {
                    if ($defaultApplied) {
                        $it['default'] = false;
                        continue;
                    }
                    if (!empty($it['default'])) {
                        $defaultApplied = true;
                    }
                }
                unset($it);
                if (!$defaultApplied && isset($items[0])) {
                    $items[0]['default'] = true;
                }
            }

            $normalized[] = [
                'name'       => $name,
                'type'       => $type,
                'min'        => $min,
                'max'        => $max,
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
        $sql = "SELECT pcg.id          AS group_id,
                       pcg.name        AS group_name,
                       pcg.type        AS group_type,
                       pcg.min_qty     AS group_min,
                       pcg.max_qty     AS group_max,
                       pcg.sort_order  AS group_sort,
                       pci.id          AS item_id,
                       pci.label       AS item_label,
                       pci.delta       AS item_delta,
                       pci.is_default  AS item_default,
                       pci.sort_order  AS item_sort
                  FROM product_custom_groups pcg
             LEFT JOIN product_custom_items  pci ON pci.group_id = pcg.id
                 WHERE pcg.product_id = ?
              ORDER BY pcg.sort_order ASC, pcg.id ASC, pci.sort_order ASC, pci.id ASC";

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
                    'id'         => (int)$row['item_id'],
                    'label'      => $row['item_label'],
                    'delta'      => (float)$row['item_delta'],
                    'default'    => (bool)$row['item_default'],
                    'sort_order' => (int)$row['item_sort'],
                ];
            }
        }

        return array_values($groups);
    }
}
