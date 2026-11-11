<?php

namespace App\Services;

use App\Models\CustomerNotification;
use App\Models\HealthDocument;
use App\Models\RefillReminder;
use App\Models\Setting;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use App\Support\Ui;
use App\Support\Util;
use Illuminate\Support\Facades\Log;

/** العناية بالزبون: تذكير إعادة الشراء، والرد على التحاليل */
class CareService
{
    public function __construct(private readonly TelegramClient $telegram) {}

    /**
     * يرسل التذكيرات المستحقة (إشعار في الحساب ورسالة على الرقم) ويحدد موعدها التالي.
     * يُستدعى يومياً من المجدول، وعند فتح الزبون حسابه، وعند فتح الصيدلاني صفحة العناية.
     */
    public function sendDueReminders(?int $customerId = null): int
    {
        if (! Ui::on('refill_reminders')) {
            return 0;
        }
        $due = RefillReminder::with(['customer', 'product'])->where('is_active', true)->where('next_at', '<=', today())
            ->when($customerId, fn ($q) => $q->where('customer_id', $customerId))->get();

        foreach ($due as $reminder) {
            $product = $reminder->product;
            $url = route('product', $product);
            CustomerNotification::create([
                'customer_id' => $reminder->customer_id,
                'title' => "حان موعد «{$product->name}»",
                'body' => 'تذكير بإعادة شراء دوائك '.$reminder->interval_label.'. اطلبه الآن قبل أن ينفد.',
                'url' => $url,
            ]);
            $reminder->update(['last_reminded_at' => now(), 'next_at' => today()->addDays($reminder->every_days)]);

            if ($reminder->customer->can_be_messaged && $this->telegram->isLinked()) {
                $this->message($reminder->customer, "💊 <b>تذكير من ".e(Setting::get('name'))."</b>\nحان موعد إعادة شراء «".e($product->name)."».\nالسعر ".Util::money($product->price).($product->stock > 0 ? '' : ' (غير متوفر الآن، سنعلمك عند توفره)')."\n\n{$url}");
            }
        }

        return $due->count();
    }

    public function documentReviewed(HealthDocument $document): void
    {
        $customer = $document->profile->customer;
        $url = route('account', ['tab' => 'health']);
        CustomerNotification::create([
            'customer_id' => $customer->id,
            'title' => 'قرأ الصيدلاني «'.$document->title.'»',
            'body' => \Illuminate\Support\Str::limit((string) $document->pharmacist_note, 140),
            'url' => $url,
        ]);
        if ($customer->can_be_messaged && $this->telegram->isLinked()) {
            $this->message($customer, '📄 <b>قرأ الصيدلاني ما أرسلته</b>: '.e($document->title)."\n\n".e((string) $document->pharmacist_note)."\n\n{$url}");
        }
    }

    private function message($customer, string $html): void
    {
        defer(function () use ($customer, $html) {
            try {
                $this->telegram->sendToPhone($customer->phone, $customer->name, $html);
            } catch (TelegramDeliveryException $e) {
                Log::info("Telegram: لم تصل رسالة العناية إلى {$customer->phone}: ".$e->getMessage());
            }
        });
    }
}
