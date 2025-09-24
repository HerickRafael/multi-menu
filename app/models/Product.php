<?php
// app/models/Product.php
require_once __DIR__ . '/../config/db.php';

class Product
{
  private static function normalizePromoValue($promo, $price): ?float {
    if ($promo === null || $promo === '') {
      return null;
    }

    if (is_array($promo)) {
      $promo = reset($promo);
    }

    $promoStr = trim((string)$promo);
    if ($promoStr === '') {
      return null;
    }

    $promoStr = str_replace(' ', '', $promoStr);
    if (strpos($promoStr, ',') !== false && strpos($promoStr, '.') !== false) {
      $promoStr = str_replace('.', '', $promoStr);
    }
    $promoStr = str_replace(',', '.', $promoStr);

    if (!is_numeric($promoStr) && !is_numeric($promo)) {
      return null;
    }

    $promoVal = (float)$promoStr;
    $priceVal = (float)$price;

    if ($promoVal <= 0) {
      return null;
    }

    if ($priceVal <= 0 || $promoVal >= $priceVal) {
      return null;
    }

    return $promoVal;
  }
  /* ========================
   * LISTAGENS / BÁSICO
   * ======================== */

  public static function listByCompany(int $companyId, ?string $q = null, bool $onlyActive = true): array {
    $sql = "SELECT * FROM products WHERE company_id = ?";
    $args = [$companyId];
    if ($onlyActive) {
      $sql .= " AND active = 1";
    }
    if ($q) {
      $sql .= " AND (name LIKE ? OR description LIKE ?)";
      $args[] = "%$q%"; $args[] = "%$q%";
    }
    $sql .= " ORDER BY sort_order, name";
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function listByCategory(int $companyId, int $categoryId, ?string $q = null): array {
    $sql = "SELECT * FROM products WHERE company_id = ? AND category_id = ? AND active = 1";
    $args = [$companyId, $categoryId];
    if ($q) {
      $sql .= " AND (name LIKE ? OR description LIKE ?)";
      $args[] = "%$q%"; $args[] = "%$q%";
    }
    $sql .= " ORDER BY sort_order, name";
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function allForCompany(int $companyId): array {
    $sql = "SELECT * FROM products WHERE company_id = ? ORDER BY name";
    $st = db()->prepare($sql);
    $st->execute([$companyId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Retorna produtos simples ativos da empresa, usados para montar combos.
   */
  public static function simpleProductsForCombo(int $companyId, ?int $excludeId = null): array {
    $sql = "SELECT p.id,
                   p.name,
                   p.price,
                   p.promo_price,
                   p.allow_customize,
                   COALESCE(c.ingredient_count,0) AS ingredient_count
              FROM products p
         LEFT JOIN (
                SELECT pcg.product_id, COUNT(pci.id) AS ingredient_count
                  FROM product_custom_groups pcg
                  JOIN product_custom_items pci ON pci.group_id = pcg.id
              GROUP BY pcg.product_id
            ) c ON c.product_id = p.id
             WHERE p.company_id = :cid
               AND p.type = 'simple'
               AND p.active = 1";

    if ($excludeId) {
      $sql .= " AND p.id <> :exclude";
    }

    $sql .= " ORDER BY p.name";

    $st = db()->prepare($sql);
    $st->bindValue(':cid', $companyId, PDO::PARAM_INT);
    if ($excludeId) {
      $st->bindValue(':exclude', $excludeId, PDO::PARAM_INT);
    }
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public static function find(int $id): ?array {
    $st = db()->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  /**
   * Retorna a próxima SKU numérica disponível para a empresa.
   * Busca o menor número positivo que ainda não está em uso,
   * permitindo reutilizar gaps quando um produto é excluído.
   */
  public static function nextSkuForCompany(int $companyId): string {
    $st = db()->prepare("SELECT sku FROM products WHERE company_id = ? AND sku IS NOT NULL AND sku <> ''");
    $st->execute([$companyId]);

    $used = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
      $sku = trim((string)($row['sku'] ?? ''));
      if ($sku === '' || !ctype_digit($sku)) {
        continue;
      }

      $value = (int)$sku;
      if ($value > 0) {
        $used[] = $value;
      }
    }

    sort($used, SORT_NUMERIC);

    $next = 1;
    foreach ($used as $value) {
      if ($value === $next) {
        $next++;
        continue;
      }

      if ($value > $next) {
        break;
      }
    }

    return (string)$next;
  }

  /** Produto garantido por empresa (útil para rotas públicas /{empresa}/produto/{id}) */
  public static function findByCompanyAndId(int $companyId, int $productId): ?array {
    $sql = "SELECT * FROM products
            WHERE company_id = ? AND id = ? AND (deleted_at IS NULL OR deleted_at='0000-00-00 00:00:00')";
    $st = db()->prepare($sql);
    $st->execute([$companyId, $productId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function create(array $data): int {
    // Campos extras (se existirem na sua tabela): type, price_mode, allow_customize
    $sql = "INSERT INTO products
              (company_id, category_id, name, description, price, promo_price, sku, image,
               type, price_mode, allow_customize, active, sort_order, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())";
    $st = db()->prepare($sql);
    $st->execute([
      $data['company_id'],
      $data['category_id'] ?: null,
      $data['name'],
      $data['description'] ?? null,
      (float)$data['price'],
      self::normalizePromoValue($data['promo_price'] ?? null, $data['price'] ?? 0),
      $data['sku'] ?? null,
      $data['image'] ?? null,
      $data['type'] ?? 'simple',           // 'simple' | 'combo'
      $data['price_mode'] ?? 'fixed',      // 'fixed' | 'sum'
      !empty($data['allow_customize']) ? 1 : 0,
      isset($data['active']) ? (int)$data['active'] : 1,
      (int)($data['sort_order'] ?? 0),
    ]);
    return (int)db()->lastInsertId();
  }

  public static function update(int $id, array $data): void {
    $sql = "UPDATE products
               SET category_id=?,
                   name=?,
                   description=?,
                   price=?,
                   promo_price=?,
                   sku=?,
                   image=?,
                   type=?,
                   price_mode=?,
                   allow_customize=?,
                   active=?,
                   sort_order=?,
                   updated_at=NOW()
             WHERE id=?";
    $st = db()->prepare($sql);
    $st->execute([
      $data['category_id'] ?: null,
      $data['name'],
      $data['description'] ?? null,
      (float)$data['price'],
      self::normalizePromoValue($data['promo_price'] ?? null, $data['price'] ?? 0),
      $data['sku'] ?? null,
      $data['image'] ?? null,
      $data['type'] ?? 'simple',
      $data['price_mode'] ?? 'fixed',
      !empty($data['allow_customize']) ? 1 : 0,
      isset($data['active']) ? (int)$data['active'] : 1,
      (int)($data['sort_order'] ?? 0),
      $id
    ]);
  }

  /**
   * Normaliza grupos de combo vindos do formulário admin.
   */
  public static function normalizeComboGroups(array $groups, int $companyId): array {
    if (!$groups) {
      return [];
    }

    $allowed = [];
    foreach (self::simpleProductsForCombo($companyId) as $sp) {
      $allowed[(int)$sp['id']] = [
        'allow_customize'  => !empty($sp['allow_customize']),
        'ingredient_count' => (int)($sp['ingredient_count'] ?? 0),
      ];
    }

    $normalized = [];
    foreach ($groups as $index => $group) {
      $name = trim((string)($group['name'] ?? ''));
      if ($name === '') {
        continue;
      }

      $itemsRaw = $group['items'] ?? [];
      if (!is_array($itemsRaw) || !$itemsRaw) {
        continue;
      }

      $min = isset($group['min']) ? max(0, (int)$group['min']) : 0;
      $max = isset($group['max']) ? (int)$group['max'] : 1;
      if ($max < $min) {
        $max = $min;
      }
      if ($max <= 0) {
        $max = 1;
      }

      $items = [];
      foreach ($itemsRaw as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        if ($productId <= 0 || !isset($allowed[$productId])) {
          continue;
        }

        $delta = isset($item['delta']) ? (float)$item['delta'] : 0.0;
        $isDefault = !empty($item['default']) ? 1 : 0;

        $eligible = $allowed[$productId]['allow_customize'] && $allowed[$productId]['ingredient_count'] >= 3;
        $customizable = $eligible && !empty($item['customizable']) ? 1 : 0;

        $items[] = [
          'product_id'   => $productId,
          'delta'        => $delta,
          'default'      => $isDefault,
          'customizable' => $customizable,
        ];
      }

      if (!$items) {
        continue;
      }

      $normalized[] = [
        'name'       => $name,
        'type'       => $max > 1 ? 'addon' : 'single',
        'min'        => $min,
        'max'        => $max,
        'sort_order' => isset($group['sort_order']) ? (int)$group['sort_order'] : (int)$index,
        'items'      => $items,
      ];
    }

    usort($normalized, static function ($a, $b) {
      return ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
    });

    return $normalized;
  }

  /**
   * Ajusta os grupos salvos para o formato usado pelo formulário admin.
   */
  public static function loadComboGroupsForAdmin(int $productId): array {
    $raw = self::getComboGroupsWithItems($productId);
    if (!$raw) {
      return [];
    }

    $result = [];
    foreach ($raw as $group) {
      $items = [];
      foreach (($group['items'] ?? []) as $item) {
        $items[] = [
          'product_id'   => (int)($item['simple_id'] ?? $item['product_id'] ?? 0),
          'delta'        => isset($item['delta']) ? (float)$item['delta'] : 0.0,
          'default'      => !empty($item['is_default'] ?? $item['default']),
          'customizable' => !empty($item['is_customizable'] ?? $item['customizable']),
        ];
      }

      $result[] = [
        'id'         => (int)($group['id'] ?? 0),
        'name'       => $group['name'] ?? '',
        'type'       => $group['type'] ?? 'single',
        'min'        => (int)($group['min'] ?? 0),
        'max'        => (int)($group['max'] ?? 1),
        'sort_order' => (int)($group['sort'] ?? 0),
        'items'      => $items,
      ];
    }

    return $result;
  }

  public static function delete(int $id): void {
    // Se preferir soft delete, troque por update de deleted_at.
    $st = db()->prepare("DELETE FROM products WHERE id=?");
    $st->execute([$id]);
  }

  /* ========================
   * SUGESTÕES / VITRINES
   * ======================== */

  public static function novidadesByCompanyId(PDO $db, int $companyId, int $dias = 14, int $limit = 12): array {
    if ($dias <= 0) return [];
    $sql = "SELECT p.*
              FROM products p
             WHERE p.company_id = :cid
               AND p.active = 1
               AND p.created_at >= (NOW() - INTERVAL :dias DAY)
          ORDER BY p.created_at DESC
             LIMIT :limit";
    $st = $db->prepare($sql);
    $st->bindValue(':cid',  $companyId, PDO::PARAM_INT);
    $st->bindValue(':dias', $dias,      PDO::PARAM_INT);
    $st->bindValue(':limit',$limit,     PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function maisPedidosByCompanyId(PDO $db, int $companyId, int $limit = 12): array {
    $sql = "SELECT p.*, SUM(oi.quantity) AS total_pedidos
              FROM order_items oi
              JOIN orders   o ON o.id = oi.order_id
              JOIN products p ON p.id = oi.product_id
             WHERE o.company_id = :cid
               AND o.status IN ('paid','completed')
          GROUP BY p.id
            HAVING total_pedidos > 0
          ORDER BY total_pedidos DESC
             LIMIT :limit";
    $st = $db->prepare($sql);
    $st->bindValue(':cid',   $companyId, PDO::PARAM_INT);
    $st->bindValue(':limit', $limit,     PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /* ========================
   * COMBO: GRUPOS + ITENS
   * ======================== */

  /**
   * Lê grupos de combo + itens (com dados do produto simples).
   * Estrutura:
   * [
   *   [
   *     'id','name','type','min','max',
   *     'items' => [
   *        ['simple_id','name','image','base_price','delta','is_default']
   *     ]
   *   ], ...
   * ]
   */
  public static function getComboGroupsWithItems(int $productId): array {
    $pdo = db();

    // grupos
    $gq = $pdo->prepare("
      SELECT id, name, type,
             COALESCE(min_qty,0) AS min,
             COALESCE(max_qty,1) AS max,
             COALESCE(sort,0)    AS sort
        FROM combo_groups
       WHERE product_id = ?
    ORDER BY sort ASC, id ASC
    ");
    $gq->execute([$productId]);
    $groups = $gq->fetchAll(PDO::FETCH_ASSOC);
    if (!$groups) return [];

    // itens de 1 grupo
    $iq = $pdo->prepare("
      SELECT gi.id,
             gi.group_id,
             gi.simple_product_id AS simple_id,
             COALESCE(gi.delta_price,0) AS delta,
             COALESCE(gi.is_default,0)  AS is_default,
             COALESCE(gi.is_customizable,0) AS is_customizable,
             sp.name,
             sp.image,
             sp.price AS base_price,
             sp.allow_customize,
             COALESCE(c.ingredient_count,0) AS ingredient_count
        FROM combo_group_items gi
  INNER JOIN products sp ON sp.id = gi.simple_product_id
   LEFT JOIN (
          SELECT pcg.product_id, COUNT(pci.id) AS ingredient_count
            FROM product_custom_groups pcg
            JOIN product_custom_items pci ON pci.group_id = pcg.id
        GROUP BY pcg.product_id
        ) c ON c.product_id = sp.id
       WHERE gi.group_id = ?
    ORDER BY gi.sort ASC, gi.id ASC
    ");

    foreach ($groups as &$g) {
      $iq->execute([$g['id']]);
      $items = $iq->fetchAll(PDO::FETCH_ASSOC) ?: [];
      foreach ($items as &$item) {
        $item['default'] = !empty($item['is_default']) ? 1 : 0;
        $item['customizable'] = !empty($item['is_customizable']) ? 1 : 0;
        $item['id'] = isset($item['simple_id']) ? (int)$item['simple_id'] : (int)($item['id'] ?? 0);
      }
      unset($item);
      $g['items'] = $items;
    }
    unset($g);

    return $groups;
  }

  /**
   * Salva grupos de opções (combo) vindos do formulário Admin.
   * Espera a estrutura semelhante ao seu form:
   * $groups = [
   *   [ 'name'=>'Escolha o produto', 'type'=>'single', 'min'=>1, 'max'=>1,
   *     'items'=>[
   *        ['product_id'=>123, 'delta'=>0.00, 'default'=>true],
   *        ...
   *     ]
   *   ],
   *   ...
   * ]
   * Estratégia: apaga todos e re-insere (mais simples e confiável).
   */
  public static function saveComboGroupsAndItems(int $productId, array $groups): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
      // apaga existentes
      $pdo->prepare("DELETE gi FROM combo_group_items gi
                      INNER JOIN combo_groups g ON g.id = gi.group_id
                      WHERE g.product_id = ?")->execute([$productId]);
      $pdo->prepare("DELETE FROM combo_groups WHERE product_id = ?")->execute([$productId]);

      if (!empty($groups)) {
        $insG = $pdo->prepare("
          INSERT INTO combo_groups (product_id, name, type, min_qty, max_qty, sort, created_at)
          VALUES (?,?,?,?,?,?,NOW())
        ");
        $insI = $pdo->prepare("
          INSERT INTO combo_group_items (group_id, simple_product_id, delta_price, is_default, is_customizable, sort, created_at)
          VALUES (?,?,?,?,?,?,NOW())
        ");

        $gSort = 0;
        foreach ($groups as $g) {
          $name = trim((string)($g['name'] ?? ''));
          if ($name === '') continue;

          $type = $g['type'] ?? 'single';
          $min  = (int)($g['min'] ?? 0);
          $max  = (int)($g['max'] ?? 1);

          $insG->execute([$productId, $name, $type, $min, $max, $gSort++]);
          $groupId = (int)$pdo->lastInsertId();

          $items = $g['items'] ?? [];
          $iSort = 0;
          foreach ($items as $it) {
            $spId   = (int)($it['product_id'] ?? 0);
            if ($spId <= 0) continue;
            $delta  = (float)($it['delta'] ?? 0);
            $isDef  = !empty($it['default']) ? 1 : 0;
            $isCust = !empty($it['customizable']) ? 1 : 0;
            $insI->execute([$groupId, $spId, $delta, $isDef, $isCust, $iSort++]);
          }
        }
      }

      $pdo->commit();
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  /* ========================
   * HELPERS DE CÁLCULO (opcional)
   * ======================== */

  /**
   * Recalcula o total de um produto combo a partir das seleções do cliente.
   * $product: array do produto (deve conter price, price_mode)
   * $selected: array no formato combo_group[group_id] => (id simples OU array de ids)
   * Retorna ['base'=>..., 'sum_delta'=>..., 'total'=>...]
   */
  public static function calculateComboTotal(array $product, array $selected): array {
    $base = (float)($product['promo_price'] ?? 0) > 0
      && (float)$product['promo_price'] < (float)$product['price']
        ? (float)$product['promo_price']
        : (float)$product['price'];

    $priceMode = $product['price_mode'] ?? 'fixed'; // 'fixed' | 'sum'
    $sumDelta = 0.0;

    if (!empty($selected)) {
      // pega todos os deltas das seleções
      $pdo = db();
      $pairs = [];
      foreach ($selected as $gid => $val) {
        if (is_array($val)) {
          foreach ($val as $sid) $pairs[] = [(int)$gid, (int)$sid];
        } else {
          $pairs[] = [(int)$gid, (int)$val];
        }
      }
      if ($pairs) {
        // consulta por lotes
        $place = [];
        $args  = [];
        foreach ($pairs as [$gid, $sid]) {
          $place[] = "(group_id = ? AND simple_product_id = ?)";
          $args[] = $gid;
          $args[] = $sid;
        }
        $sql = "SELECT SUM(COALESCE(delta_price,0)) AS s FROM combo_group_items WHERE " . implode(' OR ', $place);
        $st  = $pdo->prepare($sql);
        $st->execute($args);
        $sumDelta = (float)($st->fetchColumn() ?: 0);
      }
    }

    $total = $priceMode === 'sum' ? ($base + $sumDelta) : $base;

    return [
      'base'      => $base,
      'sum_delta' => $sumDelta,
      'total'     => $total,
    ];
  }
}
