<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\User;

final class UserService
{
    public function findByEmail(string $email): ?array
    {
        return User::findByEmail($email);
    }
}
