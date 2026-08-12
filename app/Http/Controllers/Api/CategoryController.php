<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::query()->withCount('products')->orderBy('name')->paginate(15));
    }

    public function store(StoreCategoryRequest $request): CategoryResource
    {
        return new CategoryResource(Category::create($request->validated()));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('products'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category->refresh()->loadCount('products'));
    }

    public function destroy(Category $category): Response|JsonResponse
    {
        if ($category->products()->exists()) {
            return response()->json(['message' => 'A category containing products cannot be deleted.'], 409);
        }

        $category->delete();

        return response()->noContent();
    }
}
