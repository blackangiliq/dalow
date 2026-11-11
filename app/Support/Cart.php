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
        session()->forget([self::KEY, 'coupon']);
    }

    /**
     * كود الخصم المطبق على السلة إن كان ما زال صالحاً لمجموعها.
     *
     * @return array{coupon: ?\App\Models\Coupon, discount: int}
     */
    public static function coupon(int $subtotal): array
    {
        $coupon = \App\Support\Ui::on('coupons') ? \App\Models\Coupon::findByCode(session('coupon')) : null;
        if (! $coupon || $coupon->rejection($subtotal)) {
            return ['coupon' => null, 'discount' => 0];
        }

        return ['coupon' => $coupon, 'discount' => $coupon->discountFor($subtotal)];
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

    /**
     * تعارض مواد السلة مع حساسيات ملفات الزبون (هو وعائلته).
     *
     * @return array<int, array<int, array{profile: \App\Models\HealthProfile, substance: string, via: ?string}>> رقم المنتج => التعارضات
     */
    public static function allergyConflicts(Collection $items): array
    {
        $customer = auth('customer')->user();
        if (! $customer || ! \App\Support\Ui::on('health_profile') || ! \App\Support\Ui::on('allergy_alerts')) {
            return [];
        }
        $profiles = $customer->healthProfiles()->with('allergies')->get()->filter(fn ($p) => $p->allergies->isNotEmpty());
        $out = [];
        foreach ($items as $item) {
            foreach ($profiles as $profile) {
                foreach (\App\Support\AllergyCheck::conflicts($profile, $item->product) as $hit) {
                    $out[$item->product->id][] = ['profile' => $profile, 'substance' => $hit['allergy']->substance, 'via' => $hit['via']];
                }
            }
        }

        return $out;
    }
}
