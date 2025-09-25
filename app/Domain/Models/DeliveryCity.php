<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Database;
use PDO;

class DeliveryCity
{
    /**
     * Lista cidades da empresa.
     * Se $search for informado, filtra por nome (case-insensitive).
     */
    public static function allByCompany(int $companyId, ?string $search = null): array
    {
        $sql = 'SELECT * FROM delivery_cities WHERE company_id = ?';
        $params = [$companyId];

        if ($search !== null && $search !== '') {
            $sql .= ' AND LOWER(name) LIKE LOWER(?)';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY name';

        $st = Database::connection()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /** Verifica existência por nome (case-insensitive) */
    public static function existsByName(int $companyId, string $name): bool
    {
        $st = Database::connection()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) LIMIT 1');
        $st->execute([$companyId, $name]);

        return (bool)$st->fetchColumn();
    }

    /** Verifica existência por nome, ignorando um ID específico (edição) */
    public static function existsByNameExcept(int $companyId, string $name, int $ignoreId): bool
    {
        $st = Database::connection()->prepare('SELECT 1 FROM delivery_cities WHERE company_id = ? AND LOWER(name) = LOWER(?) AND id <> ? LIMIT 1');
        $st->execute([$companyId, $name, $ignoreId]);

        return (bool)$st->fetchColumn();
    }

    /** Busca uma cidade específica da empresa */
    public static function findForCompany(int $id, int $companyId): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM delivery_cities WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** Cria cidade */
    public static function create(array $data): int
    {
        $st = Database::connection()->prepare('INSERT INTO delivery_cities (company_id, name) VALUES (?, ?)');
        $st->execute([(int)$data['company_id'], $data['name']]);

        return (int)Database::connection()->lastInsertId();
    }

    /** Atualiza cidade */
    public static function update(int $id, int $companyId, string $name): void
    {
        $st = Database::connection()->prepare('UPDATE delivery_cities SET name = ? WHERE id = ? AND company_id = ?');
        $st->execute([$name, $id, $companyId]);
    }

    /** Exclui cidade */
    public static function delete(int $id, int $companyId): void
    {
        $st = Database::connection()->prepare('DELETE FROM delivery_cities WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
    }
}
