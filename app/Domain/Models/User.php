<?php

declare(strict_types=1);

namespace App\Domain\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $st = Database::connection()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([$email]);

        return $st->fetch() ?: null;
    }
}
