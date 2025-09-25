<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Core\Database;
use App\Domain\Models\Product;

final class ProductService
{
    public function listByCompany(int $companyId, ?string $query = null, bool $onlyActive = true): array
    {
        return Product::listByCompany($companyId, $query, $onlyActive);
    }

    public function listByCategory(int $companyId, int $categoryId, ?string $query = null): array
    {
        return Product::listByCategory($companyId, $categoryId, $query);
    }

    public function allForCompany(int $companyId): array
    {
        return Product::allForCompany($companyId);
    }

    public function find(int $id): ?array
    {
        return Product::find($id);
    }

    public function findByCompanyAndId(int $companyId, int $productId): ?array
    {
        return Product::findByCompanyAndId($companyId, $productId);
    }

    public function nextSkuForCompany(int $companyId): string
    {
        return Product::nextSkuForCompany($companyId);
    }

    public function create(array $data): int
    {
        return Product::create($data);
    }

    public function update(int $id, array $data): void
    {
        Product::update($id, $data);
    }

    public function delete(int $id): void
    {
        Product::delete($id);
    }

    public function listNovelties(int $companyId, int $days = 14, int $limit = 12): array
    {
        return Product::novidadesByCompanyId(Database::connection(), $companyId, $days, $limit);
    }

    public function listBestSellers(int $companyId, int $limit = 12): array
    {
        return Product::maisPedidosByCompanyId(Database::connection(), $companyId, $limit);
    }

    public function simpleProductsForCompany(int $companyId, bool $onlyActive = true): array
    {
        return Product::simpleProductsForCompany($companyId, $onlyActive);
    }

    public function getComboGroupsWithItems(int $productId): array
    {
        return Product::getComboGroupsWithItems($productId);
    }

    public function sanitizeComboGroupsPayload(array $payload, int $companyId): array
    {
        return Product::sanitizeComboGroupsPayload($payload, $companyId);
    }

    public function saveComboGroupsAndItems(int $productId, array $groups): void
    {
        Product::saveComboGroupsAndItems($productId, $groups);
    }

    public function calculateComboTotal(array $product, array $selected): array
    {
        return Product::calculateComboTotal($product, $selected);
    }
}
