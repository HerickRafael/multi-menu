<?php

require_once __DIR__ . '/../config/db.php';

class DeliveryCity {
  public static function allByCompany(int $companyId): array {
    $st = db()->prepare('SELECT * FROM delivery_cities WHERE company_id = ? ORDER BY name');
    $st->execute([$companyId]);
    return $st->fetchAll() ?: [];
  }

  public static function existsByName(int $companyId, string $name): bool {
    $st = db()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
    $st->execute([$companyId, $name]);
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

  public static function delete(int $id, int $companyId): void {
    $st = db()->prepare('DELETE FROM delivery_cities WHERE id = ? AND company_id = ?');
    $st->execute([$id, $companyId]);
  }
}
