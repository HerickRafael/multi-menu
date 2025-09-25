<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\ProductCustomization;

final class ProductCustomizationService
{
    public function sanitizePayload(array $payload, int $companyId): array
    {
        return ProductCustomization::sanitizePayload($payload, $companyId);
    }

    public function save(int $productId, array $customization): void
    {
        ProductCustomization::save($productId, $customization);
    }

    public function loadForAdmin(int $productId): array
    {
        return ProductCustomization::loadForAdmin($productId);
    }

    public function loadForPublic(int $productId): array
    {
        return ProductCustomization::loadForPublic($productId);
    }
}
