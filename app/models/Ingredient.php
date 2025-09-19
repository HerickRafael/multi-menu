<?php
require_once __DIR__ . '/../config/db.php';

class Ingredient
{
  public static function listByCompany(int $companyId, ?int $productId = null, ?string $q = null): array
  {
    $sql = "SELECT i.*, p.name AS product_name
              FROM ingredients i
              INNER JOIN products p ON p.id = i.product_id
             WHERE p.company_id = ?";
    $args = [$companyId];

    if ($productId) {
      $sql .= " AND i.product_id = ?";
      $args[] = $productId;
    }

    if ($q) {
      $sql .= " AND i.name LIKE ?";
      $args[] = '%' . $q . '%';
    }

    $sql .= " ORDER BY p.name, i.name";

    $st = db()->prepare($sql);
    $st->execute($args);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function listRecentByCompany(int $companyId, int $limit = 8): array
  {
    $sql = "SELECT i.*, p.name AS product_name
              FROM ingredients i
              INNER JOIN products p ON p.id = i.product_id
             WHERE p.company_id = ?
          ORDER BY i.id DESC
             LIMIT ?";
    $st = db()->prepare($sql);
    $st->bindValue(1, $companyId, PDO::PARAM_INT);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function find(int $id): ?array
  {
    $st = db()->prepare("SELECT * FROM ingredients WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function findForCompany(int $companyId, int $ingredientId): ?array
  {
    $sql = "SELECT i.*, p.name AS product_name, p.company_id
              FROM ingredients i
              INNER JOIN products p ON p.id = i.product_id
             WHERE i.id = ? AND p.company_id = ?";
    $st = db()->prepare($sql);
    $st->execute([$ingredientId, $companyId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $st = db()->prepare("INSERT INTO ingredients (product_id, name) VALUES (?, ?)");
    $st->execute([$data['product_id'], $data['name']]);
    return (int)db()->lastInsertId();
  }

  public static function update(int $id, array $data): void
  {
    $st = db()->prepare("UPDATE ingredients SET product_id = ?, name = ? WHERE id = ?");
    $st->execute([$data['product_id'], $data['name'], $id]);
  }

  public static function delete(int $id): void
  {
    $st = db()->prepare("DELETE FROM ingredients WHERE id = ?");
    $st->execute([$id]);
  }

  public static function countByCompany(int $companyId): int
  {
    $sql = "SELECT COUNT(*) AS total
              FROM ingredients i
              INNER JOIN products p ON p.id = i.product_id
             WHERE p.company_id = ?";
    $st = db()->prepare($sql);
    $st->execute([$companyId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($row['total'] ?? 0);
  }
}
