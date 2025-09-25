<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Customer;

final class CustomerService
{
    public function findCompanyBySlug(string $slug): ?array
    {
        return Customer::findCompanyBySlug($slug);
    }

    public function findByCompanyAndWhatsapp(int $companyId, string $whatsappE164): ?array
    {
        return Customer::findByCompanyAndE164($companyId, $whatsappE164);
    }

    public function insert(array $data): int
    {
        return Customer::insert($data);
    }

    public function updateById(int $id, array $data): void
    {
        Customer::updateById($id, $data);
    }

    public function findById(int $id): ?array
    {
        return Customer::findById($id);
    }
}
