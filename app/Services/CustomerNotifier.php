<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\Order;
use App\Models\Product;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use App\Support\Util;
use Illuminate\Support\Facades\Log;

/**
 * إشعارات الزبون المسجل: تغيّر حالة طلبه، وتوفر منتج كان ينتظره.
 * إشعار داخل حسابه دائماً، ورسالة تيليجرام على رقمه إن وافق (وكان رقمه مؤكداً عند تشغيل خاصية التأكيد).
 */
class CustomerNotifier
{
    public function __construct(private readonly TelegramClient $telegram) {}

    public function orderStatusChanged(Order $order): void
    {
        $customer = $order->customer;
        if (! $customer) {
            return;
        }
        $text = match ($order->status) {
            'confirmed' => 'أكدنا طلبك ونبدأ تجهيزه.',
            'preparing' => 'طلبك قيد التجهيز الآن.',
            'shipping' => Util::isPickup($order->area) ? 'طلبك جاهز للاستلام من الصيدلية.' : 'طلبك خرج للتوصيل وسيصلك قريباً.',
            'delivered' => 'تم تسليم طلبك. نتمنى لك السلامة.',
            'cancelled' => 'أُلغي طلبك. إن كان ذلك خطأ فاتصل بالصيدلية.',
            default => null,
        };
        if ($text === null) {
            return;
        }
        $url = route('track', ['number' => $order->number, 'phone' => $order->phone]);
        $this->notify(collect([$customer]), "طلبك {$order->number}: {$order->status_label}", $text, $url, "📦 <b>طلبك {$order->number}</b>\n{$text}\n\n{$url}");
    }

    public function backInStock(Product $product): void
    {
        $url = route('product', $product);
        $body = 'السعر '.Util::money($product->price).'. الكمية محدودة.';
        $this->notify(
            $product->favoredBy()->get(),
            "توفر الآن: «{$product->name}»",
            $body,
            $url,
            '✅ <b>توفر المنتج الذي تنتظره</b>'."\n".e($product->name)."\n{$body}\n\n{$url}",
        );
    }

    /** @param \Illuminate\Support\Collection<int, Customer> $customers */
    private function notify($customers, string $title, string $body, string $url, string $telegramHtml): void
    {
        foreach ($customers as $customer) {
            CustomerNotification::create(['customer_id' => $customer->id, 'title' => $title, 'body' => $body, 'url' => $url]);
        }
        $toMessage = $customers->filter->can_be_messaged;
        if ($toMessage->isEmpty() || ! $this->telegram->isLinked()) {
            return;
        }
        defer(function () use ($toMessage, $telegramHtml) {
            foreach ($toMessage as $customer) {
                try {
                    $this->telegram->sendToPhone($customer->phone, $customer->name, $telegramHtml);
                } catch (TelegramDeliveryException $e) {
                    Log::info("Telegram: لم يصل إشعار الزبون {$customer->phone}: ".$e->getMessage());
                }
            }
        });
    }
}
