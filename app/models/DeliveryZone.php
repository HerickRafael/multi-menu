<?php

require_once __DIR__ . '/../config/db.php';

class DeliveryZone {
  public static function allByCompany(int $companyId): array {
    $st = db()->prepare("SELECT * FROM delivery_zones WHERE company_id = ? ORDER BY city, neighborhood");
    $st->execute([$companyId]);
    return $st->fetchAll() ?: [];
  }

  public static function create(array $data): int {
    $st = db()->prepare("INSERT INTO delivery_zones (company_id, city, neighborhood, fee) VALUES (?, ?, ?, ?)");
    $st->execute([
      (int)$data['company_id'],
      $data['city'],
      $data['neighborhood'],
      $data['fee'],
    ]);
    return (int)db()->lastInsertId();
  }

  public static function delete(int $id, int $companyId): void {
    $st = db()->prepare("DELETE FROM delivery_zones WHERE id = ? AND company_id = ?");
    $st->execute([$id, $companyId]);
  }
}
