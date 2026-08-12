<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $suppliers = Supplier::query()
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request): SupplierResource
    {
        return new SupplierResource(Supplier::create($request->validated())->refresh());
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier->loadCount('products'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): SupplierResource
    {
        $supplier->update($request->validated());

        return new SupplierResource($supplier->refresh()->loadCount('products'));
    }

    public function destroy(Supplier $supplier): Response
    {
        $supplier->delete();

        return response()->noContent();
    }
}
