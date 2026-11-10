<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $items = Cart::items();

        return view('shop.cart', ['items' => $items, 'subtotal' => $items->sum('line')]);
    }

    public function add(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'qty' => 'nullable|integer|min:1|max:99']);
        $product = Product::active()->findOrFail($data['product_id']);

        if ($product->stock < 1) {
            $message = 'هذا المنتج غير متوفر حالياً';
            $ok = false;
        } else {
            Cart::add($product, $data['qty'] ?? 1);
            $message = 'أضيف «'.$product->name.'» إلى السلة';
            $ok = true;
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message, 'count' => Cart::count()]);
        }

        return back()->with($ok ? 'ok' : 'error', $message);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'qty' => 'required|integer|min:0|max:99']);
        $product = Product::active()->find($data['product_id']);
        Cart::set($data['product_id'], $product ? min($data['qty'], $product->stock) : 0);

        return redirect()->route('cart');
    }
}
