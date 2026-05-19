<?php

namespace App\Repositories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleRepository implements SaleRepositoryInterface
{
    public function all(): Collection
    {
        return Sale::all();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Sale::paginate($perPage);
    }

    public function find(int $id): ?Sale
    {
        return Sale::find($id);
    }

    public function create(array $data): Sale
    {
        return Sale::create($data);
    }

    public function update(Sale $sale, array $data): bool
    {
        return $sale->update($data);
    }

    public function delete(Sale $sale): bool
    {
        return $sale->delete();
    }

    public function findByProduct(int $productId): Collection
    {
        return Sale::where('product_id', $productId)->get();
    }
}