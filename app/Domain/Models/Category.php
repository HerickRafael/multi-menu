<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Database;
use PDO;

class Category
{
    public static function listByCompany(int $companyId): array
    {
        $st = Database::connection()->prepare('SELECT * FROM categories WHERE company_id = ? AND active = 1 ORDER BY sort_order, name');
        $st->execute([$companyId]);

        return $st->fetchAll();
    }
    public static function allByCompany(int $companyId): array
    {
        $st = Database::connection()->prepare('SELECT * FROM categories WHERE company_id = ? ORDER BY sort_order, name');
        $st->execute([$companyId]);

        return $st->fetchAll();
    }
    public static function find(int $id): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM categories WHERE id = ?');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }
    public static function create(array $data): int
    {
        $st = Database::connection()->prepare('INSERT INTO categories (company_id, name, sort_order, active) VALUES (?,?,?,?)');
        $st->execute([$data['company_id'], $data['name'], (int)($data['sort_order'] ?? 0), (int)($data['active'] ?? 1)]);

        return (int)Database::connection()->lastInsertId();
    }
    public static function update(int $id, array $data): void
    {
        $st = Database::connection()->prepare('UPDATE categories SET name=?, sort_order=?, active=? WHERE id=?');
        $st->execute([$data['name'], (int)($data['sort_order'] ?? 0), (int)($data['active'] ?? 1), $id]);
    }
    public static function delete(int $id): void
    {
        $st = Database::connection()->prepare('DELETE FROM categories WHERE id=?');
        $st->execute([$id]);
    }
}
