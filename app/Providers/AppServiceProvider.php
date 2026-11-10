<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ContactMessage;
use App\Services\Telegram\MadelineTelegramClient;
use App\Services\Telegram\TelegramClient;
use App\Support\Cart;
use App\Support\Util;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // حساب تيليجرام: api_id و api_hash من إعدادات لوحة التحكم، وإن لم توجد فمن .env
        $this->app->singleton(MadelineTelegramClient::class, fn () => new MadelineTelegramClient(
            apiId: Setting::get('tg_api_id'),
            apiHash: Setting::get('tg_api_hash'),
            sessionPath: storage_path('app/private/telegram/session.madeline'),
            linkedMarkerPath: storage_path('app/private/telegram/linked.json'),
        ));
        $this->app->bind(TelegramClient::class, MadelineTelegramClient::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        // حدود مستقلة لكل نوع من الطلبات حتى لا يستهلك البحث حصة إرسال الطلب أو تسجيل الدخول
        foreach (['orders' => 6, 'login' => 6, 'track' => 20, 'suggest' => 90, 'contact' => 4] as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)->by($name.'|'.$request->ip()));
        }

        // @iqd(12500) => 12,500 د.ع
        Blade::directive('iqd', fn ($expr) => "<?php echo e(\\App\\Support\\Util::money($expr)); ?>");
        // @icon('pill')
        Blade::directive('icon', fn ($expr) => "<?php echo view('partials.icon', ['name' => $expr])->render(); ?>");

        // بيانات مشتركة لكل صفحات المتجر، تُحسب مرة واحدة في الطلب
        View::composer(['layouts.shop', 'shop.*'], function ($view) {
            static $shared;
            $shared ??= [
                'navCategories' => Category::orderBy('sort')->get(),
                'cartCount' => Cart::count(),
                'site' => collect(['name', 'address', 'phone', 'whatsapp', 'hours', 'map_url', 'free_delivery_over', 'pharmacist_label', 'email', 'facebook_url', 'instagram_url', 'capsules'])
                    ->mapWithKeys(fn ($k) => [$k => Setting::get($k)]),
                'consultUrl' => Util::consultLink(null, url()->current()),
            ];
            $view->with($shared);
        });

        View::composer('layouts.admin', fn ($view) => $view->with([
            'pendingOrders' => Order::where('status', 'new')->count(),
            'unreadMessages' => ContactMessage::where('is_read', false)->count(),
        ]));
    }
}
