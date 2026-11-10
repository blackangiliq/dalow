<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function toggle(Request $request, Product $product)
    {
        $customer = $request->user('customer');
        if (! $customer) {
            // بعد تسجيل الدخول يعود الزبون لصفحة المنتج نفسها
            session(['url.intended' => route('product', $product)]);
            $message = 'سجّل الدخول لتضيف المنتج للمفضلة ويصلك إشعار عند خصمه';

            return $request->expectsJson()
                ? response()->json(['login' => route('customer.login'), 'message' => $message], 401)
                : redirect()->route('customer.login')->with('error', $message);
        }

        $changes = $customer->favorites()->toggle($product->id);
        $on = (bool) $changes['attached'];
        $message = $on ? 'أضيف للمفضلة. سنخبرك عند أي خصم عليه.' : 'أزيل من المفضلة';

        return $request->expectsJson()
            ? response()->json(['on' => $on, 'message' => $message, 'count' => $customer->favorites()->count()])
            : back()->with('ok', $message);
    }
}
