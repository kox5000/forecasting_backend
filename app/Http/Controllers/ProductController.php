<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        $products = $this->productService->getProductsByUser($userId);
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $product = $this->productService->createProduct($data);
        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $product = $this->productService->getProductById($id);
        if (!$product || $product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductUpdateRequest $request, int $id)
    {
        $product = $this->productService->getProductById($id);
        if (!$product || $product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        $this->productService->updateProduct($product, $request->validated());
        return new ProductResource($product);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $product = $this->productService->getProductById($id);
        if (!$product || $product->user_id !== auth()->id()) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        $this->productService->deleteProduct($product);
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
