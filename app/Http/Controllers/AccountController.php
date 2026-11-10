<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Support\Cart;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user('customer');
        $tab = in_array($request->query('tab'), ['favorites', 'notifications', 'profile'], true) ? $request->query('tab') : 'orders';

        $data = match ($tab) {
            'favorites' => ['favorites' => $customer->favorites()->with('category')->latest('favorites.created_at')->get()],
            'notifications' => ['notifications' => $customer->notifications()->latest()->take(50)->get()],
            'profile' => ['areas' => Util::areas()],
            default => ['orders' => $customer->orders()->with('items')->latest()->take(30)->get()],
        };
        if ($tab === 'notifications') {
            // فتح الصفحة يعني أن الزبون رآها
            $customer->notifications()->whereNull('read_at')->update(['read_at' => now()]);
        }

        return view('shop.account', $data + ['customer' => $customer, 'tab' => $tab]);
    }

    public function update(Request $request)
    {
        $customer = $request->user('customer');
        $data = $request->validate([
            'name' => 'required|string|min:3|max:80',
            'area' => ['nullable', Rule::in(array_keys(Util::areas()))],
            'address' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6|confirmed',
        ], ['password.min' => 'كلمة المرور 6 أحرف على الأقل', 'password.confirmed' => 'تأكيد كلمة المرور غير مطابق']);

        if (empty($data['password'])) {
            unset($data['password']);
        }
        $customer->update($data + [
            'notify_offers' => $request->boolean('notify_offers'),
            'notify_telegram' => $request->boolean('notify_telegram'),
        ]);

        return back()->with('ok', 'حُفظت بياناتك');
    }

    /** يعيد مواد طلب سابق إلى السلة بضغطة واحدة (مفيد لأدوية الأمراض المزمنة) */
    public function reorder(Request $request, Order $order)
    {
        abort_unless($order->customer_id === $request->user('customer')->id, 404);

        $added = 0;
        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::active()->find($item->product_id) : null;
            if ($product && $product->stock > 0) {
                Cart::add($product, $item->qty);
                $added++;
            }
        }
        $missing = $order->items->count() - $added;

        return redirect()->route('cart')->with(
            $added ? 'ok' : 'error',
            $added ? "أضيفت {$added} مادة إلى السلة".($missing ? "، و{$missing} غير متوفرة حالياً" : '') : 'مواد هذا الطلب غير متوفرة حالياً'
        );
    }
}
