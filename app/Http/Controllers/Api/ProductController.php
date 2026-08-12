<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'low_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,price,quantity,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::query()
            ->with(['category', 'suppliers'])
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where(
                fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%")
            ))
            ->when($validated['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when(array_key_exists('is_active', $validated), fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when($validated['low_stock'] ?? false, fn ($query) => $query->lowStock())
            ->orderBy($validated['sort'] ?? 'created_at', $validated['direction'] ?? 'desc')
            ->paginate($validated['per_page'] ?? 15)
            ->withQueryString();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = DB::transaction(function () use ($request): Product {
            $validated = $request->validated();
            $supplierIds = $validated['supplier_ids'] ?? [];
            unset($validated['supplier_ids']);

            $product = Product::create($validated);
            $product->suppliers()->sync($supplierIds);

            return $product;
        });

        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'suppliers']));
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        DB::transaction(function () use ($request, $product): void {
            $validated = $request->validated();
            $hasSupplierIds = array_key_exists('supplier_ids', $validated);
            $supplierIds = $validated['supplier_ids'] ?? [];
            unset($validated['supplier_ids']);

            $product->update($validated);

            if ($hasSupplierIds) {
                $product->suppliers()->sync($supplierIds);
            }
        });

        return new ProductResource($product->refresh()->load(['category', 'suppliers']));
    }

    public function destroy(Product $product): Response
    {
        $product->delete();

        return response()->noContent();
    }
}
