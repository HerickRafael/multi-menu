<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;

class AdminHashController extends Controller
{
    public function show(): void
    {
        $this->view('admin/hash/index', [
            'hash' => null,
            'error' => null,
        ]);
    }

    public function generate(): void
    {
        $password = (string) ($_POST['password'] ?? '');
        $hash = null;
        $error = null;

        if (trim($password) === '') {
            $error = 'Informe uma senha para gerar o hash.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            if ($hash === false) {
                $error = 'Não foi possível gerar o hash. Tente novamente.';
                $hash = null;
            }
        }

        $this->view('admin/hash/index', compact('hash', 'error'));
    }
}
