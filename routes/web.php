<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductRequestController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TrackController;
use App\Http\Middleware\TrackVisit;
use Illuminate\Support\Facades\Route;

// ---------- واجهة الزبائن ----------
Route::get('/robots.txt', [PageController::class, 'robots']);
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

Route::middleware(TrackVisit::class)->group(function () {
    Route::get('/', [ShopController::class, 'home'])->name('home');
    Route::get('/products', [ShopController::class, 'products'])->name('products');
    Route::get('/category/{category}', [ShopController::class, 'products'])->name('category');
    Route::get('/product/{product}', [ShopController::class, 'show'])->name('product');
    Route::get('/search/suggest', [ShopController::class, 'suggest'])->name('suggest')->middleware('throttle:suggest');
    Route::get('/verify', [ShopController::class, 'verify'])->name('verify')->middleware('throttle:track');
    Route::post('/t', TrackController::class)->name('track.event')->middleware('throttle:events');

    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');

    Route::get('/checkout', [CheckoutController::class, 'form'])->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:orders');
    Route::get('/prescription', [CheckoutController::class, 'prescriptionForm'])->name('prescription');
    Route::post('/prescription', [CheckoutController::class, 'prescriptionStore'])->name('prescription.store')->middleware('throttle:orders');
    Route::get('/order/{number}/done', [CheckoutController::class, 'done'])->name('order.done');
    Route::get('/track', [CheckoutController::class, 'track'])->name('track')->middleware('throttle:track');

    Route::get('/request', [ProductRequestController::class, 'form'])->name('request');
    Route::post('/request', [ProductRequestController::class, 'store'])->name('request.store')->middleware('throttle:contact');

    Route::get('/faq', [PageController::class, 'faq'])->name('faq');
    Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
    Route::post('/contact', [PageController::class, 'contactStore'])->name('contact.store')->middleware('throttle:contact');

    // حساب الزبون
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:login');
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/favorite/{product}', [FavoriteController::class, 'toggle'])->name('favorite');

    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
        Route::get('/account', [AccountController::class, 'index'])->name('account');
        Route::put('/account', [AccountController::class, 'update'])->name('account.update');
        Route::post('/account/reorder/{order}', [AccountController::class, 'reorder'])->name('account.reorder');
    });
});

// ---------- لوحة التحكم ----------
Route::get('/admin/login', [Admin\AuthController::class, 'form'])->name('login');
Route::post('/admin/login', [Admin\AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [Admin\AuthController::class, 'logout'])->name('logout');
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/pulse', [Admin\DashboardController::class, 'pulse'])->name('pulse');

    Route::post('/products/bulk', [Admin\ProductController::class, 'bulk'])->name('products.bulk');
    Route::resource('products', Admin\ProductController::class)->except('show');

    Route::get('/categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{id}', [Admin\CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{id}', [Admin\CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::get('/orders', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/export', [Admin\OrderController::class, 'export'])->name('orders.export');
    Route::get('/orders/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}', [Admin\OrderController::class, 'update'])->name('orders.update');
    Route::get('/orders/{order}/invoice', [Admin\OrderController::class, 'invoice'])->name('orders.invoice');
    Route::get('/orders/{order}/prescription', [Admin\OrderController::class, 'prescription'])->name('orders.prescription');

    Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
    Route::post('/staff/import', [Admin\StaffController::class, 'import'])->name('staff.import');
    Route::put('/staff/{staff}', [Admin\StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [Admin\StaffController::class, 'destroy'])->name('staff.destroy');
    Route::post('/staff/{staff}/test', [Admin\StaffController::class, 'test'])->name('staff.test');

    Route::get('/import', [Admin\ImportController::class, 'form'])->name('import');
    Route::post('/import', [Admin\ImportController::class, 'upload'])->name('import.upload');
    Route::get('/import/map', [Admin\ImportController::class, 'map'])->name('import.map');
    Route::post('/import/run', [Admin\ImportController::class, 'run'])->name('import.run');
    Route::get('/import/template', [Admin\ImportController::class, 'template'])->name('import.template');

    Route::get('/telegram', [Admin\TelegramController::class, 'index'])->name('telegram');
    Route::put('/telegram/credentials', [Admin\TelegramController::class, 'credentials'])->name('telegram.credentials');
    Route::post('/telegram/link', [Admin\TelegramController::class, 'link'])->name('telegram.link');
    Route::post('/telegram/code', [Admin\TelegramController::class, 'verifyCode'])->name('telegram.code');
    Route::post('/telegram/password', [Admin\TelegramController::class, 'verifyPassword'])->name('telegram.password');
    Route::post('/telegram/cancel', [Admin\TelegramController::class, 'cancel'])->name('telegram.cancel');
    Route::post('/telegram/test', [Admin\TelegramController::class, 'test'])->name('telegram.test');
    Route::delete('/telegram', [Admin\TelegramController::class, 'unlink'])->name('telegram.unlink');

    Route::get('/faqs', [Admin\FaqController::class, 'index'])->name('faqs.index');
    Route::post('/faqs', [Admin\FaqController::class, 'store'])->name('faqs.store');
    Route::put('/faqs/{faq}', [Admin\FaqController::class, 'update'])->name('faqs.update');
    Route::delete('/faqs/{faq}', [Admin\FaqController::class, 'destroy'])->name('faqs.destroy');

    Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
    Route::put('/messages/{message}', [Admin\MessageController::class, 'toggle'])->name('messages.toggle');
    Route::delete('/messages/{message}', [Admin\MessageController::class, 'destroy'])->name('messages.destroy');

    Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::post('/analytics/synonyms', [Admin\AnalyticsController::class, 'synonymStore'])->name('synonyms.store');
    Route::delete('/analytics/synonyms/{synonym}', [Admin\AnalyticsController::class, 'synonymDestroy'])->name('synonyms.destroy');

    Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/price-alerts', [Admin\PriceAlertController::class, 'index'])->name('price-alerts');

    Route::get('/requests', [Admin\ProductRequestController::class, 'index'])->name('requests.index');
    Route::put('/requests/{productRequest}', [Admin\ProductRequestController::class, 'update'])->name('requests.update');
    Route::delete('/requests/{productRequest}', [Admin\ProductRequestController::class, 'destroy'])->name('requests.destroy');
    Route::get('/requests/{productRequest}/image', [Admin\ProductRequestController::class, 'image'])->name('requests.image');

    Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings');
    Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::put('/settings/password', [Admin\SettingController::class, 'password'])->name('settings.password');
});
