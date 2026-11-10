<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
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
    {
        $this->get('/')->assertOk()->assertSee('صيدلية الدلو');
        $this->get('/products?q=Paracetamol')->assertOk()->assertSee('Panadol');
        $this->get('/product/'.Product::first()->id)->assertOk();
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
        $html = $this->get('/product/'.$product->id)->assertOk()->assertSee('استشر الصيدلانية')->getContent();

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
