<?php

require_once __DIR__ . '/../config/db.php';

class DeliveryZone {
  public static function allByCompany(int $companyId, string $search = ''): array {
    $sql = 'SELECT dz.*, dc.name AS city_name'
         . ' FROM delivery_zones dz'
         . ' JOIN delivery_cities dc ON dc.id = dz.city_id'
         . ' WHERE dz.company_id = ?';
    $params = [$companyId];

    if ($search !== '') {
      $sql .= ' AND (dz.neighborhood LIKE ? OR dc.name LIKE ?)';
      $params[] = '%' . $search . '%';
      $params[] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY dc.name, dz.neighborhood';

    $st = db()->prepare($sql);
    $st->execute($params);
    return $st->fetchAll() ?: [];
  }

  public static function create(array $data): int {
    $st = db()->prepare('INSERT INTO delivery_zones (company_id, city_id, neighborhood, fee) VALUES (?, ?, ?, ?)');
    $st->execute([
      (int)$data['company_id'],
      (int)$data['city_id'],
      $data['neighborhood'],
      $data['fee'],
    ]);
    return (int)db()->lastInsertId();
  }

  public static function findForCompany(int $id, int $companyId): ?array {
    $sql = 'SELECT dz.*, dc.name AS city_name'
         . ' FROM delivery_zones dz'
         . ' JOIN delivery_cities dc ON dc.id = dz.city_id'
         . ' WHERE dz.id = ? AND dz.company_id = ?';
    $st = db()->prepare($sql);
    $st->execute([$id, $companyId]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public static function update(int $id, int $companyId, array $data): void {
    $st = db()->prepare('UPDATE delivery_zones SET city_id = ?, neighborhood = ?, fee = ? WHERE id = ? AND company_id = ?');
    $st->execute([
      (int)$data['city_id'],
      $data['neighborhood'],
      $data['fee'],
      $id,
      $companyId,
    ]);
  }

  public static function existsForCity(int $companyId, int $cityId, string $neighborhood, ?int $excludeId = null): bool {
    $sql = 'SELECT 1 FROM delivery_zones WHERE company_id = ? AND city_id = ? AND LOWER(neighborhood) = LOWER(?)';
    $params = [$companyId, $cityId, $neighborhood];

    if ($excludeId !== null) {
      $sql .= ' AND id <> ?';
      $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $st = db()->prepare($sql);
    $st->execute($params);
    return (bool)$st->fetchColumn();
  }

  public static function adjustAll(int $companyId, float $amount, string $operation): void {
    if ($amount <= 0) {
      return;
    }

    if ($operation === 'decrease') {
      $st = db()->prepare('UPDATE delivery_zones SET fee = GREATEST(fee - ?, 0) WHERE company_id = ?');
      $st->execute([$amount, $companyId]);
    } else {
      $st = db()->prepare('UPDATE delivery_zones SET fee = fee + ? WHERE company_id = ?');
      $st->execute([$amount, $companyId]);
    }
  }

  public static function delete(int $id, int $companyId): void {
    $st = db()->prepare('DELETE FROM delivery_zones WHERE id = ? AND company_id = ?');
    $st->execute([$id, $companyId]);
  }
}
