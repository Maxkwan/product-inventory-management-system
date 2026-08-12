<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['status' => 'ok']));

Route::apiResource('categories', CategoryController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('suppliers', SupplierController::class);
