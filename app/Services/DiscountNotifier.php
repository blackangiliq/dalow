<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Product;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use App\Support\Util;
use Illuminate\Support\Facades\Log;

/**
 * عند نزول سعر منتج:
 * - من وضعه في المفضلة يصله إشعار داخل الموقع، ورسالة تيليجرام إن وافق عليها.
 * - من اختار «أخبرني بكل العروض» يصله إشعار داخل الموقع فقط (حتى لا يُزعَج برسائل كثيرة).
 */
class DiscountNotifier
{
    public function __construct(private readonly TelegramClient $telegram) {}

    public function priceDropped(Product $product, int $oldPrice): void
    {
        $was = max($oldPrice, (int) $product->old_price);
        if ($was <= $product->price) {
            return;
        }
        $percent = (int) round(100 - $product->price * 100 / $was);
        $url = route('product', $product);
        $body = 'السعر الآن '.Util::money($product->price).' بدل '.Util::money($was);

        $fans = $product->favoredBy()->get();
        foreach ($fans as $customer) {
            CustomerNotification::create([
                'customer_id' => $customer->id,
                'title' => "خصم {$percent}% على «{$product->name}» من مفضلتك",
                'body' => $body,
                'url' => $url,
            ]);
        }

        Customer::where('notify_offers', true)->whereNotIn('id', $fans->modelKeys())->pluck('id')
            ->each(fn ($id) => CustomerNotification::create([
                'customer_id' => $id,
                'title' => "عرض جديد: خصم {$percent}% على «{$product->name}»",
                'body' => $body,
                'url' => $url,
            ]));

        $toMessage = $fans->where('notify_telegram', true);
        if ($toMessage->isEmpty() || ! $this->telegram->isLinked()) {
            return;
        }
        $text = "🏷 <b>خصم {$percent}% على منتج في مفضلتك</b>\n".e($product->name)."\n{$body}\n\n{$url}";
        // بعد انتهاء الطلب الحالي، حتى لا ينتظر المدير عند حفظ السعر
        defer(function () use ($toMessage, $text) {
            foreach ($toMessage as $customer) {
                try {
                    $this->telegram->sendToPhone($customer->phone, $customer->name, $text);
                } catch (TelegramDeliveryException $e) {
                    Log::info("Telegram: لم يصل إشعار الخصم إلى {$customer->phone}: ".$e->getMessage());
                }
            }
        });
    }
}
