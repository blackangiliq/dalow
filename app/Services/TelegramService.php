<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Staff;
use App\Support\Util;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    public function configured(): bool
    {
        return (bool) Setting::get('telegram_bot_token');
    }

    /** مفتاح سري ضمن رابط الويب هوك حتى لا يستقبل الموقع تحديثات مزيفة */
    public static function webhookSecret(): string
    {
        return substr(hash('sha256', 'telegram|'.config('app.key')), 0, 40);
    }

    public function call(string $method, array $params = []): array
    {
        $token = Setting::get('telegram_bot_token');
        if (! $token) {
            return ['ok' => false, 'description' => 'لم يتم إدخال توكن البوت بعد'];
        }
        try {
            $http = Http::timeout(8)->connectTimeout(5);
            $url = "https://api.telegram.org/bot{$token}/{$method}";
            $response = $params ? $http->asJson()->post($url, $params) : $http->get($url);

            return $response->json() ?? ['ok' => false, 'description' => 'رد غير مفهوم من تيليجرام'];
        } catch (\Throwable $e) {
            Log::warning('Telegram: '.$e->getMessage());

            return ['ok' => false, 'description' => 'تعذر الاتصال بتيليجرام'];
        }
    }

    public function send(string|int $chatId, string $html, ?array $markup = null): array
    {
        return $this->call('sendMessage', array_filter([
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
            'reply_markup' => $markup,
        ], fn ($v) => $v !== null));
    }

    /** @return string[] معرفات المحادثات التي تستلم الإشعارات (الموظفون + المجموعة إن وجدت) */
    public function recipients(): array
    {
        $ids = Staff::where('notify', true)->whereNotNull('telegram_chat_id')->pluck('telegram_chat_id')->all();
        if ($group = trim((string) Setting::get('telegram_group_chat_id'))) {
            $ids[] = $group;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public function broadcast(string $html, ?array $markup = null): int
    {
        $sent = 0;
        foreach ($this->recipients() as $chatId) {
            $sent += ($this->send($chatId, $html, $markup)['ok'] ?? false) ? 1 : 0;
        }

        return $sent;
    }

    public function notifyOrder(Order $order): int
    {
        if (! $this->configured()) {
            return 0;
        }
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
        $url = route('admin.orders.show', $order);
        if (! str_contains($url, 'localhost') && ! str_contains($url, '127.0.0.1')) {
            $lines[] = '';
            $lines[] = '<a href="'.e($url).'">فتح الطلب في لوحة التحكم</a>';
        }

        $markup = ['inline_keyboard' => [[
            ['text' => '✅ تأكيد الطلب', 'callback_data' => "o:{$order->id}:confirmed"],
            ['text' => '❌ إلغاء', 'callback_data' => "o:{$order->id}:cancelled"],
        ]]];

        return $this->broadcast(implode("\n", $lines), $markup);
    }

    /** جلب التحديثات يدوياً (بديل عن الويب هوك عند العمل محلياً) */
    public function sync(): array
    {
        $offset = (int) Setting::get('telegram_update_offset', 0);
        $res = $this->call('getUpdates', ['offset' => $offset, 'timeout' => 0, 'allowed_updates' => ['message', 'callback_query']]);
        if (! ($res['ok'] ?? false)) {
            return ['ok' => false, 'message' => $res['description'] ?? 'فشل جلب التحديثات'];
        }
        $linked = 0;
        foreach ($res['result'] as $update) {
            $linked += $this->processUpdate($update) ? 1 : 0;
            $offset = $update['update_id'] + 1;
        }
        Setting::put('telegram_update_offset', (string) $offset);

        return ['ok' => true, 'message' => 'تمت المزامنة. عدد الموظفين الذين تم ربطهم: '.$linked];
    }

    /** يعالج تحديثاً واحداً من تيليجرام. يرجع true إذا تم ربط موظف جديد. */
    public function processUpdate(array $update): bool
    {
        if ($cb = $update['callback_query'] ?? null) {
            $this->handleCallback($cb);

            return false;
        }

        $msg = $update['message'] ?? null;
        if (! $msg || ($msg['chat']['type'] ?? '') !== 'private') {
            return false;
        }
        $chatId = (string) $msg['chat']['id'];

        if ($contact = $msg['contact'] ?? null) {
            // نقبل فقط جهة الاتصال الخاصة بالمرسل نفسه
            if ((string) ($contact['user_id'] ?? '') !== (string) ($msg['from']['id'] ?? '-')) {
                $this->send($chatId, 'يرجى مشاركة رقمك أنت عبر الزر، وليس جهة اتصال أخرى.');

                return false;
            }
            $staff = Staff::where('phone', Util::phone($contact['phone_number'] ?? ''))->first();
            if (! $staff) {
                $this->send($chatId, "رقمك غير مسجل ضمن موظفي الصيدلية.\nاطلب من المدير إضافة رقمك من لوحة التحكم ثم أعد المحاولة.", ['remove_keyboard' => true]);

                return false;
            }
            $staff->update(['telegram_chat_id' => $chatId]);
            $this->send($chatId, '✅ أهلاً '.e($staff->name)."، تم ربط حسابك.\nستصلك إشعارات طلبات ".e(Setting::get('name')).' هنا.', ['remove_keyboard' => true]);

            return true;
        }

        $this->send(
            $chatId,
            'مرحباً بك في بوت إشعارات '.e(Setting::get('name')).".\nاضغط الزر أدناه لمشاركة رقم هاتفك وربط حسابك كموظف.\n\nمعرف محادثتك: <code>{$chatId}</code>",
            ['keyboard' => [[['text' => '📱 مشاركة رقم هاتفي', 'request_contact' => true]]], 'resize_keyboard' => true, 'one_time_keyboard' => true]
        );

        return false;
    }

    private function handleCallback(array $cb): void
    {
        $fromId = (string) ($cb['from']['id'] ?? '');
        $staff = Staff::where('telegram_chat_id', $fromId)->first();
        $answer = fn (string $text) => $this->call('answerCallbackQuery', ['callback_query_id' => $cb['id'], 'text' => $text]);

        if (! $staff) {
            $answer('حسابك غير مربوط كموظف');

            return;
        }
        if (! preg_match('/^o:(\d+):(confirmed|cancelled)$/', $cb['data'] ?? '', $m) || ! ($order = Order::find($m[1]))) {
            $answer('الطلب غير موجود');

            return;
        }
        if ($order->status !== 'new') {
            $answer('الطلب تمت معالجته مسبقاً ('.$order->status_label.')');

            return;
        }
        $order->changeStatus($m[2], $staff->name);
        $answer('تم');
        $verb = $m[2] === 'confirmed' ? '✅ تم تأكيد' : '❌ تم إلغاء';
        $this->broadcast("{$verb} الطلب <code>".e($order->number).'</code> بواسطة '.e($staff->name));
    }
}
