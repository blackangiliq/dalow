<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

// ---------- واجهة الزبائن ----------
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/products', [ShopController::class, 'products'])->name('products');
Route::get('/category/{category}', [ShopController::class, 'products'])->name('category');
Route::get('/product/{product}', [ShopController::class, 'show'])->name('product');
Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('suggest')->middleware('throttle:suggest');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');

Route::get('/checkout', [CheckoutController::class, 'form'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:orders');
Route::get('/prescription', [CheckoutController::class, 'prescriptionForm'])->name('prescription');
Route::post('/prescription', [CheckoutController::class, 'prescriptionStore'])->name('prescription.store')->middleware('throttle:orders');
Route::get('/order/{number}/done', [CheckoutController::class, 'done'])->name('order.done');
Route::get('/track', [CheckoutController::class, 'track'])->name('track')->middleware('throttle:track');

Route::post('/telegram/webhook/{secret}', TelegramWebhookController::class)->name('telegram.webhook');

// ---------- لوحة التحكم ----------
Route::get('/admin/login', [Admin\AuthController::class, 'form'])->name('login');
Route::post('/admin/login', [Admin\AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::post('/products/bulk', [Admin\ProductController::class, 'bulk'])->name('products.bulk');
    Route::resource('products', Admin\ProductController::class)->except('show');

    Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::get('/orders/{order}/prescription', [Admin\OrderController::class, 'prescription'])->name('orders.prescription');

    Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
    Route::post('/staff/sync', [Admin\StaffController::class, 'sync'])->name('staff.sync');
    Route::put('/staff/{staff}', [Admin\StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [Admin\StaffController::class, 'destroy'])->name('staff.destroy');
    Route::post('/staff/{staff}/test', [Admin\StaffController::class, 'test'])->name('staff.test');

    Route::get('/import', [Admin\ImportController::class, 'form'])->name('import');
    Route::post('/import', [Admin\ImportController::class, 'upload'])->name('import.upload');
    Route::get('/import/map', [Admin\ImportController::class, 'map'])->name('import.map');
    Route::post('/import/run', [Admin\ImportController::class, 'run'])->name('import.run');
    Route::get('/import/template', [Admin\ImportController::class, 'template'])->name('import.template');

    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/webhook', [Admin\SettingController::class, 'webhook'])->name('settings.webhook');
    Route::put('/settings/password', [Admin\SettingController::class, 'password'])->name('settings.password');
});
