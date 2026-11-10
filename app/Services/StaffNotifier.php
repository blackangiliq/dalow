<?php

namespace App\Services;

use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Staff;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use App\Support\Util;
use Illuminate\Support\Facades\Log;

/**
 * يرسل إشعارات الموقع (طلب جديد، رسالة تواصل) إلى الموظفين المختارين عبر حساب تيليجرام المرتبط.
 */
class StaffNotifier
{
    public function __construct(private readonly TelegramClient $telegram) {}

    public function ready(): bool
    {
        return $this->telegram->isLinked();
    }

    /** @return array{sent: int, failed: int} */
    public function orderPlaced(Order $order): array
    {
        return $this->broadcast($this->orderText($order));
    }

    /** @return array{sent: int, failed: int} */
    public function contactReceived(ContactMessage $message): array
    {
        return $this->broadcast(implode("\n", array_filter([
            '✉️ <b>رسالة جديدة من صفحة تواصل معنا</b>',
            '',
            '👤 '.e($message->name),
            '📞 '.e($message->phone),
            $message->subject ? '📌 '.e($message->subject) : null,
            '',
            e($message->body),
        ], fn ($l) => $l !== null)));
    }

    /**
     * يرسل رسالة لموظف واحد ويحفظ نتيجة التسليم عليه.
     *
     * @throws TelegramDeliveryException
     */
    public function sendTo(Staff $staff, string $html): void
    {
        try {
            if ($staff->telegram_user_id) {
                $this->telegram->sendToUser($staff->telegram_user_id, $html);
            } else {
                $account = $this->telegram->sendToPhone($staff->phone, $staff->name, $html);
                $staff->telegram_user_id = (string) $account['id'];
                $staff->telegram_name = $account['name'];
            }
            $staff->telegram_error = null;
            $staff->save();
        } catch (TelegramDeliveryException $e) {
            // قد يكون المعرف المحفوظ قديماً: نمسحه لنعيد البحث بالرقم في المرة القادمة
            $staff->forceFill(['telegram_user_id' => null, 'telegram_error' => mb_substr($e->getMessage(), 0, 250)])->save();
            throw $e;
        }
    }

    public function orderText(Order $order): string
    {
        $order->loadMissing('items');
        $lines = [];
        $lines[] = $order->type === 'prescription' ? '📋 <b>طلب جديد بوصفة طبية</b>' : '🛒 <b>طلب جديد من الموقع</b>';
        $lines[] = 'رقم الطلب: <code>'.e($order->number).'</code>';
        $lines[] = '';
        $lines[] = '👤 '.e($order->customer_name);
        $lines[] = '📞 '.e($order->phone);
        $lines[] = '📍 '.e($order->area).'، '.e($order->address);
        if ($order->notes) {
            $lines[] = '📝 '.e($order->notes);
        }
        if ($order->items->count()) {
            $lines[] = '';
            $lines[] = '<b>المواد:</b>';
            foreach ($order->items as $item) {
                $lines[] = '• '.e($item->name).' × '.$item->qty.' = '.Util::money($item->price * $item->qty);
            }
            $lines[] = '';
            $lines[] = 'التوصيل: '.($order->delivery_fee ? Util::money($order->delivery_fee) : 'مجاني');
            $lines[] = '<b>المجموع: '.Util::money($order->total).'</b> (الدفع عند الاستلام)';
        }
        if ($order->prescription_image) {
            $lines[] = '';
            $lines[] = '📎 مرفقة صورة وصفة، تجدها في لوحة التحكم.';
        }
        $lines[] = '';
        $lines[] = 'فتح الطلب: '.route('admin.orders.show', $order);

        return implode("\n", $lines);
    }

    /** @return array{sent: int, failed: int} */
    private function broadcast(string $html): array
    {
        $result = ['sent' => 0, 'failed' => 0];
        if (! $this->ready()) {
            return $result;
        }
        foreach (Staff::where('notify', true)->get() as $staff) {
            try {
                $this->sendTo($staff, $html);
                $result['sent']++;
            } catch (TelegramDeliveryException $e) {
                $result['failed']++;
                Log::warning("Telegram: تعذر إشعار {$staff->name}: ".$e->getMessage());
            }
        }

        return $result;
    }
}
