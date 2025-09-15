<?php
// app/models/Company.php
require_once __DIR__ . '/../config/db.php';

class Company
{
    /** Busca empresa pelo slug (url amigável) */
    public static function findBySlug(string $slug): ?array {
        $st = db()->prepare("SELECT * FROM companies WHERE slug = ? LIMIT 1");
        $st->execute([$slug]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Busca empresa pelo ID */
    public static function find(int $id): ?array {
        $st = db()->prepare("SELECT * FROM companies WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Lista todas as empresas (ex.: para painel admin global) */
    public static function all(): array {
        $st = db()->query("SELECT * FROM companies ORDER BY name ASC");
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Cria nova empresa e retorna ID */
    public static function create(array $data): int {
        $st = db()->prepare("
            INSERT INTO companies (name, slug, logo, active, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $st->execute([
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            isset($data['active']) ? (int)$data['active'] : 1,
        ]);
        return (int) db()->lastInsertId();
    }

    /** Atualiza empresa existente */
    public static function update(int $id, array $data): void {
        $st = db()->prepare("
            UPDATE companies
               SET name = ?, slug = ?, logo = ?, active = ?, updated_at = NOW()
             WHERE id = ?
        ");
        $st->execute([
            $data['name'],
            $data['slug'],
            $data['logo'] ?? null,
            isset($data['active']) ? (int)$data['active'] : 1,
            $id
        ]);
    }

    /** Remove empresa (pode adaptar para soft delete se preferir) */
    public static function delete(int $id): void {
        $st = db()->prepare("DELETE FROM companies WHERE id = ?");
        $st->execute([$id]);
    }
}
