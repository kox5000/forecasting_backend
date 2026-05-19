<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleStoreRequest;
use App\Http\Requests\SaleUpdateRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        // Get sales for user's products
        $sales = $this->saleService->getAllSales()->filter(function ($sale) use ($userId) {
            return $sale->product->user_id === $userId;
        });
        return SaleResource::collection($sales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaleStoreRequest $request)
    {
        $data = $request->validated();
        // Check if product belongs to user
        $product = Product::find($data['product_id']);
        if (!$product || $product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $sale = $this->saleService->createSale($data);
        return new SaleResource($sale);
    }

    /**
     * Display the specified resource.
     */
public function show(int $id)
{
    $sale = $this->saleService->getSaleById($id);

    if (!$sale) {
        return response()->json(['message' => 'Sale not found'], 404);
    }

    if (!$sale->product || $sale->product->user_id !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return new SaleResource($sale);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(SaleUpdateRequest $request, int $id)
    {
        $sale = $this->saleService->getSaleById($id);
        if (!$sale || $sale->product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
        $this->saleService->updateSale($sale, $request->validated());
        return new SaleResource($sale);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $sale = $this->saleService->getSaleById($id);
        if (!$sale || $sale->product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Sale not found'], 404);
        }
        $this->saleService->deleteSale($sale);
        return response()->json(['message' => 'Sale deleted successfully']);
    }

    public function get7DayMovingAverage(Request $request)
    {
        $productId = $request->query('product_id');
        $query = \App\Models\Sale::query()->orderBy('date');

        if ($productId) {
            $product = Product::find($productId);
            if (!$product || $product->user_id !== auth()->id()) {
                return response()->json(['message' => 'Product not found or unauthorized'], 404);
            }
            $query->where('product_id', $productId);
        } else {
            $userId = auth()->id();
            $query->whereHas('product', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $sales = $query->get();

        $result = [];

        for ($i = 6; $i < count($sales); $i++) {
            $sum = 0;

            for ($j = $i; $j > $i - 7; $j--) {
                $sum += $sales[$j]->revenue; // أو quantity
            }

            $avg = $sum / 7;

            $result[] = [
                'date' => $sales[$i]->date->toDateString(),
                'average' => $avg
            ];
        }

        return response()->json($result);
    }
}
