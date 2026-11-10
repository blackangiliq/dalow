<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\StaffNotifier;
use App\Support\Cart;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function form()
    {
        $items = Cart::items();
        if ($items->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('shop.checkout', [
            'items' => $items,
            'subtotal' => $items->sum('line'),
            'areas' => Util::areas(),
            'needsRx' => $items->contains(fn ($i) => $i->product->requires_prescription),
            'customer' => auth('customer')->user(),
        ]);
    }

    public function store(Request $request, StaffNotifier $notifier)
    {
        $items = Cart::items();
        if ($items->isEmpty()) {
            return redirect()->route('cart');
        }
        $needsRx = $items->contains(fn ($i) => $i->product->requires_prescription);
        $data = $this->validateCustomer($request, $needsRx);

        $order = DB::transaction(function () use ($items, $data, $request) {
            $subtotal = 0;
            $lines = [];
            foreach ($items as $item) {
                // قفل الكمية حتى لا يباع نفس المخزون مرتين
                $product = Product::lockForUpdate()->find($item->product->id);
                if (! $product || $product->stock < $item->qty) {
                    throw ValidationException::withMessages(['cart' => 'الكمية المطلوبة من «'.$item->product->name.'» لم تعد متوفرة. راجع السلة.']);
                }
                $product->decrement('stock', $item->qty);
                $subtotal += $product->price * $item->qty;
                $lines[] = ['product_id' => $product->id, 'name' => $product->name, 'price' => $product->price, 'qty' => $item->qty];
            }
            $fee = Util::deliveryFee($data['area'], $subtotal);
            $order = Order::create($data + [
                'number' => Order::newNumber(),
                'customer_id' => auth('customer')->id(),
                'type' => 'cart',
                'prescription_image' => $request->file('prescription')?->store('prescriptions', 'local'),
                'subtotal' => $subtotal,
                'delivery_fee' => $fee,
                'total' => $subtotal + $fee,
            ]);
            $order->items()->createMany($lines);

            return $order;
        });

        Cart::clear();
        // الإشعار يُرسل بعد إظهار صفحة التأكيد للزبون حتى لا ينتظر تيليجرام
        defer(fn () => $notifier->orderPlaced($order));
        session()->push('my_orders', $order->number);

        return redirect()->route('order.done', $order->number);
    }

    public function prescriptionForm()
    {
        return view('shop.prescription', ['areas' => Util::areas(), 'customer' => auth('customer')->user()]);
    }

    public function prescriptionStore(Request $request, StaffNotifier $notifier)
    {
        $data = $this->validateCustomer($request, true);
        $order = Order::create($data + [
            'number' => Order::newNumber(),
            'customer_id' => auth('customer')->id(),
            'type' => 'prescription',
            'prescription_image' => $request->file('prescription')->store('prescriptions', 'local'),
        ]);
        // الإشعار يُرسل بعد إظهار صفحة التأكيد للزبون حتى لا ينتظر تيليجرام
        defer(fn () => $notifier->orderPlaced($order));
        session()->push('my_orders', $order->number);

        return redirect()->route('order.done', $order->number);
    }

    public function done(string $number)
    {
        // صفحة التأكيد تظهر فقط لمن أرسل الطلب من نفس المتصفح
        abort_unless(in_array($number, session('my_orders', []), true), 404);

        return view('shop.done', ['order' => Order::with('items')->where('number', $number)->firstOrFail()]);
    }

    public function track(Request $request)
    {
        $order = null;
        $searched = $request->filled('number');
        if ($searched) {
            $order = Order::with('items')
                ->where('number', strtoupper(trim(Util::digits($request->query('number')))))
                ->where('phone', Util::phone($request->query('phone')))
                ->first();
        }

        return view('shop.track', compact('order', 'searched'));
    }

    private function validateCustomer(Request $request, bool $needsRx): array
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);

        $data = $request->validate([
            'customer_name' => 'required|string|min:3|max:80',
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/'],
            'area' => ['required', Rule::in(array_keys(Util::areas()))],
            'address' => [Util::isPickup($request->input('area')) ? 'nullable' : 'required', 'string', 'min:5', 'max:255'],
            'notes' => 'nullable|string|max:500',
            'prescription' => [$needsRx ? 'required' : 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:6144'],
        ], [
            'phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567',
            'prescription.required' => 'أرفق صورة الوصفة الطبية لإكمال الطلب',
            'prescription.image' => 'الوصفة يجب أن تكون صورة (JPG أو PNG)',
            'prescription.max' => 'حجم الصورة أكبر من 6 ميغابايت',
        ], [
            'customer_name' => 'الاسم', 'phone' => 'رقم الهاتف', 'area' => 'المنطقة', 'address' => 'العنوان', 'notes' => 'الملاحظات',
        ]);
        unset($data['prescription']);
        $data['address'] = $data['address'] ?? 'يستلم من الصيدلية';

        return $data;
    }
}
