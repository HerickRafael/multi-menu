<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Order;
use PDO;

final class OrderService
{
    public function listByCompany(PDO $pdo, int $companyId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        return Order::listByCompany($pdo, $companyId, $status, $limit, $offset);
    }

    public function findWithItems(PDO $pdo, int $orderId, int $companyId): ?array
    {
        return Order::findWithItems($pdo, $orderId, $companyId);
    }

    public function updateStatus(PDO $pdo, int $orderId, int $companyId, string $status): bool
    {
        return Order::updateStatus($pdo, $orderId, $companyId, $status);
    }

    public function delete(PDO $pdo, int $orderId, int $companyId): bool
    {
        return Order::delete($pdo, $orderId, $companyId);
    }

    public function create(PDO $pdo, array $data): int
    {
        return Order::create($pdo, $data);
    }

    public function addItem(PDO $pdo, int $orderId, array $item): void
    {
        Order::addItem($pdo, $orderId, $item);
    }

    public function countByCompany(int $companyId): int
    {
        return Order::countByCompany($companyId);
    }

    public function listRecentByCompany(int $companyId, int $limit = 8): array
    {
        return Order::listRecentByCompany($companyId, $limit);
    }
}
