<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Staff;
use App\Services\Telegram\MadelineTelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use Illuminate\Http\Request;

/**
 * ربط حساب تيليجرام الذي يرسل إشعارات الطلبات.
 * خطوة الربط الحالية (كود / كلمة مرور) تُحفظ في جلسة المتصفح، أما حالة الدخول الفعلية
 * فمحفوظة في ملف جلسة MadelineProto وتبقى صحيحة بين الطلبات.
 */
class TelegramController extends Controller
{
    private const STEP = 'telegram_login_step';

    public function index(MadelineTelegramClient $client)
    {
        return view('admin.telegram', [
            'configured' => $client->isConfigured(),
            'linked' => $client->isLinked(),
            'account' => $client->linkedAccount(),
            'step' => session(self::STEP),
            'apiId' => Setting::get('tg_api_id'),
            'hasHash' => filled(Setting::get('tg_api_hash')),
            'recipients' => Staff::where('notify', true)->count(),
        ]);
    }

    public function credentials(Request $request)
    {
        $data = $request->validate([
            'tg_api_id' => ['required', 'regex:/^\d{4,12}$/'],
            'tg_api_hash' => [filled(Setting::get('tg_api_hash')) ? 'nullable' : 'required', 'regex:/^[a-f0-9]{32}$/i'],
        ], [
            'tg_api_id.regex' => 'API ID رقم فقط، مثل 12345678',
            'tg_api_hash.regex' => 'API Hash يتكون من 32 حرفاً ورقماً. انسخه كما هو من my.telegram.org',
        ], ['tg_api_id' => 'API ID', 'tg_api_hash' => 'API Hash']);

        Setting::put('tg_api_id', $data['tg_api_id']);
        if (! empty($data['tg_api_hash'])) {
            Setting::put('tg_api_hash', strtolower($data['tg_api_hash']));
        }

        return back()->with('ok', 'حُفظت بيانات الاتصال. الآن اربط الحساب برقم الهاتف.');
    }

    public function link(Request $request, MadelineTelegramClient $client)
    {
        $data = $request->validate(
            ['phone' => ['required', 'regex:/^\+\d{10,15}$/']],
            ['phone.regex' => 'اكتب الرقم بالصيغة الدولية، مثل +9647701234567'],
        );

        try {
            $client->startLogin($data['phone']);
        } catch (TelegramDeliveryException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        session([self::STEP => 'code', 'telegram_login_phone' => $data['phone']]);

        return back()->with('ok', 'أرسل تيليجرام كود التحقق إلى تطبيق تيليجرام على هذا الرقم.');
    }

    public function verifyCode(Request $request, MadelineTelegramClient $client)
    {
        $data = $request->validate(['code' => ['required', 'regex:/^\d{4,8}$/']], ['code.regex' => 'الكود أرقام فقط'], ['code' => 'الكود']);

        try {
            $state = $client->completeLogin($data['code']);
        } catch (TelegramDeliveryException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($state === 'signup_required') {
            session()->forget(self::STEP);

            return back()->with('error', 'هذا الرقم لا يملك حساب تيليجرام. أنشئ الحساب من تطبيق تيليجرام أولاً.');
        }
        if ($state === 'password_required') {
            session([self::STEP => 'password']);

            return back()->with('ok', 'الحساب محمي بالتحقق بخطوتين. أدخل كلمة المرور لإكمال الربط.');
        }
        session()->forget(self::STEP);

        return back()->with('ok', 'تم ربط حساب تيليجرام. اختر الآن من يستلم الإشعارات من صفحة الموظفين.');
    }

    public function verifyPassword(Request $request, MadelineTelegramClient $client)
    {
        $data = $request->validate(['password' => 'required|string'], [], ['password' => 'كلمة مرور التحقق بخطوتين']);

        try {
            $client->completePasswordLogin($data['password']);
        } catch (TelegramDeliveryException $e) {
            return back()->with('error', $e->getMessage());
        }
        session()->forget(self::STEP);

        return back()->with('ok', 'تم ربط حساب تيليجرام. اختر الآن من يستلم الإشعارات من صفحة الموظفين.');
    }

    public function cancel()
    {
        session()->forget(self::STEP);

        return back();
    }

    public function unlink(MadelineTelegramClient $client)
    {
        $client->logout();
        session()->forget(self::STEP);
        Staff::query()->update(['telegram_user_id' => null, 'telegram_error' => null]);

        return back()->with('ok', 'فُصل حساب تيليجرام عن الموقع.');
    }

    /** رسالة تجريبية إلى «الرسائل المحفوظة» في الحساب نفسه */
    public function test(MadelineTelegramClient $client)
    {
        try {
            $client->sendToUser('me', '🔔 رسالة تجريبية من لوحة تحكم '.e(Setting::get('name')).'. الربط يعمل.');
        } catch (TelegramDeliveryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('ok', 'أُرسلت رسالة تجريبية إلى «الرسائل المحفوظة» في الحساب المرتبط.');
    }
}
