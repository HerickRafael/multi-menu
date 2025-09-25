<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = config('database');

        if (!is_array($config)) {
            throw new RuntimeException('Configuração de banco de dados inválida.');
        }

        $driver = $config['default'] ?? 'mysql';
        $connectionConfig = $config['connections'][$driver] ?? null;

        if (!is_array($connectionConfig)) {
            throw new RuntimeException(sprintf('Configuração para conexão %s não encontrada.', $driver));
        }

        if ($connectionConfig['driver'] !== 'mysql') {
            throw new RuntimeException('Atualmente apenas MySQL é suportado.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $connectionConfig['host'],
            $connectionConfig['port'],
            $connectionConfig['database'],
            $connectionConfig['charset'] ?? 'utf8mb4'
        );

        try {
            self::$connection = new PDO(
                $dsn,
                (string) $connectionConfig['username'],
                (string) $connectionConfig['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados.', 0, $exception);
        }

        return self::$connection;
    }
}
