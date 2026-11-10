<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Services\TelegramService;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(TelegramService $telegram)
    {
        $bot = $telegram->configured() ? $telegram->call('getMe') : null;

        return view('admin.staff', [
            'staff' => Staff::orderBy('name')->get(),
            'botUsername' => $bot['result']['username'] ?? null,
            'botError' => ($bot && ! ($bot['ok'] ?? false)) ? ($bot['description'] ?? 'خطأ') : null,
            'configured' => $telegram->configured(),
        ]);
    }

    public function store(Request $request)
    {
        Staff::create($this->data($request));

        return back()->with('ok', 'أضيف الموظف. اطلب منه فتح البوت ومشاركة رقمه ليستلم الإشعارات.');
    }

    public function update(Request $request, Staff $staff)
    {
        $staff->update($this->data($request, $staff));

        return back()->with('ok', 'حُفظت بيانات '.$staff->name);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();

        return back()->with('ok', 'حُذف الموظف');
    }

    public function test(Staff $staff, TelegramService $telegram)
    {
        if (! $staff->telegram_chat_id) {
            return back()->with('error', $staff->name.' لم يربط حسابه في تيليجرام بعد');
        }
        $res = $telegram->send($staff->telegram_chat_id, '🔔 رسالة تجريبية من لوحة تحكم الصيدلية. الإشعارات تعمل.');

        return ($res['ok'] ?? false)
            ? back()->with('ok', 'أُرسلت رسالة تجريبية إلى '.$staff->name)
            : back()->with('error', 'فشل الإرسال: '.($res['description'] ?? ''));
    }

    public function sync(TelegramService $telegram)
    {
        $res = $telegram->sync();

        return back()->with($res['ok'] ? 'ok' : 'error', $res['message']);
    }

    private function data(Request $request, ?Staff $staff = null): array
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);

        return $request->validate([
            'name' => 'required|string|max:80',
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', Rule::unique('staff', 'phone')->ignore($staff)],
            'role' => 'nullable|string|max:60',
            'telegram_chat_id' => ['nullable', 'regex:/^-?\d{3,20}$/'],
        ], [
            'phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567',
            'phone.unique' => 'هذا الرقم مسجل لموظف آخر',
            'telegram_chat_id.regex' => 'معرف المحادثة يتكون من أرقام فقط',
        ], ['name' => 'الاسم', 'phone' => 'رقم الهاتف']) + ['notify' => $request->boolean('notify')];
    }
}
