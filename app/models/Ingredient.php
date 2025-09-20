<?php

require_once __DIR__ . '/../config/db.php';

class Ingredient
{
  public static function listByCompany(int $companyId, ?int $productId = null, ?string $q = null): array
  {
    $pdo = db();

    $sql = "SELECT i.*,
                   GROUP_CONCAT(DISTINCT p.name ORDER BY p.name SEPARATOR '||') AS product_names
              FROM ingredients i
         LEFT JOIN product_custom_items  pci ON pci.ingredient_id = i.id
         LEFT JOIN product_custom_groups pcg ON pcg.id = pci.group_id
         LEFT JOIN products p ON p.id = pcg.product_id AND p.company_id = i.company_id
             WHERE i.company_id = :company";

    $params = ['company' => $companyId];

    if ($productId) {
      $sql .= " AND EXISTS (
                  SELECT 1
                    FROM product_custom_items pci2
                    JOIN product_custom_groups pcg2 ON pcg2.id = pci2.group_id
                   WHERE pci2.ingredient_id = i.id
                     AND pcg2.product_id = :productId
                )";
      $params['productId'] = $productId;
    }

    if ($q !== null && $q !== '') {
      $sql .= " AND i.name LIKE :q";
      $params['q'] = '%' . $q . '%';
    }

    $sql .= " GROUP BY i.id
              ORDER BY i.name";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public static function existsByName(int $companyId, string $name, ?int $ignoreId = null): bool
  {
    $pdo = db();
    $base = 'SELECT id FROM ingredients WHERE company_id = ? AND LOWER(name) = LOWER(?)';
    $params = [$companyId, $name];

    if ($ignoreId) {
      $base .= ' AND id <> ?';
      $params[] = $ignoreId;
    }

    $st = $pdo->prepare($base . ' LIMIT 1');
    $st->execute($params);
    return (bool)$st->fetchColumn();
  }

  public static function listRecentByCompany(int $companyId, int $limit = 8): array
  {
    $pdo = db();
    $sql = "SELECT * FROM ingredients WHERE company_id = ? ORDER BY updated_at DESC LIMIT ?";
    $st = $pdo->prepare($sql);
    $st->bindValue(1, $companyId, PDO::PARAM_INT);
    $st->bindValue(2, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public static function countByCompany(int $companyId): int
  {
    $pdo = db();
    $st = $pdo->prepare('SELECT COUNT(*) AS total FROM ingredients WHERE company_id = ?');
    $st->execute([$companyId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return (int)($row['total'] ?? 0);
  }

  public static function allForCompany(int $companyId): array
  {
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM ingredients WHERE company_id = ? ORDER BY name');
    $st->execute([$companyId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  public static function find(int $id): ?array
  {
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM ingredients WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function findForCompany(int $companyId, int $ingredientId): ?array
  {
    $pdo = db();
    $st = $pdo->prepare('SELECT * FROM ingredients WHERE id = ? AND company_id = ?');
    $st->execute([$ingredientId, $companyId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
  }

  public static function create(array $data): int
  {
    $pdo = db();
    $st = $pdo->prepare(
      'INSERT INTO ingredients (company_id, name, cost, sale_price, unit, unit_value, min_qty, max_qty, image_path)
       VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $st->execute([
      $data['company_id'],
      $data['name'],
      $data['cost'],
      $data['sale_price'],
      $data['unit'],
      $data['unit_value'],
      $data['min_qty'] ?? 0,
      $data['max_qty'] ?? 1,
      $data['image_path'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
  }

  public static function update(int $id, array $data): void
  {
    $pdo = db();
    $st = $pdo->prepare(
      'UPDATE ingredients
          SET name = ?, cost = ?, sale_price = ?, unit = ?, unit_value = ?, min_qty = ?, max_qty = ?, image_path = ?, updated_at = NOW()
        WHERE id = ?'
    );
    $st->execute([
      $data['name'],
      $data['cost'],
      $data['sale_price'],
      $data['unit'],
      $data['unit_value'],
      $data['min_qty'] ?? 0,
      $data['max_qty'] ?? 1,
      $data['image_path'] ?? null,
      $id,
    ]);
  }

  public static function delete(int $companyId, int $ingredientId): void
  {
    $pdo = db();
    $pdo->beginTransaction();
    try {
      // Remove vínculos primeiro
      $pdo->prepare('DELETE pci FROM product_custom_items pci WHERE pci.ingredient_id = ?')
          ->execute([$ingredientId]);
      // Depois remove o ingrediente
      $pdo->prepare('DELETE FROM ingredients WHERE id = ? AND company_id = ?')
          ->execute([$ingredientId, $companyId]);

      $pdo->commit();
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  public static function assignedProducts(int $ingredientId): array
  {
    $pdo = db();
    $sql = "SELECT DISTINCT p.id, p.name
              FROM product_custom_items pci
              JOIN product_custom_groups pcg ON pcg.id = pci.group_id
              JOIN products p ON p.id = pcg.product_id
              JOIN ingredients i ON i.id = pci.ingredient_id
             WHERE pci.ingredient_id = ?
               AND p.company_id = i.company_id
          ORDER BY p.name";
    $st = $pdo->prepare($sql);
    $st->execute([$ingredientId]);
    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
}
