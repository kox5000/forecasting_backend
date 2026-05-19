<?php

namespace App\Repositories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface SaleRepositoryInterface
{
    public function all(): Collection;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?Sale;
    public function create(array $data): Sale;
    public function update(Sale $sale, array $data): bool;
    public function delete(Sale $sale): bool;
    public function findByProduct(int $productId): Collection;
}