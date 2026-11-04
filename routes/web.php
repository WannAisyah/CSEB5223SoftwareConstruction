<?php

use App\Http\Controllers\Auth\AdminSessionController;
use App\Http\Controllers\Auth\CustomerSessionController;
use App\Http\Controllers\Auth\RegisteredCustomerController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartItemController;
use App\Http\Controllers\CustomerCartController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products');

Route::get('/admin/login', [AdminSessionController::class, 'create'])->name('login');
Route::post('/admin/login', [AdminSessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('admin.login.store');
Route::post('/admin/logout', [AdminSessionController::class, 'destroy'])
    ->middleware('auth:web')
    ->name('admin.logout');

Route::get('/login', [CustomerSessionController::class, 'create'])->name('customer.login');
Route::post('/login', [CustomerSessionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('customer.login.store');
Route::get('/register', [RegisteredCustomerController::class, 'create'])->name('customer.register');
Route::post('/register', [RegisteredCustomerController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('customer.register.store');
Route::post('/logout', [CustomerSessionController::class, 'destroy'])
    ->middleware('auth:customer')
    ->name('customer.logout');

Route::get('/products', [ProductController::class, 'index'])->name('products.index');

Route::middleware('auth:web')->group(function (): void {
    Route::resource('products', ProductController::class)->except(['index', 'show']);
    Route::resource('customers', CustomerController::class);
    Route::resource('carts', CartController::class);
    Route::resource('cart-items', CartItemController::class);
    Route::resource('orders', OrderController::class);
    Route::resource('order-items', OrderItemController::class);
    Route::resource('payments', PaymentController::class);
});

Route::get('/products/{product}', [ProductController::class, 'show'])
    ->whereNumber('product')
    ->name('products.show');

Route::middleware('auth:customer')->prefix('my')->name('customer.')->group(function (): void {
    Route::get('/profile', [CustomerProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [CustomerProfileController::class, 'update'])->name('profile.update');
    Route::get('/cart', [CustomerCartController::class, 'show'])->name('cart.show');
    Route::delete('/cart', [CustomerCartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/items', [CustomerCartController::class, 'store'])->name('cart-items.store');
    Route::patch('/cart/items/{cartItem}', [CustomerCartController::class, 'update'])
        ->whereNumber('cartItem')
        ->name('cart-items.update');
    Route::delete('/cart/items/{cartItem}', [CustomerCartController::class, 'destroy'])
        ->whereNumber('cartItem')
        ->name('cart-items.destroy');
    Route::post('/checkout', [CustomerCartController::class, 'checkout'])
        ->middleware('throttle:10,1')
        ->name('checkout');
    Route::get('/orders', [CustomerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [CustomerOrderController::class, 'show'])
        ->whereNumber('order')
        ->name('orders.show');
});
