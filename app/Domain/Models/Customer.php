<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Database;
use PDO;

class Customer
{
    private static ?PDO $shared = null;

    /**
     * Retorna um PDO. Ajuste para usar sua função global de conexão (ex.: Database::connection()) se já existir.
     * Você pode definir as credenciais via variáveis de ambiente ou constantes.
     *
     * Env/consts suportados:
     *  - DB_DSN  (ex: "mysql:host=localhost;dbname=multimenu;charset=utf8mb4")
     *  - DB_USER
     *  - DB_PASS
     */
    protected static function pdo(): PDO
    {
        if (self::$shared instanceof PDO) {
            return self::$shared;
        }

        self::$shared = Database::connection();

        return self::$shared;
    }

    /** Empresas */

    public static function findCompanyBySlug(string $slug): ?array
    {
        $sql = 'SELECT * FROM companies WHERE slug = :slug LIMIT 1';
        $st  = self::pdo()->prepare($sql);
        $st->execute([':slug' => $slug]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** Clientes */
    public static function findByCompanyAndE164(int $companyId, string $e164): ?array
    {
        $sql = 'SELECT * FROM customers WHERE company_id = :cid AND whatsapp_e164 = :e LIMIT 1';
        $st  = self::pdo()->prepare($sql);
        $st->execute([':cid' => $companyId, ':e' => $e164]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM customers WHERE id = :id LIMIT 1';
        $st  = self::pdo()->prepare($sql);
        $st->execute([':id' => $id]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public static function insert(array $data): int
    {
        $sql = 'INSERT INTO customers (company_id, name, whatsapp, whatsapp_e164, created_at, updated_at, last_login_at)
                VALUES (:company_id, :name, :whatsapp, :e164, :created_at, :updated_at, :last_login_at)';
        $pdo = self::pdo();
        $st  = $pdo->prepare($sql);
        $st->execute([
            ':company_id'   => (int)$data['company_id'],
            ':name'         => $data['name'],
            ':whatsapp'     => $data['whatsapp'],
            ':e164'         => $data['whatsapp_e164'],
            ':created_at'   => $data['created_at'],
            ':updated_at'   => $data['updated_at'],
            ':last_login_at' => $data['last_login_at'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function updateById(int $id, array $data): void
    {
        $sql = 'UPDATE customers
                   SET name = :name,
                       whatsapp = :whatsapp,
                       updated_at = :updated_at,
                       last_login_at = :last_login_at
                 WHERE id = :id';
        $st  = self::pdo()->prepare($sql);
        $st->execute([
            ':name'          => $data['name'],
            ':whatsapp'      => $data['whatsapp'],
            ':updated_at'    => $data['updated_at'],
            ':last_login_at' => $data['last_login_at'],
            ':id'            => $id,
        ]);
    }
}
