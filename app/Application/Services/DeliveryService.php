<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\DeliveryCity;
use App\Domain\Models\DeliveryZone;

final class DeliveryService
{
    public function listCities(int $companyId, ?string $search = null): array
    {
        return DeliveryCity::allByCompany($companyId, $search);
    }

    public function listZones(int $companyId, ?string $search = null): array
    {
        return DeliveryZone::allByCompany($companyId, $search);
    }

    public function createCity(array $data): int
    {
        return DeliveryCity::create($data);
    }

    public function updateCity(int $companyId, int $cityId, string $name): void
    {
        DeliveryCity::update($cityId, $companyId, $name);
    }

    public function deleteCity(int $companyId, int $cityId): void
    {
        DeliveryCity::delete($cityId, $companyId);
    }

    public function createZone(array $data): int
    {
        return DeliveryZone::create($data);
    }

    public function updateZone(int $zoneId, int $companyId, array $data): void
    {
        DeliveryZone::update($zoneId, $companyId, $data);
    }

    public function deleteZone(int $zoneId, int $companyId): void
    {
        DeliveryZone::delete($zoneId, $companyId);
    }

    public function findCity(int $id, int $companyId): ?array
    {
        return DeliveryCity::findForCompany($id, $companyId);
    }

    public function findZone(int $id, int $companyId): ?array
    {
        return DeliveryZone::findForCompany($id, $companyId);
    }

    public function cityExists(int $companyId, string $name, ?int $ignoreId = null): bool
    {
        if ($ignoreId !== null) {
            return DeliveryCity::existsByNameExcept($companyId, $name, $ignoreId);
        }

        return DeliveryCity::existsByName($companyId, $name);
    }

    public function adjustFees(int $companyId, float $delta): void
    {
        DeliveryZone::adjustFees($companyId, $delta);
    }

    public function zoneExists(int $companyId, int $cityId, string $neighborhood, ?int $ignoreId = null): bool
    {
        if ($ignoreId !== null) {
            return DeliveryZone::existsForCityExcept($companyId, $cityId, $neighborhood, $ignoreId);
        }

        return DeliveryZone::existsForCity($companyId, $cityId, $neighborhood);
    }
}
