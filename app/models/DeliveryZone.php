<?php

require_once __DIR__ . '/../config/db.php';

class DeliveryZone
{
  /** Lista zonas da empresa com nome da cidade */
  public static function allByCompany(int $companyId): array
  {
    $st = db()->prepare(
      'SELECT dz.*, dc.name AS city_name
         FROM delivery_zones dz
         JOIN delivery_cities dc ON dc.id = dz.city_id
        WHERE dz.company_id = ?
        ORDER BY dc.name, dz.neighborhood'
    );
    $st->execute([$companyId]);
    return $st->fetchAll() ?: [];
  }

  /** Cria uma zona (bairro) vinculada à cidade */
  public static function create(array $data): int
  {
    $st = db()->prepare(
      'INSERT INTO delivery_zones (company_id, city_id, neighborhood, fee)
       VALUES (?, ?, ?, ?)'
    );
    $st->execute([
      (int)$data['company_id'],
      (int)$data['city_id'],
      $data['neighborhood'],
      $data['fee'],
    ]);
    return (int)db()->lastInsertId();
  }

  /** Verifica duplicidade de bairro na mesma cidade */
  public static function existsForCity(int $companyId, int $cityId, string $neighborhood): bool
  {
    $st = db()->prepare(
      'SELECT 1
         FROM delivery_zones
        WHERE company_id = ?
          AND city_id = ?
          AND LOWER(neighborhood) = LOWER(?)
        LIMIT 1'
    );
    $st->execute([$companyId, $cityId, $neighborhood]);
    return (bool)$st->fetchColumn();
  }

  /** Exclui zona */
  public static function delete(int $id, int $companyId): void
  {
    $st = db()->prepare('DELETE FROM delivery_zones WHERE id = ? AND company_id = ?');
    $st->execute([$id, $companyId]);
  }
}
