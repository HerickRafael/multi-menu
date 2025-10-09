<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

class PaymentMethod
{
    public static function allByCompany(int $companyId): array
    {
        try {
            $st = db()->prepare('SELECT * FROM payment_methods WHERE company_id = ? ORDER BY sort_order, name');
            $st->execute([$companyId]);

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'payment_methods') !== false) {
                return [];
            }
            throw $e;
        }
    }

    public static function activeByCompany(int $companyId): array
    {
        try {
            $st = db()->prepare('SELECT * FROM payment_methods WHERE company_id = ? AND active = 1 ORDER BY sort_order, name');
            $st->execute([$companyId]);

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'payment_methods') !== false) {
                return [];
            }
            throw $e;
        }
    }

    public static function findForCompany(int $id, int $companyId): ?array
    {
        try {
            $st = db()->prepare('SELECT * FROM payment_methods WHERE id = ? AND company_id = ? LIMIT 1');
            $st->execute([$id, $companyId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            return $row ?: null;
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'payment_methods') !== false) {
                return null;
            }
            throw $e;
        }
    }

    public static function create(array $data): int
    {
        $sortOrder = isset($data['sort_order']) ? (int)$data['sort_order'] : self::nextSortOrder((int)$data['company_id']);
        $meta = !empty($data['meta']) ? json_encode($data['meta'], JSON_UNESCAPED_UNICODE) : null;
        $icon = isset($data['icon']) && $data['icon'] !== '' ? $data['icon'] : null;

        // permitir inserir com ID explícito (reutilizar lacunas de ids deletados)
        if (isset($data['id']) && $data['id']) {
            $st = db()->prepare(
                'INSERT INTO payment_methods (id, company_id, name, instructions, sort_order, active, `type`, `meta`, icon, pix_key)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $st->execute([
                (int)$data['id'],
                (int)$data['company_id'],
                $data['name'],
                $data['instructions'] ?? null,
                $sortOrder,
                !empty($data['active']) ? 1 : 0,
                $data['type'] ?? 'others',
                $meta,
                $icon,
                $data['pix_key'] ?? null,
            ]);

            return (int)$data['id'];
        }

        $st = db()->prepare(
            'INSERT INTO payment_methods (company_id, name, instructions, sort_order, active, `type`, `meta`, icon, pix_key)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([
            (int)$data['company_id'],
            $data['name'],
            $data['instructions'] ?? null,
            $sortOrder,
            !empty($data['active']) ? 1 : 0,
            $data['type'] ?? 'others',
            $meta,
            $icon,
            $data['pix_key'] ?? null,
        ]);

        return (int)db()->lastInsertId();
    }

    public static function findMissingId(): int
    {
        // retorna o menor inteiro >=1 que não existe na tabela
        $st = db()->prepare('SELECT id FROM payment_methods ORDER BY id ASC');
        $st->execute();
        $used = $st->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
        $next = 1;
        foreach ($used as $id) {
            $idInt = (int)$id;
            if ($idInt === $next) {
                $next++;
                continue;
            }
            if ($idInt > $next) {
                break;
            }
        }
        return $next;
    }

    public static function update(int $id, int $companyId, array $data): void
    {
        $st = db()->prepare(
            'UPDATE payment_methods
                SET name = ?, instructions = ?, sort_order = ?, active = ?, `type` = ?, `meta` = ?, icon = ?, pix_key = ?
              WHERE id = ? AND company_id = ?'
        );
        $meta = !empty($data['meta']) ? json_encode($data['meta'], JSON_UNESCAPED_UNICODE) : null;
        $icon = isset($data['icon']) && $data['icon'] !== '' ? $data['icon'] : null;
        $st->execute([
            $data['name'],
            $data['instructions'] ?? null,
            (int)$data['sort_order'],
            !empty($data['active']) ? 1 : 0,
            $data['type'] ?? 'others',
            $meta,
            $icon,
            $data['pix_key'] ?? null,
            $id,
            $companyId,
        ]);
    }

    public static function delete(int $id, int $companyId): void
    {
        $st = db()->prepare('DELETE FROM payment_methods WHERE id = ? AND company_id = ?');
        $st->execute([$id, $companyId]);
    }

    public static function setAllActiveForCompany(int $companyId, int $active): void
    {
        $st = db()->prepare('UPDATE payment_methods SET active = ? WHERE company_id = ?');
        $st->execute([(int)$active, $companyId]);
    }

    public static function nextSortOrder(int $companyId): int
    {
        $st = db()->prepare('SELECT MAX(sort_order) FROM payment_methods WHERE company_id = ?');
        $st->execute([$companyId]);
        $max = $st->fetchColumn();

        return $max !== null ? ((int)$max + 1) : 0;
    }
}
