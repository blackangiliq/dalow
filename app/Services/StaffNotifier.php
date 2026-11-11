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

    /** @return array{sent: int, failed: int} */
    public function productRequested(\App\Models\ProductRequest $request): array
    {
        return $this->broadcast(implode("\n", array_filter([
            '🔎 <b>طلب توفير منتج غير موجود في الموقع</b>',
            '',
            '💊 '.e($request->product_name),
            $request->details ? '📝 '.e($request->details) : null,
            '',
            '👤 '.e($request->name),
            '📞 '.e($request->phone),
            $request->image ? '📎 مرفقة صورة، تجدها في لوحة التحكم.' : null,
        ], fn ($l) => $l !== null)));
    }

    /** @return array{sent: int, failed: int} */
    public function orderCancelledByCustomer(Order $order): array
    {
        return $this->broadcast('❌ <b>الزبون ألغى طلبه</b> <code>'.e($order->number)."</code>\n👤 ".e($order->customer_name).' · '.e($order->phone)."\nرجعت الكميات إلى المخزون.");
    }

    /** @return array{sent: int, failed: int} */
    public function documentUploaded(\App\Models\HealthDocument $document): array
    {
        $profile = $document->profile;
        $customer = $profile->customer;

        return $this->broadcast(implode("\n", array_filter([
            '📄 <b>زبون أرسل '.e($document->kind_label).' للقراءة</b>',
            '',
            '👤 '.e($customer->name).($profile->relation !== 'self' ? ' (لـ'.e($profile->name).')' : ''),
            '📞 '.e($customer->phone),
            '📌 '.e($document->title),
            $document->customer_note ? '📝 '.e($document->customer_note) : null,
            '',
            'اقرأه ورد عليه: '.route('admin.customers.show', $customer),
        ], fn ($l) => $l !== null)));
    }

    /**
     * أسطر الملف الصحي في إشعار الطلب: الحساسية أولاً، ثم ما يغيّر اختيار الدواء.
     *
     * @return string[]
     */
    public function patientLines(Order $order): array
    {
        $profile = $order->healthProfile;
        if (! $profile || ! \App\Support\Ui::on('health_profile')) {
            return [];
        }
        $profile->loadMissing('allergies');
        $lines = [''];
        $who = $profile->relation === 'self' ? 'الزبون' : 'المريض: '.e($profile->name).' ('.e(\App\Models\HealthProfile::RELATIONS[$profile->relation] ?? '').')';
        $lines[] = '🩺 <b>'.$who.'</b>'.($profile->age_label ? ' · العمر '.$profile->age_label : '').($profile->pregnancy ? ' · '.\App\Models\HealthProfile::PREGNANCY[$profile->pregnancy] : '');
        if ($profile->allergies->count()) {
            $lines[] = '⚠️ <b>حساسية من:</b> '.e($profile->allergies->map(fn ($a) => $a->substance.' ('.$a->severity_label.')')->implode('، '));
        } elseif ($profile->no_known_allergies) {
            $lines[] = 'لا توجد حساسية معروفة';
        }
        if ($profile->condition_labels) {
            $lines[] = 'أمراض مزمنة: '.e(implode('، ', $profile->condition_labels));
        }
        if ($order->allergy_ack) {
            $lines[] = '🚨 <b>تعارض مع الحساسية:</b> '.e($order->allergy_ack).' (أقرّ الزبون بعلمه، راجعه قبل الصرف)';
        }

        return $lines;
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
        foreach ($this->patientLines($order) as $line) {
            $lines[] = $line;
        }
        if ($order->items->count()) {
            $lines[] = '';
            $lines[] = '<b>المواد:</b>';
            foreach ($order->items as $item) {
                $lines[] = '• '.e($item->name).' × '.$item->qty.' = '.Util::money($item->price * $item->qty);
            }
            $lines[] = '';
            if ($order->discount) {
                $lines[] = 'خصم الكود '.e((string) $order->coupon_code).': -'.Util::money($order->discount);
            }
            $lines[] = Util::isPickup($order->area) ? 'استلام من الصيدلية' : 'التوصيل: '.($order->delivery_fee ? Util::money($order->delivery_fee) : 'مجاني');
            $lines[] = '<b>المجموع: '.Util::money($order->total).'</b> (الدفع عند الاستلام)';
        }
        if ($order->prescription_image) {
            $lines[] = '';
            $lines[] = '📎 مرفقة صورة وصفة، تجدها في لوحة التحكم.';
        }
        // تنبيه الموظف بما قارب على النفاد بعد هذا الطلب
        $low = \App\Models\Product::whereIn('id', $order->items->pluck('product_id')->filter())->where('stock', '<=', 5)->get();
        if ($low->count()) {
            $lines[] = '';
            foreach ($low as $product) {
                $lines[] = '⚠️ مخزون قليل: '.e($product->name).' ('.($product->stock < 1 ? 'نفد' : 'بقي '.$product->stock).')';
            }
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
