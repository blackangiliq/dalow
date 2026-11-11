<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount(['orders', 'favorites'])->with('healthProfiles.allergies')->latest();
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
        }

        return view('admin.customers', ['customers' => $query->paginate(30)->withQueryString()]);
    }

    /** تأكيد الرقم يدوياً بعد الاتصال بالزبون (لمن ليس عنده تيليجرام) */
    public function verify(Customer $customer)
    {
        $customer->forceFill(['phone_verified_at' => $customer->phone_verified_at ? null : now()])->save();
        if ($customer->phone_verified_at) {
            \App\Models\Order::whereNull('customer_id')->where('phone', $customer->phone)->update(['customer_id' => $customer->id]);
        }

        return back()->with('ok', $customer->phone_verified_at ? 'أُكد رقم '.$customer->name : 'أُلغي تأكيد رقم '.$customer->name);
    }

    /** كلمة مرور مؤقتة يبلغها المدير للزبون بنفسه */
    public function password(Customer $customer)
    {
        $temporary = (string) random_int(10000000, 99999999);
        $customer->forceFill(['password' => $temporary])->save();

        return back()->with('ok', "كلمة المرور الجديدة لـ{$customer->name} هي {$temporary}. أبلغه بها ليدخل ثم يغيّرها من «بياناتي».");
    }

    /** ملف الزبون الكامل: بياناته، ملفه الصحي وعائلته، تحاليله، طلباته السابقة، وما ينتظره */
    public function show(Customer $customer)
    {
        $customer->load(['healthProfiles.allergies', 'healthProfiles.documents', 'favorites', 'refillReminders.product']);
        $orders = $customer->orders()->with(['items', 'healthProfile'])->latest()->get();
        $valid = $orders->where('status', '!=', 'cancelled');

        // الأدوية التي يشتريها أكثر من مرة: دليل على استعمال مستمر
        $repeat = \App\Models\OrderItem::whereIn('order_id', $valid->pluck('id'))->whereNotNull('product_id')
            ->selectRaw('product_id, max(name) as name, count(distinct order_id) as times, sum(qty) as qty, max(created_at) as last_at')
            ->groupBy('product_id')->orderByDesc('times')->get();

        return view('admin.customer', [
            'customer' => $customer,
            'orders' => $orders,
            'stats' => [
                'orders' => $valid->count(),
                'spent' => (int) $valid->where('status', 'delivered')->sum('total'),
                'first' => $orders->last()?->created_at,
                'last' => $orders->first()?->created_at,
            ],
            'repeat' => $repeat,
            'requests' => \App\Models\ProductRequest::where('customer_id', $customer->id)->orWhere('phone', $customer->phone)->latest()->take(10)->get(),
            'messages' => \App\Models\ContactMessage::where('phone', $customer->phone)->latest()->take(10)->get(),
        ]);
    }

    public function note(Request $request, Customer $customer)
    {
        $customer->update($request->validate(['pharmacist_note' => 'nullable|string|max:2000']));

        return back()->with('ok', 'حُفظت الملاحظة الداخلية');
    }
}
