<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Company;

final class CompanyService
{
    public function findBySlug(string $slug): ?array
    {
        return Company::findBySlug($slug);
    }

    public function find(int $id): ?array
    {
        return Company::find($id);
    }

    public function all(): array
    {
        return Company::all();
    }

    public function update(int $id, array $data): void
    {
        Company::update($id, $data);
    }

    public function updateDeliveryOptions(int $id, float $afterHoursFee, bool $freeDelivery): void
    {
        Company::updateDeliveryOptions($id, $afterHoursFee, $freeDelivery);
    }
}
