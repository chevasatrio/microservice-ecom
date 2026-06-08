<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::apiResource('products', ProductController::class);
Route::post('products/{id}/update-stock', [ProductController::class, 'updateStock']);