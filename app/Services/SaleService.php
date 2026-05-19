<?php

namespace App\Services;

use App\Models\Sale;
use App\Repositories\SaleRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleService
{
    protected SaleRepositoryInterface $saleRepository;
    protected DashboardService $dashboardService;

    public function __construct(SaleRepositoryInterface $saleRepository, DashboardService $dashboardService)
    {
        $this->saleRepository = $saleRepository;
        $this->dashboardService = $dashboardService;
    }

    public function getAllSales(): Collection
    {
        return $this->saleRepository->all();
    }

    public function getPaginatedSales(int $perPage = 15): LengthAwarePaginator
    {
        return $this->saleRepository->paginate($perPage);
    }

    public function getSaleById(int $id): ?Sale
    {
        return $this->saleRepository->find($id);
    }

    public function createSale(array $data): Sale
    {
        $sale = $this->saleRepository->create($data);
        $this->dashboardService->clearUserCache($sale->product->user_id);
        return $sale;
    }

    public function updateSale(Sale $sale, array $data): bool
    {
        $updated = $this->saleRepository->update($sale, $data);

        if ($updated) {
            $this->dashboardService->clearUserCache($sale->product->user_id);
        }

        return $updated;
    }

    public function deleteSale(Sale $sale): bool
    {
        $userId = $sale->product->user_id;
        $deleted = $this->saleRepository->delete($sale);

        if ($deleted) {
            $this->dashboardService->clearUserCache($userId);
        }

        return $deleted;
    }

    public function getSalesByProduct(int $productId): Collection
    {
        return $this->saleRepository->findByProduct($productId);
    }
}