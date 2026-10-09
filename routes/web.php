<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('customers', CustomerController::class);
Route::resource('products', ProductController::class);
Route::resource('carts', CartController::class);
Route::resource('cart-items', CartItemController::class);
Route::resource('orders', OrderController::class);
Route::resource('order-items', OrderItemController::class);
Route::resource('payments', PaymentController::class);
