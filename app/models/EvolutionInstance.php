<?php
// app/models/EvolutionInstance.php
require_once __DIR__ . '/../config/db.php';

class EvolutionInstance
{
    public static function allForCompany(int $companyId): array
    {
        $st = db()->prepare('SELECT * FROM evolution_instances WHERE company_id = ? ORDER BY created_at DESC');
        $st->execute([$companyId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(int $companyId, array $data): int
    {
        $st = db()->prepare('INSERT INTO evolution_instances (company_id, label, number, instance_identifier, qr_code, status, connected_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $st->execute([
            $companyId,
            $data['label'] ?? null,
            $data['number'] ?? null,
            $data['instance_identifier'] ?? null,
            $data['qr_code'] ?? null,
            $data['status'] ?? 'pending',
            $data['connected_at'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM evolution_instances WHERE id = ? LIMIT 1');
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function update(int $id, array $data): void
    {
        $fields = [];
        $vals = [];

        foreach (['label','number','instance_identifier','qr_code','status','connected_at'] as $k) {
            if (array_key_exists($k, $data)) {
                $fields[] = "$k = ?";
                $vals[] = $data[$k];
            }
        }

        if (!$fields) return;

        $vals[] = $id;
        $sql = 'UPDATE evolution_instances SET ' . implode(', ', $fields) . ' WHERE id = ?';
        db()->prepare($sql)->execute($vals);
    }

    public static function delete(int $id): void
    {
        db()->prepare('DELETE FROM evolution_instances WHERE id = ?')->execute([$id]);
    }
}
