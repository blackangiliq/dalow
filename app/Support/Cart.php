<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const KEY = 'cart';

    /** @return array<int,int> product id => qty */
    public static function raw(): array
    {
        return session(self::KEY, []);
    }

    public static function add(Product $product, int $qty = 1): void
    {
        $cart = self::raw();
        $cart[$product->id] = min(($cart[$product->id] ?? 0) + max(1, $qty), max(0, $product->stock));
        if ($cart[$product->id] < 1) {
            unset($cart[$product->id]);
        }
        session([self::KEY => $cart]);
    }

    public static function set(int $productId, int $qty): void
    {
        $cart = self::raw();
        if ($qty < 1) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }
        session([self::KEY => $cart]);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    public static function count(): int
    {
        return array_sum(self::raw());
    }

    /** @return Collection<int, object{product: Product, qty: int, line: int}> */
    public static function items(): Collection
    {
        $cart = self::raw();
        if (! $cart) {
            return collect();
        }
        $products = Product::active()->with('category')->whereIn('id', array_keys($cart))->get();
        $clean = [];
        $items = $products->map(function (Product $p) use ($cart, &$clean) {
            $qty = min($cart[$p->id], $p->stock);
            if ($qty < 1) {
                return null;
            }
            $clean[$p->id] = $qty;

            return (object) ['product' => $p, 'qty' => $qty, 'line' => $qty * $p->price];
        })->filter()->values();
        session([self::KEY => $clean]);

        return $items;
    }
}
