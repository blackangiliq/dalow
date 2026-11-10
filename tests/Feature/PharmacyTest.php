<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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

    public function test_order_is_saved_and_staff_are_notified_on_telegram(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => []])]);
        Setting::put('telegram_bot_token', '123456:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA');
        Staff::first()->update(['telegram_chat_id' => '777']);

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
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && $request['chat_id'] === '777' && str_contains($request['text'], $order->number));

        // إلغاء الطلب يعيد الكمية للمخزون
        $order->changeStatus('cancelled');
        $this->assertSame($stock, $product->fresh()->stock);
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
