<?php

namespace App\Services;

use App\Models\Setting;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * أكواد تحقق من ستة أرقام تُرسل على تيليجرام من حساب الصيدلية:
 * لتأكيد أن رقم الموبايل يخص من سجّل به، ولاستعادة كلمة المرور.
 * الكود صالح عشر دقائق وخمس محاولات، ويُحفظ مشفّراً.
 */
class PhoneCodes
{
    public function __construct(private readonly TelegramClient $telegram) {}

    public function available(): bool
    {
        return \App\Support\Ui::on('phone_verify') && $this->telegram->isLinked();
    }

    /** @throws TelegramDeliveryException إذا لم يكن الرقم على تيليجرام أو تعذر الإرسال */
    public function send(string $phone, string $purpose, string $name = 'زبون'): void
    {
        $code = (string) random_int(100000, 999999);
        Cache::put($this->key($phone, $purpose), ['hash' => Hash::make($code), 'tries' => 0], now()->addMinutes(10));

        $why = $purpose === 'reset' ? 'لتغيير كلمة مرور حسابك' : 'لتأكيد رقمك';
        $this->telegram->sendToPhone($phone, $name, "كود التحقق {$why} في ".e(Setting::get('name')).": <code>{$code}</code>\nصالح لعشر دقائق. لا تشاركه مع أحد.");
    }

    public function check(string $phone, string $purpose, string $code): bool
    {
        $key = $this->key($phone, $purpose);
        $entry = Cache::get($key);
        if (! $entry || $entry['tries'] >= 5) {
            Cache::forget($key);

            return false;
        }
        if (! Hash::check(trim($code), $entry['hash'])) {
            Cache::put($key, ['hash' => $entry['hash'], 'tries' => $entry['tries'] + 1], now()->addMinutes(10));

            return false;
        }
        Cache::forget($key);

        return true;
    }

    private function key(string $phone, string $purpose): string
    {
        return "phonecode:{$purpose}:{$phone}";
    }
}
