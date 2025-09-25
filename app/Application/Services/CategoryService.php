<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Category;

final class CategoryService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function listActiveByCompany(int $companyId): array
    {
        return Category::listByCompany($companyId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllByCompany(int $companyId): array
    {
        return Category::allByCompany($companyId);
    }

    public function find(int $id): ?array
    {
        return Category::find($id);
    }

    public function create(array $data): int
    {
        return Category::create($data);
    }

    public function update(int $id, array $data): void
    {
        Category::update($id, $data);
    }

    public function delete(int $id): void
    {
        Category::delete($id);
    }
}
