<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Database;
use PDO;

final class Company
{
    public static function findBySlug(string $slug): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM companies WHERE slug = ? LIMIT 1');
        $statement->execute([$slug]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function find(int $id): ?array
    {
        $statement = Database::connection()->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
        $statement->execute([$id]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function all(): array
    {
        $statement = Database::connection()->query('SELECT * FROM companies ORDER BY name ASC');

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(array $data): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO companies (name, slug, logo, active, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $statement->execute([
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            isset($data['active']) ? (int) $data['active'] : 1,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE companies SET name = ?, slug = ?, logo = ?, active = ?, updated_at = NOW() WHERE id = ?'
        );
        $statement->execute([
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            isset($data['active']) ? (int) $data['active'] : 1,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $statement = Database::connection()->prepare('DELETE FROM companies WHERE id = ?');
        $statement->execute([$id]);
    }

    public static function updateDeliveryOptions(int $id, float $afterHoursFee, bool $freeDelivery): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE companies SET delivery_after_hours_fee = ?, delivery_free_enabled = ? WHERE id = ?'
        );
        $statement->execute([
            number_format($afterHoursFee, 2, '.', ''),
            $freeDelivery ? 1 : 0,
            $id,
        ]);
    }
}
