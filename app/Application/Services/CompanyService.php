<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Models\Company;
use PDOException;
use RuntimeException;

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
        try {
            return Company::all();
        } catch (PDOException|RuntimeException $exception) {
            // Quando o banco de dados ainda não está acessível (ex.: containers subindo)
            // evitamos propagar o erro para a camada HTTP. A tela inicial continua
            // disponível e o log já registra o problema via Router::dispatch().
            return [];
        }
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
