<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Support\InventoryCache;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $suppliers = Cache::remember(InventoryCache::key('suppliers.index', request()->query()), InventoryCache::TTL_SECONDS,
            fn () => Supplier::query()->withCount('products')->orderBy('name')->paginate(15));

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        $supplier = Supplier::create($request->validated())->refresh();
        InventoryCache::invalidate();

        return new SupplierResource($supplier);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier->loadCount('products'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());
        InventoryCache::invalidate();

        return new SupplierResource($supplier->refresh()->loadCount('products'));
    }

    public function destroy(Supplier $supplier): Response
    {
        $supplier->delete();
        InventoryCache::invalidate();

        return response()->noContent();
    }
}
