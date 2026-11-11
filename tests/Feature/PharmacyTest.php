<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Models\SearchLog;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use App\Models\Visit;
use App\Search\ProductSearch;
use App\Support\Ui;
use App\Support\Util;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PharmacyTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_storefront_pages_open(): void
    {        // كل صفحات المنتجات والأقسام تفتح، بما فيها المنتجات بلا اسم علمي أو بلا مخزون
        foreach (Product::active()->get() as $p) {
            $this->get(route('product', $p))->assertOk();
        }
        foreach (\App\Models\Category::all() as $c) {
            $this->get(route('category', $c))->assertOk();
        }
        foreach (['/products', '/products?offers=1', '/cart', '/prescription', '/track', '/faq', '/contact', '/verify', '/request', '/register', '/login', '/privacy'] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get('/')->assertOk()->assertSee('صيدلية الدلو');
        $this->get('/products?q=Paracetamol')->assertOk()->assertSee('Panadol');
        $this->get('/product/'.Product::first()->id)->assertRedirect(route('product', Product::first()));
        $this->get(route('product', Product::first()))->assertOk()->assertSee('application/ld+json', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
        $this->get('/sitemap.xml')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    /** عميل تيليجرام وهمي يسجّل ما أُرسل بدل الاتصال بتيليجرام */
    private function fakeTelegram(bool $linked = true): object
    {
        $fake = new class($linked) implements TelegramClient
        {
            public array $sent = [];

            public function __construct(private bool $linked) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function isLinked(): bool
            {
                return $this->linked;
            }

            public function linkedAccount(): ?array
            {
                return ['id' => 1, 'name' => 'صيدلية الدلو', 'username' => null, 'phone' => '9647700000712', 'linked_at' => now()->toIso8601String()];
            }

            public function sendToPhone(string $phone, string $contactName, string $message): array
            {
                if ($phone === '07800000002') {
                    throw new TelegramDeliveryException('الرقم غير مسجَّل في تيليجرام');
                }
                $this->sent[] = ['phone' => $phone, 'text' => $message];

                return ['id' => 9000 + count($this->sent), 'name' => $contactName];
            }

            public function sendToUser(int|string $userId, string $message): void
            {
                $this->sent[] = ['user' => $userId, 'text' => $message];
            }

            public function contacts(): array
            {
                return [
                    ['id' => 501, 'name' => 'مريم صالح', 'phone' => '9647711111111', 'username' => null],
                    ['id' => 502, 'name' => 'د. أحمد الدلو', 'phone' => '9647700000001', 'username' => 'ahmed'],
                ];
            }
        };
        $this->app->instance(TelegramClient::class, $fake);

        return $fake;
    }

    public function test_order_is_saved_and_chosen_staff_are_notified_on_telegram(): void
    {
        $this->withoutDefer();
        $telegram = $this->fakeTelegram();
        Staff::where('phone', '07500000003')->update(['notify' => false]);

        $product = Product::where('requires_prescription', false)->where('stock', '>', 5)->first();
        $stock = $product->stock;

        $this->post('/cart/add', ['product_id' => $product->id, 'qty' => 2]);
        $response = $this->post('/checkout', [
            'customer_name' => 'محمد علي', 'phone' => '+964 770 123 4567', 'area' => 'زيونة', 'address' => 'محلة 712 زقاق 5',
        ]);

        $order = Order::first();
        $response->assertRedirect(route('order.done', $order->number));
        $this->assertSame('07701234567', $order->phone);
        $this->assertSame($product->price * 2 + 1000, $order->total);
        $this->assertSame($stock - 2, $product->fresh()->stock);

        // وصل الإشعار للموظف المفعّل فقط، وحُفظ حسابه لاستعماله مباشرة في المرة القادمة
        $this->assertCount(1, $telegram->sent);
        $this->assertSame('07700000001', $telegram->sent[0]['phone']);
        $this->assertStringContainsString($order->number, $telegram->sent[0]['text']);
        $this->assertNotNull(Staff::where('phone', '07700000001')->first()->telegram_user_id);
        // الرقم غير المسجل في تيليجرام يظهر سببه للمدير، والموظف المعطّل لا يُراسَل
        $this->assertNotNull(Staff::where('phone', '07800000002')->first()->telegram_error);
        $this->assertNull(Staff::where('phone', '07500000003')->first()->telegram_user_id);

        // إلغاء الطلب يعيد الكمية للمخزون
        $order->changeStatus('cancelled');
        $this->assertSame($stock, $product->fresh()->stock);
    }

    public function test_staff_can_be_picked_from_telegram_contacts(): void
    {
        $this->fakeTelegram();
        $admin = User::first();

        $this->actingAs($admin)->get('/admin/staff?contacts=1')->assertOk()->assertSee('مريم صالح')->assertSee('مضاف مسبقاً');
        $this->actingAs($admin)->post('/admin/staff/import', [
            'pick' => [json_encode(['id' => 501, 'name' => 'مريم صالح', 'phone' => '07711111111'])],
        ])->assertRedirect(route('admin.staff.index'));

        $picked = Staff::where('phone', '07711111111')->first();
        $this->assertSame('501', $picked->telegram_user_id);
        $this->assertTrue($picked->notify);
    }

    public function test_consult_link_mentions_the_site_and_the_product(): void
    {
        Setting::put('whatsapp_pharmacist', '07809998877');
        $product = Product::first();
        $html = $this->get(route('product', $product))->assertOk()->assertSee('استشر الصيدلانية')->getContent();

        $this->assertStringContainsString('https://wa.me/9647809998877?text=', $html);
        $link = urldecode(\App\Support\Util::consultLink($product));
        $this->assertStringContainsString('أنا قادم من موقع صيدلية الدلو', $link);
        $this->assertStringContainsString($product->name, $link);
        $this->assertStringContainsString(route('product', $product), $link);
    }

    public function test_contact_message_is_saved_and_faq_page_opens(): void
    {
        $this->withoutDefer();
        $telegram = $this->fakeTelegram();
        $this->get('/faq')->assertOk()->assertSee('كيف أطلب من الموقع؟');
        $this->post('/contact', ['name' => 'سارة أحمد', 'phone' => '07801112223', 'body' => 'هل يتوفر عندكم دواء معين؟'])
            ->assertSessionHas('ok');

        $this->assertSame('07801112223', ContactMessage::first()->phone);
        $this->assertStringContainsString('هل يتوفر عندكم دواء معين؟', $telegram->sent[0]['text']);
    }

    public function test_search_understands_arabic_names_typos_and_symptoms(): void
    {
        $engine = app(ProductSearch::class);
        $top = fn (string $q) => Product::find($engine->search($q)['ids'][0] ?? 0)?->name;

        $this->assertStringContainsString('Panadol', $top('بنادول'));       // عربي => إنجليزي
        $this->assertStringContainsString('Augmentin', $top('اوكمنتين'));
        $this->assertStringContainsString('Glucophage', $top('كلوكوفاج'));
        $this->assertStringContainsString('Panadol', $top('Panadool'));     // خطأ إملائي
        $this->assertStringContainsString('Zithromax', $top('zithromaks'));
        $this->assertStringContainsString('Vitamin D3', $top('فيتامين د'));
        $this->assertSame('synonym', $engine->search('صداع')['via']);       // عرَض => مسكنات
        $this->assertSame([], $engine->search('qqqqzzzz')['ids']);

        // الصفحة تعرض النتيجة وتسجل البحث، والبحث بلا نتائج يعرض طلب التوفير
        $this->get('/products?q='.urlencode('بنادول'))->assertOk()->assertSee('Panadol Extra');
        $this->get('/products?q=qqqqzzzz')->assertOk()->assertSee('اطلب توفير');
        $this->assertSame(2, SearchLog::count());
        $this->assertSame(0, SearchLog::where('normalized', 'qqqqzzzz')->first()->results);
    }

    public function test_price_drop_alert_notifies_customer_and_shows_phone_to_admin(): void
    {
        $this->withoutDefer();
        $telegram = $this->fakeTelegram();
        $product = Product::where('stock', '>', 0)->whereNull('old_price')->first();

        // الزائر غير المسجل يُطلب منه تسجيل الدخول
        $this->postJson(route('favorite', $product))->assertStatus(401)->assertJsonStructure(['login']);

        $this->post('/register', ['name' => 'نور محمد', 'phone' => '0770 555 1234', 'password' => 'secret12', 'password_confirmation' => 'secret12', 'notify_telegram' => 1, 'notify_offers' => 1])
            ->assertRedirect();
        $customer = Customer::where('phone', '07705551234')->firstOrFail();
        $this->postJson(route('favorite', $product))->assertOk()->assertJson(['on' => true, 'count' => 1]);

        // المدير يخفّض السعر: يصل الزبون إشعار ورسالة على رقمه، وتظهر أرقام المنتظرين للمدير
        $old = $product->price;
        $response = $this->actingAs(User::first())->put(route('admin.products.update', $product), [
            'name' => $product->name, 'price' => $old - 500, 'old_price' => $old, 'stock' => $product->stock, 'is_active' => 1,
        ]);
        $response->assertRedirect(route('admin.price-alerts', ['product' => $product->id]));

        $this->assertSame(1, $customer->notifications()->count());
        $this->assertStringContainsString($product->name, $customer->notifications()->first()->title);
        $this->assertSame('07705551234', $telegram->sent[0]['phone']);
        $this->actingAs(User::first())->get(route('admin.price-alerts'))->assertOk()->assertSee('07705551234')->assertSee('نور محمد');
    }

    public function test_missing_product_can_be_requested_and_barcode_can_be_verified(): void
    {
        $this->withoutDefer();
        $telegram = $this->fakeTelegram();
        $this->post('/request', ['product_name' => 'Januvia 100mg', 'name' => 'سارة أحمد', 'phone' => '07801112223'])->assertSessionHas('ok');
        $this->assertSame('Januvia 100mg', ProductRequest::first()->product_name);
        $this->assertStringContainsString('Januvia 100mg', $telegram->sent[0]['text']);

        $product = Product::whereNotNull('barcode')->first();
        $this->get('/verify?code='.$product->barcode)->assertOk()->assertSee('مطابق لمنتج في قائمة الصيدلية')->assertSee($product->name);
        $this->get('/verify?code=999999999999')->assertOk()->assertSee('غير موجود في قائمتنا');
    }

    public function test_visits_are_counted_once_per_page_and_admin_sees_analytics(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) Mobile Safari');
        $this->get('/');
        $this->get('/category/adwiya');
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get('/');   // محركات البحث لا تُحسب

        $this->assertSame(2, Visit::count());
        $this->assertSame('mobile', Visit::first()->device);
        $this->assertSame('category', Visit::where('type', 'category')->first()->type);

        $this->actingAs(User::first())->get('/admin/analytics')->assertOk()->assertSee('أكثر الأقسام زيارة');
        $this->assertSame(2, Visit::count());                          // المدير لا يُحسب زائراً
    }

    public function test_admin_controls_delivery_pickup_and_free_delivery(): void
    {
        $admin = User::first();
        $base = ['name' => 'صيدلية الدلو', 'address' => 'زيونة', 'phone' => '07700000712', 'pharmacist_label' => 'الصيدلانية',
            'delivery_areas' => "زيونة|1000\nالمنصور|5000", 'pickup_label' => 'استلام من الصيدلية', 'free_delivery_over' => 20000];
        $product = Product::where('requires_prescription', false)->where('stock', '>', 5)->where('price', '<', 5000)->first();
        $order = function (string $area, int $qty = 1) use ($product) {
            $this->post('/cart/add', ['product_id' => $product->id, 'qty' => $qty]);
            $this->post('/checkout', ['customer_name' => 'محمد علي', 'phone' => '07701234567', 'area' => $area, 'address' => $area === 'استلام من الصيدلية' ? '' : 'محلة 712 زقاق 5']);

            return Order::latest('id')->first();
        };

        // توصيل واستلام وتوصيل مجاني: الكل مشغل
        $this->actingAs($admin)->put('/admin/settings', $base + ['delivery_enabled' => 1, 'pickup_enabled' => 1, 'free_delivery_enabled' => 1])->assertSessionHasNoErrors();
        auth()->logout();
        $this->get('/')->assertSee('توصيل مجاني للطلبات فوق 20,000 دينار');
        $this->assertSame(5000, $order('المنصور')->delivery_fee);
        $this->assertSame(0, $order('المنصور', 9)->delivery_fee);                 // تجاوز حد التوصيل المجاني
        $pickup = $order('استلام من الصيدلية');                                 // بلا عنوان وبلا أجور
        $this->assertSame(0, $pickup->delivery_fee);
        $this->assertSame('يستلم من الصيدلية', $pickup->address);

        // إيقاف التوصيل المجاني
        $this->actingAs($admin)->put('/admin/settings', $base + ['delivery_enabled' => 1, 'pickup_enabled' => 1])->assertSessionHasNoErrors();
        auth()->logout();
        $this->assertSame(5000, $order('المنصور', 9)->delivery_fee);
        $this->get('/')->assertDontSee('class="promo"', false);               // شريط التوصيل المجاني اختفى

        // إيقاف التوصيل كلياً: يبقى الاستلام فقط
        $this->actingAs($admin)->put('/admin/settings', $base + ['pickup_enabled' => 1])->assertSessionHasNoErrors();
        auth()->logout();
        $this->assertSame(['استلام من الصيدلية'], array_keys(Util::areas()));
        $count = Order::count();
        $this->post('/cart/add', ['product_id' => $product->id]);
        $this->post('/checkout', ['customer_name' => 'محمد علي', 'phone' => '07701234567', 'area' => 'المنصور', 'address' => 'محلة 712 زقاق 5'])->assertSessionHasErrors('area');
        $this->assertSame($count, Order::count());

        // لا يمكن إيقاف الاثنين معاً
        $this->actingAs($admin)->put('/admin/settings', $base)->assertSessionHasErrors('delivery_enabled');
    }

    public function test_admin_controls_what_the_storefront_shows(): void
    {
        $admin = User::first();
        $this->get('/')->assertSee('دواؤك من صيدلية الدلو يوصلك لباب بيتك')->assertSee('استشر الصيدلانية')->assertSee('تحقق من دوائك');

        $sections = array_keys(Ui::SECTIONS);
        $payload = [
            'text' => ['hero_title' => 'صيدليتك في جيبك', 'assure1_title' => 'نوصل خلال ساعة'],
            'color' => ['water' => '#7b2cbf', 'navy' => '#0b2545', 'buy' => '#f4b63f', 'coral' => '#d8452b'],
            // كل الخصائص مشغلة عدا الاستشارة والتحقق والسلة
            'on' => collect(array_merge(...array_values(Ui::FEATURES)))->keys()->diff(['consult', 'verify', 'cart'])->mapWithKeys(fn ($k) => [$k => 1])->all(),
            // كل الأقسام ظاهرة عدا «كيف تطلب»، و«زورنا» أولاً
            'section_on' => collect($sections)->diff(['steps'])->mapWithKeys(fn ($k) => [$k => 1])->all(),
            'section_order' => collect($sections)->mapWithKeys(fn ($k, $i) => [$k => $k === 'visit' ? 0 : $i + 1])->all(),
        ];
        $this->actingAs($admin)->put('/admin/appearance', $payload)->assertSessionHasNoErrors()->assertSessionHas('ok');
        auth()->logout();

        $html = $this->get('/')->assertOk()
            ->assertSee('صيدليتك في جيبك')->assertSee('نوصل خلال ساعة')
            ->assertDontSee('دواؤك من صيدلية الدلو يوصلك لباب بيتك')
            ->assertDontSee('data-track="consult"', false)->assertDontSee('تحقق من دوائك')->assertDontSee('كيف تطلب')->assertDontSee('أضف للسلة')
            ->assertSee('--water: #7b2cbf', false)->getContent();
        $this->assertLessThan(strpos($html, 'data-section="cats"'), strpos($html, 'data-section="visit"'));   // «زورنا» قبل الأقسام

        // الصفحات الموقوفة تُغلق
        $this->get('/verify')->assertNotFound();
        $this->get('/cart')->assertNotFound();
        $this->get('/faq')->assertOk();
        $this->get(route('product', Product::first()))->assertOk()->assertSee('اطلب عبر واتساب')->assertDontSee('أضف للسلة');

        // الرجوع للنصوص الافتراضية
        $this->actingAs($admin)->post('/admin/appearance/reset', ['what' => 'texts']);
        auth()->logout();
        $this->get('/')->assertSee('دواؤك من صيدلية الدلو يوصلك لباب بيتك');
    }

    public function test_coupon_discounts_the_order_and_respects_its_limits(): void
    {
        $product = Product::where('requires_prescription', false)->where('stock', '>', 10)->where('price', '>=', 4000)->first();
        Coupon::create(['code' => 'TEN', 'type' => 'percent', 'value' => 10, 'min_total' => 5000, 'max_uses' => 1]);

        $this->post('/cart/add', ['product_id' => $product->id, 'qty' => 2]);
        $this->post('/cart/coupon', ['code' => 'nope'])->assertSessionHasErrors('code');
        $this->post('/cart/coupon', ['code' => ' ten '])->assertSessionHasNoErrors();   // بأي حالة أحرف ومع فراغات
        $subtotal = $product->price * 2;
        $this->get('/cart')->assertSee('خصم 10%');

        $this->post('/checkout', ['customer_name' => 'محمد علي', 'phone' => '07701234567', 'area' => 'زيونة', 'address' => 'محلة 712 زقاق 5']);
        $order = Order::latest('id')->first();
        $this->assertSame('TEN', $order->coupon_code);
        $this->assertSame((int) round($subtotal * 0.1), $order->discount);
        $this->assertSame($subtotal - $order->discount + 1000, $order->total);
        $this->assertSame(1, Coupon::where('code', 'TEN')->first()->used);

        // استُنفد الكود: لا يُطبق مرة ثانية
        $this->post('/cart/add', ['product_id' => $product->id, 'qty' => 2]);
        $this->post('/cart/coupon', ['code' => 'TEN'])->assertSessionHasErrors('code');
    }

    public function test_expired_products_disappear_and_expiry_is_imported(): void
    {
        $product = Product::where('stock', '>', 0)->whereNull('expires_at')->first();
        $this->get(route('product', $product))->assertOk();

        $product->update(['expires_at' => today()->subDay()]);
        $this->get(route('product', $product))->assertNotFound();
        $this->assertNotContains($product->id, app(ProductSearch::class)->search($product->name)['ids']);
        $this->actingAs(User::first())->get('/admin')->assertSee('انتهت صلاحيته');

        // صيغ التاريخ الشائعة في قوائم المذاخر
        $this->assertSame('2028-05-31', \App\Http\Controllers\Admin\ImportController::date('05/2028'));
        $this->assertSame('2027-03-09', \App\Http\Controllers\Admin\ImportController::date('9/3/2027'));
        $this->assertSame('2027-12-01', \App\Http\Controllers\Admin\ImportController::date('2027-12-01'));
        $this->assertNull(\App\Http\Controllers\Admin\ImportController::date('غير معروف'));
        $this->actingAs(User::first())->get('/admin/products/export')->assertOk();
    }

    public function test_customer_is_told_about_order_status_and_restock_and_can_cancel(): void
    {
        $this->withoutDefer();
        $telegram = $this->fakeTelegram();
        $this->post('/register', ['name' => 'نور محمد', 'phone' => '07705551234', 'password' => 'secret12', 'password_confirmation' => 'secret12', 'notify_telegram' => 1]);
        $customer = Customer::first();

        $product = Product::where('requires_prescription', false)->where('stock', '>', 5)->first();
        $this->post('/cart/add', ['product_id' => $product->id]);
        $this->post('/checkout', ['customer_name' => 'نور محمد', 'phone' => '07705551234', 'area' => 'زيونة', 'address' => 'محلة 712 زقاق 5']);
        $order = Order::latest('id')->first();
        $this->assertSame($customer->id, $order->customer_id);

        // المدير يؤكد الطلب: إشعار في الحساب ورسالة على الرقم
        $telegram->sent = [];
        $order->changeStatus('confirmed', 'المدير');
        $this->assertStringContainsString($order->number, $customer->notifications()->latest('id')->first()->title);
        $this->assertSame('07705551234', $telegram->sent[0]['phone']);

        // بعد التأكيد لا يستطيع الزبون الإلغاء من حسابه
        $this->post(route('account.cancel', $order))->assertSessionHas('error');
        $this->assertSame('confirmed', $order->fresh()->status);

        // طلب جديد يلغيه الزبون فترجع الكمية
        $stock = $product->fresh()->stock;
        $this->post('/cart/add', ['product_id' => $product->id]);
        $this->post('/checkout', ['customer_name' => 'نور محمد', 'phone' => '07705551234', 'area' => 'زيونة', 'address' => 'محلة 712 زقاق 5']);
        $second = Order::latest('id')->first();
        $this->post(route('account.cancel', $second))->assertSessionHas('ok');
        $this->assertSame('cancelled', $second->fresh()->status);
        $this->assertSame($stock, $product->fresh()->stock);

        // منتج نافد في قائمته يرجع للمخزون
        $out = Product::where('stock', 0)->first();
        $customer->favorites()->attach($out->id);
        $out->update(['stock' => 12]);
        $this->assertStringContainsString('توفر الآن', $customer->notifications()->latest('id')->first()->title);
    }

    public function test_phone_is_verified_by_code_and_password_can_be_reset(): void
    {
        $telegram = $this->fakeTelegram();
        Setting::put('on_phone_verify', '1');
        $code = fn () => preg_match('/<code>(\d{6})<\/code>/', end($telegram->sent)['text'], $m) ? $m[1] : null;

        // طلب قديم بنفس الرقم قبل التسجيل
        $guestOrder = Order::create(['number' => 'D100001', 'customer_name' => 'نور', 'phone' => '07705551234', 'area' => 'زيونة', 'address' => 'محلة 712']);

        $this->post('/register', ['name' => 'نور محمد', 'phone' => '07705551234', 'password' => 'secret12', 'password_confirmation' => 'secret12', 'notify_telegram' => 1])
            ->assertRedirect(route('customer.verify'));
        $customer = Customer::first();
        $this->assertNull($customer->phone_verified_at);
        $this->assertFalse($customer->can_be_messaged);              // لا رسائل لرقم غير مؤكد
        $this->assertNull($guestOrder->fresh()->customer_id);        // ولا تُكشف له طلبات الرقم

        $this->post('/phone/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/phone/verify', ['code' => $code()])->assertRedirect();
        $this->assertNotNull($customer->fresh()->phone_verified_at);
        $this->assertSame($customer->id, $guestOrder->fresh()->customer_id);

        // استعادة كلمة المرور بكود
        $this->post('/logout');
        $this->post('/forgot', ['phone' => '07705551234'])->assertRedirect(route('customer.forgot'));
        $this->post('/forgot/reset', ['code' => $code(), 'password' => 'newpass99', 'password_confirmation' => 'newpass99'])->assertRedirect(route('account'));
        $this->post('/logout');
        $this->post('/login', ['phone' => '07705551234', 'password' => 'newpass99'])->assertRedirect(route('account'));

        // رقم غير مسجل: الرد نفسه ولا يُرسل شيء
        $count = count($telegram->sent);
        $this->post('/logout');
        $this->post('/forgot', ['phone' => '07809990000'])->assertSessionHas('ok');
        $this->assertCount($count, $telegram->sent);
    }

    public function test_staff_role_is_limited_and_last_admin_is_protected(): void
    {
        $admin = User::first();
        $this->actingAs($admin)->post('/admin/users', ['name' => 'موظف الكاشير', 'email' => 'staff@aldalu.iq', 'password' => 'password12', 'role' => 'staff'])->assertSessionHas('ok');
        $staff = User::where('email', 'staff@aldalu.iq')->first();

        foreach (['/admin', '/admin/orders', '/admin/products', '/admin/customers', '/admin/price-alerts', '/admin/requests', '/admin/messages', '/admin/profile'] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }
        foreach (['/admin/settings', '/admin/analytics', '/admin/appearance', '/admin/users', '/admin/telegram', '/admin/coupons', '/admin/import', '/admin/staff'] as $url) {
            $this->actingAs($staff)->get($url)->assertRedirect(route('admin.dashboard'));
        }
        $this->actingAs($staff)->get('/admin')->assertDontSee('الإعدادات</a>', false);

        // المدير الوحيد لا يُحذف ولا يُنزَّل
        $this->actingAs($admin)->put(route('admin.users.update', $admin), ['role' => 'staff'])->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_storefront_gaps_error_pages_bots_and_pickup_texts(): void
    {
        // صفحة خطأ عربية
        $this->get('/no-such-page')->assertNotFound()->assertSee('هذه الصفحة غير موجودة');

        // رسالة آلية (ملأت الحقل المخفي) لا تُحفظ
        $this->post('/contact', ['name' => 'Bot', 'phone' => '07701234567', 'body' => 'spam spam spam', 'website' => 'http://spam.example'])->assertSessionHas('ok');
        $this->assertSame(0, ContactMessage::count());

        // ترويسات الأمان وملف التثبيت
        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('display', 'standalone');

        // عند إيقاف التوصيل تتبدل النصوص الافتراضية تلقائياً، ونص المدير الخاص يبقى
        $this->get('/')->assertSee('يوصلك لباب بيتك')->assertSee('توصيل لكل بغداد');
        Setting::put('delivery_enabled', '0');
        Setting::put('ui_assure3_title', 'أدوية مضمونة');
        $this->get('/')->assertDontSee('يوصلك لباب بيتك')->assertDontSee('توصيل لكل بغداد')
            ->assertSee('واستلمه جاهزاً')->assertSee('استلام من الصيدلية')->assertSee('أدوية مضمونة');
    }

    public function test_prescription_medicine_needs_a_prescription_image(): void
    {
        $product = Product::where('requires_prescription', true)->where('stock', '>', 0)->first();
        $this->post('/cart/add', ['product_id' => $product->id]);
        $this->post('/checkout', [
            'customer_name' => 'محمد علي', 'phone' => '07701234567', 'area' => 'زيونة', 'address' => 'محلة 712 زقاق 5',
        ])->assertSessionHasErrors('prescription');
        $this->assertSame(0, Order::count());
    }

    public function test_excel_import_with_chosen_columns(): void
    {
        Storage::fake('local');
        $sheet = new Spreadsheet;
        $sheet->getActiveSheet()->fromArray([
            ['Code', 'المادة', 'سعر البيع', 'الرصيد', 'الصنف'],
            ['900001', 'Test Drug 500mg', '3,500 د.ع', 20, 'قسم جديد'],
            ['900002', 'Panadol Extra 24 قرص', 9999, 7, 'أدوية'],
            ['900003', '', 100, 1, ''],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new Xlsx($sheet))->save($path);

        $admin = User::first();
        $this->actingAs($admin)
            ->post('/admin/import', ['file' => new UploadedFile($path, 'list.xlsx', null, null, true)])
            ->assertRedirect(route('admin.import.map'));
        $this->actingAs($admin)->get('/admin/import/map')->assertOk()->assertSee('Test Drug 500mg');

        $this->actingAs($admin)->post('/admin/import/run', [
            'map' => ['name' => 1, 'price' => 2, 'stock' => 3, 'category' => 4, 'barcode' => 0],
            'has_header' => 1, 'create_categories' => 1, 'match_by' => 'barcode', 'on_duplicate' => 'update',
        ])->assertOk()->assertSee('السطر 4');

        $new = Product::where('name', 'Test Drug 500mg')->first();
        $this->assertSame(3500, $new->price);
        $this->assertSame(20, $new->stock);
        $this->assertSame('قسم جديد', $new->category->name);
        $this->assertSame(9999, Product::where('name', 'Panadol Extra 24 قرص')->first()->price);
        $this->assertSame(1, Product::where('name', 'Panadol Extra 24 قرص')->count());
    }
}
