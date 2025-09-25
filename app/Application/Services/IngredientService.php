<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Ingredient;

final class IngredientService
{
    public function listByCompany(int $companyId, ?int $productId = null, ?string $query = null): array
    {
        return Ingredient::listByCompany($companyId, $productId, $query);
    }

    public function allForCompany(int $companyId): array
    {
        return Ingredient::allForCompany($companyId);
    }

    public function listRecentByCompany(int $companyId, int $limit = 8): array
    {
        return Ingredient::listRecentByCompany($companyId, $limit);
    }

    public function countByCompany(int $companyId): int
    {
        return Ingredient::countByCompany($companyId);
    }

    public function existsByName(int $companyId, string $name, ?int $ignoreId = null): bool
    {
        return Ingredient::existsByName($companyId, $name, $ignoreId);
    }

    public function findForCompany(int $companyId, int $ingredientId): ?array
    {
        return Ingredient::findForCompany($companyId, $ingredientId);
    }

    public function create(array $data): int
    {
        return Ingredient::create($data);
    }

    public function update(int $id, array $data): void
    {
        Ingredient::update($id, $data);
    }

    public function delete(int $companyId, int $id): void
    {
        Ingredient::delete($companyId, $id);
    }

    public function assignedProducts(int $ingredientId): array
    {
        return Ingredient::assignedProducts($ingredientId);
    }
}
