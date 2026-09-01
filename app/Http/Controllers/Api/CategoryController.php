<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\InventoryCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = Cache::remember(InventoryCache::key('categories.index.latest', request()->query()), InventoryCache::TTL_SECONDS,
            fn () => Category::query()->withCount('products')->latest()->latest('id')->paginate(15));

        return CategoryResource::collection($categories);
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        $category = Category::create($request->validated());
        InventoryCache::invalidate();

        return new CategoryResource($category);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());
        InventoryCache::invalidate();

        return new CategoryResource($category->refresh()->loadCount('products'));
    }

    public function destroy(Category $category): Response|JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'A category containing products cannot be deleted.'], 409);
        }

        $category->delete();
        InventoryCache::invalidate();

        return response()->noContent();
    }
}
