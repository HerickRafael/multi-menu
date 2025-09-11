<?php
require_once __DIR__ . '/../config/db.php';

class Product {
  public static function listByCompany(int $companyId, ?string $q=null): array {
    $sql = "SELECT * FROM products WHERE company_id = ? AND active = 1";
    $args = [$companyId];
    if ($q) {
      $sql .= " AND (name LIKE ? OR description LIKE ?)";
      $args[] = "%$q%"; $args[] = "%$q%";
    }
    $sql .= " ORDER BY sort_order, name";
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll();
  }

  public static function listByCategory(int $companyId, int $categoryId, ?string $q=null): array {
    $sql = "SELECT * FROM products WHERE company_id = ? AND category_id = ? AND active = 1";
    $args = [$companyId, $categoryId];
    if ($q) {
      $sql .= " AND (name LIKE ? OR description LIKE ?)";
      $args[] = "%$q%"; $args[] = "%$q%";
    }
    $sql .= " ORDER BY sort_order, name";
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll();
  }

  public static function find(int $id): ?array {
    $st = db()->prepare("SELECT * FROM products WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
  }

  public static function create(array $data): int {
    $st = db()->prepare("INSERT INTO products (company_id, category_id, name, description, price, promo_price, sku, image, active, sort_order, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
    $st->execute([
      $data['company_id'],
      $data['category_id'] ?: null,
      $data['name'],
      $data['description'] ?? null,
      $data['price'],
      $data['promo_price'] ?: null,
      $data['sku'] ?: null,
      $data['image'] ?: null,
      isset($data['active']) ? (int)$data['active'] : 1,
      (int)($data['sort_order'] ?? 0),
    ]);
    return (int)db()->lastInsertId();
  }

  public static function update(int $id, array $data): void {
    $st = db()->prepare("UPDATE products SET category_id=?, name=?, description=?, price=?, promo_price=?, sku=?, image=?, active=?, sort_order=? WHERE id=?");
    $st->execute([
      $data['category_id'] ?: null,
      $data['name'],
      $data['description'] ?? null,
      $data['price'],
      $data['promo_price'] ?: null,
      $data['sku'] ?: null,
      $data['image'] ?: null,
      isset($data['active']) ? (int)$data['active'] : 1,
      (int)($data['sort_order'] ?? 0),
      $id
    ]);
  }

  public static function delete(int $id): void {
    $st = db()->prepare("DELETE FROM products WHERE id=?");
    $st->execute([$id]);
  }

  /* ====== Novidades ====== */
  public static function novidadesByCompanyId(PDO $db, int $companyId, int $dias = 14, int $limit = 12): array {
    if ($dias <= 0) return []; // desliga novidades
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

  /* ====== Mais pedidos ====== */
  public static function maisPedidosByCompanyId(PDO $db, int $companyId, int $limit = 12): array {
    $sql = "SELECT
              p.*,
              SUM(oi.quantity) AS total_pedidos
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
}
