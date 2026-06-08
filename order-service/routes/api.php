<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderController;

Route::apiResource('orders', OrderController::class);
Route::get('orders/user/{userId}', [OrderController::class, 'getByUser']);