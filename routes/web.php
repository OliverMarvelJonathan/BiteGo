<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

// ─── Halaman publik ───────────────────────────────────────────────
Route::get('/', [CanteenController::class, 'index'])->name('canteen.index');
Route::get('/canteen', [CanteenController::class, 'index'])->name('canteen.index.alt');
Route::get('/menu', [CanteenController::class, 'menu'])->name('canteen.menu');
Route::get('/stall/{stall:slug}', [CanteenController::class, 'stall'])->name('canteen.stall');

// ─── Autentikasi (guest) ──────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

// ─── Pemesanan (siswa yang login) ─────────────────────────────────
Route::middleware(['auth', 'role:siswa'])->group(function () {
    // Keranjang Belanja (Multi-item)
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/cart/items/{menu}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/items/{menu}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
    Route::post('/cart/replace', [CartController::class, 'replace'])->name('cart.replace');

    // 3-Step Checkout Flow
    Route::get('/checkout/details', [CheckoutController::class, 'details'])->name('checkout.details');
    Route::post('/checkout/details', [CheckoutController::class, 'processDetails'])->name('checkout.processDetails');
    Route::get('/checkout/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');
    Route::post('/checkout/confirm', [CheckoutController::class, 'placeOrder'])->name('checkout.placeOrder');

    // Single-item direct order (kompatibilitas Phase 2)
    Route::get('/order/{menu:slug}/create', [OrderController::class, 'create'])->name('orders.create');
    Route::post('/order/{menu:slug}', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/success', [OrderController::class, 'success'])->name('orders.success');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
});

// ─── Dashboard penjual (vendor) ───────────────────────────────────
Route::middleware(['auth', 'role:vendor'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', [VendorController::class, 'dashboard'])->name('dashboard');
    Route::post('/orders/{order}/status', [VendorController::class, 'updateStatus'])->name('orders.status');

    Route::get('/menu', [VendorController::class, 'menus'])->name('menu.index');
    Route::post('/menu', [VendorController::class, 'menuStore'])->name('menu.store');
    Route::patch('/menu/{menu}', [VendorController::class, 'menuUpdate'])->name('menu.update');
    Route::delete('/menu/{menu}', [VendorController::class, 'menuDestroy'])->name('menu.destroy');
});
