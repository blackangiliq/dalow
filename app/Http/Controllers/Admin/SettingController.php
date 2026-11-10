<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    private const KEYS = ['name', 'address', 'phone', 'whatsapp', 'hours', 'map_url', 'free_delivery_over', 'delivery_areas', 'telegram_group_chat_id'];

    public function edit(TelegramService $telegram)
    {
        $hook = $telegram->configured() ? $telegram->call('getWebhookInfo') : null;

        return view('admin.settings', [
            'values' => collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]),
            'hasToken' => $telegram->configured(),
            'webhookUrl' => $hook['result']['url'] ?? '',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'address' => 'required|string|max:160',
            'phone' => 'required|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'hours' => 'nullable|string|max:160',
            'map_url' => 'nullable|url|max:500',
            'free_delivery_over' => 'nullable|integer|min:0',
            'delivery_areas' => 'required|string|max:5000',
            'telegram_group_chat_id' => ['nullable', 'regex:/^-?\d{3,20}$/'],
            'telegram_bot_token' => ['nullable', 'regex:/^\d+:[\w-]{30,}$/'],
        ], [
            'telegram_bot_token.regex' => 'صيغة التوكن غير صحيحة. انسخه كما هو من BotFather.',
            'telegram_group_chat_id.regex' => 'معرف المجموعة يتكون من أرقام (ويبدأ غالباً بإشارة سالب)',
        ]);

        foreach (self::KEYS as $key) {
            Setting::put($key, isset($data[$key]) ? (string) $data[$key] : null);
        }
        // التوكن لا يُعرض في الصفحة، لذلك لا نغيّره إلا إذا كُتب توكن جديد
        if (! empty($data['telegram_bot_token'])) {
            Setting::put('telegram_bot_token', $data['telegram_bot_token']);
        }

        return back()->with('ok', 'حُفظت الإعدادات');
    }

    public function webhook(Request $request, TelegramService $telegram)
    {
        if ($request->input('action') === 'remove') {
            $res = $telegram->call('deleteWebhook', ['drop_pending_updates' => false]);
            $done = 'أُلغي الربط التلقائي. استخدم زر المزامنة في صفحة الموظفين.';
        } else {
            $url = route('telegram.webhook', TelegramService::webhookSecret());
            if (! str_starts_with($url, 'https://')) {
                return back()->with('error', 'الربط التلقائي يحتاج موقعاً يعمل على https. عدّل APP_URL في ملف .env بعد رفع الموقع.');
            }
            $res = $telegram->call('setWebhook', ['url' => $url, 'allowed_updates' => ['message', 'callback_query']]);
            $done = 'تم تفعيل الربط التلقائي مع تيليجرام';
        }

        return ($res['ok'] ?? false) ? back()->with('ok', $done) : back()->with('error', 'تيليجرام: '.($res['description'] ?? 'خطأ'));
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'كلمة المرور الحالية غير صحيحة',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
            'password.min' => 'كلمة المرور الجديدة 8 أحرف على الأقل',
        ]);
        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('ok', 'تغيرت كلمة المرور');
    }
}
