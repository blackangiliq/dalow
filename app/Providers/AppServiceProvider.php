<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Setting;
use App\Models\ContactMessage;
use App\Services\Telegram\MadelineTelegramClient;
use App\Services\Telegram\TelegramClient;
use App\Support\Cart;
use App\Support\Ui;
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
        foreach (['orders' => 6, 'login' => 6, 'track' => 20, 'suggest' => 90, 'contact' => 4, 'events' => 60, 'codes' => 3] as $name => $perMinute) {
            RateLimiter::for($name, fn (Request $request) => Limit::perMinute($perMinute)->by($name.'|'.$request->ip()));
        }

        // @iqd(12500) => 12,500 د.ع
        Blade::directive('iqd', fn ($expr) => "<?php echo e(\\App\\Support\\Util::money($expr)); ?>");
        // @icon('pill')
        Blade::directive('icon', fn ($expr) => "<?php echo view('partials.icon', ['name' => $expr])->render(); ?>");

        // بيانات مشتركة لكل صفحات المتجر، تُحسب مرة واحدة في الطلب
        View::composer(['layouts.shop', 'shop.*'], function ($view) {
            // تُحسب مرة واحدة لكل طلب وتُحفظ عليه (لا على مستوى العملية، حتى لا تبقى قيم قديمة بين الطلبات)
            $request = request();
            if ($request->attributes->has('shop.shared')) {
                return $view->with($request->attributes->get('shop.shared'));
            }
            $shared = [
                'navCategories' => Category::orderBy('sort')->get(),
                'cartCount' => Cart::count(),
                'site' => collect(['name', 'address', 'phone', 'whatsapp', 'hours', 'map_url', 'pharmacist_label', 'email', 'facebook_url', 'instagram_url', 'google_verification'])
                    ->mapWithKeys(fn ($k) => [$k => Setting::get($k)]),
                'consultUrl' => Util::consultLink(null, url()->current()),
                // ما يديره المدير من صفحة «واجهة الموقع»
                'ui' => Ui::texts(),
                'on' => collect(array_merge(...array_values(Ui::FEATURES)))->map(fn ($def, $key) => Ui::on($key))->all(),
                'customColors' => Ui::customColors(),
                'freeOver' => Util::freeDeliveryOver(),
                'deliveryOn' => Util::deliveryEnabled(),
            ];
            // شريط العرض: نص المدير، وإن لم يكتب نصاً فمن إعدادات التوصيل المجاني
            $shared['promo'] = trim((string) Setting::get('promo_text'))
                ?: ($shared['freeOver'] ? 'توصيل مجاني للطلبات فوق '.number_format($shared['freeOver']).' دينار' : '');
            // بيانات الزبون المسجل
            $customer = auth('customer')->user();
            $favoriteIds = $customer ? $customer->favorites()->pluck('products.id')->all() : [];
            $shared += [
                'me' => $customer,
                'favoriteIds' => $favoriteIds,
                'favoriteCount' => count($favoriteIds),
                'unreadCount' => $customer?->notifications()->whereNull('read_at')->count() ?? 0,
            ];
            $request->attributes->set('shop.shared', $shared);
            $view->with($shared);
        });

        View::composer('layouts.admin', fn ($view) => $view->with([
            'pendingOrders' => Order::where('status', 'new')->count(),
            'unreadMessages' => ContactMessage::where('is_read', false)->count(),
            'newRequests' => \App\Models\ProductRequest::where('status', 'new')->count(),
            'pendingDocs' => \App\Support\Ui::on('health_documents') ? \App\Models\HealthDocument::whereNull('reviewed_at')->count() : 0,
        ]));
    }
}
