<?php
class Order
{
  public static function listByCompany(PDO $db, int $companyId, ?string $status=null, int $limit=50, int $offset=0): array {
    $sql = "SELECT * FROM orders WHERE company_id = :cid";
    $args = [':cid' => $companyId];
    if ($status) { $sql .= " AND status = :st"; $args[':st'] = $status; }
    $sql .= " ORDER BY id DESC LIMIT :lim OFFSET :off";
    $st = $db->prepare($sql);
    foreach ($args as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':lim', $limit, PDO::PARAM_INT);
    $st->bindValue(':off', $offset, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function create(PDO $db, array $data): int {
    $sql = "INSERT INTO orders (company_id, customer_name, customer_phone, subtotal, delivery_fee, discount, total, status, notes, created_at)
            VALUES (:cid,:name,:phone,:sub,:fee,:disc,:tot,:st,:notes,NOW())";
    $st = $db->prepare($sql);
    $st->execute([
      ':cid'   => $data['company_id'],
      ':name'  => $data['customer_name'],
      ':phone' => $data['customer_phone'],
      ':sub'   => $data['subtotal'],
      ':fee'   => $data['delivery_fee'],
      ':disc'  => $data['discount'],
      ':tot'   => $data['total'],
      ':st'    => $data['status'] ?? 'pending',
      ':notes' => $data['notes'] ?? null,
    ]);
    return (int)$db->lastInsertId();
  }

  public static function addItem(PDO $db, int $orderId, array $it): void {
    $sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total)
            VALUES (:oid,:pid,:q,:unit,:line)";
    $st = $db->prepare($sql);
    $st->execute([
      ':oid'  => $orderId,
      ':pid'  => $it['product_id'],
      ':q'    => $it['quantity'],
      ':unit' => $it['unit_price'],
      ':line' => $it['line_total'],
    ]);
  }

  public static function countByCompany(PDO $db, int $companyId): int {
    $st = $db->prepare('SELECT COUNT(*) FROM orders WHERE company_id = :cid');
    $st->execute([':cid' => $companyId]);
    return (int)$st->fetchColumn();
  }

  public static function findWithItems(PDO $db, int $orderId, int $companyId): ?array {
    $st = $db->prepare("SELECT * FROM orders WHERE id = ? AND company_id = ?");
    $st->execute([$orderId, $companyId]);
    $o = $st->fetch(PDO::FETCH_ASSOC);
    if (!$o) return null;
    $it = $db->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi
                        LEFT JOIN products p ON p.id = oi.product_id
                        WHERE oi.order_id = ? ORDER BY oi.id");
    $it->execute([$orderId]);
    $o['items'] = $it->fetchAll(PDO::FETCH_ASSOC);
    return $o;
  }

  public static function updateStatus(PDO $db, int $orderId, int $companyId, string $status): bool {
    $allowed = ['pending','paid','completed','canceled'];
    if (!in_array($status, $allowed, true)) return false;
    $st = $db->prepare("UPDATE orders SET status=? WHERE id=? AND company_id=?");
    return $st->execute([$status, $orderId, $companyId]);
  }
}
