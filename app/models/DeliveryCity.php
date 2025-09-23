<?php

require_once __DIR__ . '/../config/db.php';

class DeliveryCity {
  public static function allByCompany(int $companyId, ?string $search = null): array {
    $sql = 'SELECT * FROM delivery_cities WHERE company_id = ?';
    $params = [$companyId];

    if ($search !== null && $search !== '') {
      $sql .= ' AND LOWER(name) LIKE LOWER(?)';
      $params[] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY name';

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
  }

  public static function existsByName(int $companyId, string $name): bool {
    $st = db()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
    $st->execute([$companyId, $name]);
    return (bool)$st->fetchColumn();
  }

  public static function existsByNameExcept(int $companyId, string $name, int $ignoreId): bool {
    $st = db()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
    $st->execute([$companyId, $name, $ignoreId]);
    return (bool)$st->fetchColumn();
  }

  public static function findForCompany(int $id, int $companyId): ?array {
    $st = db()->prepare('SELECT * FROM delivery_cities WHERE id = ? AND company_id = ?');
    $st->execute([$id, $companyId]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function create(array $data): int {
    $st = db()->prepare('INSERT INTO delivery_cities (company_id, name) VALUES (?, ?)');
    $st->execute([(int)$data['company_id'], $data['name']]);
    return (int)db()->lastInsertId();
  }

  public static function update(int $id, int $companyId, string $name): void {
    $st = db()->prepare('UPDATE delivery_cities SET name = ? WHERE id = ? AND company_id = ?');
    $st->execute([$name, $id, $companyId]);
  }

  public static function delete(int $id, int $companyId): void {
    $st = db()->prepare('DELETE FROM delivery_cities WHERE id = ? AND company_id = ?');
    $st->execute([$id, $companyId]);
  }
}
