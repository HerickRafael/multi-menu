<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Services\CompanyService;
use App\Core\Controller;

final class WelcomeController extends Controller
{
    private CompanyService $companies;

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    public function index(): void
    {
        $companies = array_values(array_filter(
            $this->companies->all(),
            static fn (array $company): bool => (int)($company['active'] ?? 0) === 1
        ));

        if (count($companies) === 1) {
            $slug = (string)($companies[0]['slug'] ?? '');

            if ($slug !== '') {
                header('Location: /' . $slug, true, 302);
                exit;
            }
        }

        $this->view('welcome', [
            'companies' => $companies,
        ]);
    }
}
