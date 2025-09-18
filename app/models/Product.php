<?php
// app/models/Product.php
require_once __DIR__ . '/../config/db.php';

class Product
{
  /* ========================
   * LISTAGENS / BÁSICO
   * ======================== */

  public static function listByCompany(int $companyId, ?string $q = null): array {
    $sql = "SELECT * FROM products WHERE company_id = ? AND active = 1";
    $args = [$companyId];
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

  public static function find(int $id): ?array {
    $st = db()->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
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
      $data['promo_price'] !== '' ? (float)$data['promo_price'] : null,
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
      $data['promo_price'] !== '' ? (float)$data['promo_price'] : null,
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
   * INGREDIENTES
   * ======================== */

  /**
   * Determina a tabela real usada para persistir ingredientes.
   * Aceita os nomes legacy `ingredients` e o novo `product_ingredients`.
   */
  private static function ingredientTable(): ?array {
    static $cacheInitialized = false;
    static $cache = null;
    if ($cacheInitialized) return $cache;

    $pdo = db();
    $candidates = ['product_ingredients', 'ingredients'];

    foreach ($candidates as $name) {
      try {
        $pdo->query("SELECT 1 FROM {$name} LIMIT 0");

        $hasSort = false;
        try {
          $col = $pdo->query("SHOW COLUMNS FROM {$name} LIKE 'sort'");
          $hasSort = (bool)$col->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $colErr) {
          // Alguns bancos (SQLite, PostgreSQL) não suportam SHOW COLUMNS.
          // Faz uma checagem alternativa usando PRAGMA/DESCRIBE simples.
          try {
            $pragma = $pdo->query("PRAGMA table_info({$name})");
            if ($pragma) {
              while ($row = $pragma->fetch(PDO::FETCH_ASSOC)) {
                if (isset($row['name']) && $row['name'] === 'sort') { $hasSort = true; break; }
                if (isset($row['Field']) && $row['Field'] === 'sort') { $hasSort = true; break; }
              }
            }
          } catch (PDOException $pragmaErr) {
            // Ignora – se não conseguir verificar, assume que não tem.
          }
        }

        $cacheInitialized = true;
        $cache = ['table' => $name, 'has_sort' => $hasSort];
        return $cache;
      } catch (PDOException $e) {
        if (($e->errorInfo[0] ?? '') === '42S02') {
          continue; // tabela não existe, tenta a próxima
        }
        throw $e; // outro erro (permissão, etc.) deve emergir
      }
    }

    $cacheInitialized = true;
    $cache = null;
    return null;
  }

  /**
   * Lê a lista simples de ingredientes do produto (tabela dinâmica)
   * e retorna cada item como ['name' => <string>].
   */
  public static function getIngredients(int $productId): array {
    $info = self::ingredientTable();
    if ($info === null) {
      return [];
    }
    $table = $info['table'];
    $order = $info['has_sort'] ? 'sort ASC, id ASC' : 'id ASC';
    $sql = "SELECT name
              FROM {$table}
             WHERE product_id = ?
          ORDER BY {$order}";
    $st = db()->prepare($sql);
    $st->execute([$productId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /**
   * Salva ingredientes do formulário Admin.
   * Recebe array de strings (names). Substitui os existentes.
   */
  public static function saveIngredients(int $productId, array $ingredients): void {
    $pdo = db();
    $info = self::ingredientTable();
    if ($info === null) {
      return; // nenhum local para persistir, ignora silenciosamente
    }
    $table = $info['table'];
    $hasSort = $info['has_sort'];
    $pdo->beginTransaction();
    try {
      $pdo->prepare("DELETE FROM {$table} WHERE product_id=?")->execute([$productId]);

      if (!empty($ingredients)) {
        if ($hasSort) {
          $ins = $pdo->prepare("INSERT INTO {$table} (product_id, name, sort) VALUES (?,?,?)");
        } else {
          $ins = $pdo->prepare("INSERT INTO {$table} (product_id, name) VALUES (?,?)");
        }
        $sort = 0;
        foreach ($ingredients as $name) {
          $name = trim((string)$name);
          if ($name === '') continue;
          if ($hasSort) {
            $ins->execute([$productId, $name, $sort++]);
          } else {
            $ins->execute([$productId, $name]);
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
             sp.name,
             sp.image,
             sp.price AS base_price
        FROM combo_group_items gi
  INNER JOIN products sp ON sp.id = gi.simple_product_id
       WHERE gi.group_id = ?
    ORDER BY gi.sort ASC, gi.id ASC
    ");

    foreach ($groups as &$g) {
      $iq->execute([$g['id']]);
      $g['items'] = $iq->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
          INSERT INTO combo_group_items (group_id, simple_product_id, delta_price, is_default, sort, created_at)
          VALUES (?,?,?,?,?,NOW())
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
            $insI->execute([$groupId, $spId, $delta, $isDef, $iSort++]);
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
