<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Staff;
use App\Services\StaffNotifier;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramDeliveryException;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index(Request $request, TelegramClient $telegram)
    {
        $contacts = null;
        $contactsError = null;
        if ($request->boolean('contacts') && $telegram->isLinked()) {
            try {
                $existing = Staff::pluck('phone')->all();
                $contacts = collect($telegram->contacts())
                    ->filter(fn ($c) => $c['phone'])
                    ->map(fn ($c) => $c + ['local' => Util::phone($c['phone'])])
                    ->map(fn ($c) => $c + ['added' => in_array($c['local'], $existing, true)])
                    ->values();
            } catch (TelegramDeliveryException $e) {
                $contactsError = $e->getMessage();
            }
        }

        return view('admin.staff', [
            'staff' => Staff::orderBy('name')->get(),
            'linked' => $telegram->isLinked(),
            'account' => $telegram->linkedAccount(),
            'contacts' => $contacts,
            'contactsError' => $contactsError,
        ]);
    }

    public function store(Request $request)
    {
        Staff::create($this->data($request));

        return back()->with('ok', 'أضيف الموظف وسيستلم إشعارات الطلبات. جرّب «إرسال تجربة» للتأكد من وصولها.');
    }

    /** إضافة موظفين من جهات اتصال حساب تيليجرام المرتبط */
    public function import(Request $request)
    {
        $data = $request->validate([
            'pick' => 'required|array|min:1',
            'pick.*' => 'required|string|max:400',
        ], ['pick.required' => 'اختر جهة اتصال واحدة على الأقل']);

        $added = 0;
        foreach ($data['pick'] as $encoded) {
            $c = json_decode($encoded, true);
            $phone = Util::phone($c['phone'] ?? '');
            if (! is_array($c) || ! Util::validPhone($phone) || empty($c['id']) || Staff::where('phone', $phone)->exists()) {
                continue;
            }
            Staff::create([
                'name' => mb_substr((string) ($c['name'] ?? 'موظف'), 0, 80),
                'phone' => $phone,
                'telegram_user_id' => (string) (int) $c['id'],
                'telegram_name' => mb_substr((string) ($c['name'] ?? ''), 0, 80),
                'notify' => true,
            ]);
            $added++;
        }

        return redirect()->route('admin.staff.index')->with('ok', "أضيف {$added} موظف من جهات الاتصال وسيستلمون إشعارات الطلبات.");
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $this->data($request, $staff);
        if ($data['phone'] !== $staff->phone) {
            // رقم جديد يعني حساب تيليجرام آخر
            $data += ['telegram_user_id' => null, 'telegram_name' => null, 'telegram_error' => null];
        }
        $staff->update($data);

        return back()->with('ok', 'حُفظت بيانات '.$staff->name);
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();

        return back()->with('ok', 'حُذف الموظف');
    }

    public function test(Staff $staff, StaffNotifier $notifier)
    {
        if (! $notifier->ready()) {
            return back()->with('error', 'اربط حساب تيليجرام أولاً من صفحة «إشعارات تيليجرام».');
        }
        try {
            $notifier->sendTo($staff, '🔔 رسالة تجريبية من '.e(Setting::get('name')).'. ستصلك إشعارات الطلبات هنا.');
        } catch (TelegramDeliveryException $e) {
            return back()->with('error', 'لم تصل الرسالة إلى '.$staff->name.': '.$e->getMessage());
        }

        return back()->with('ok', 'وصلت الرسالة التجريبية إلى '.$staff->name);
    }

    private function data(Request $request, ?Staff $staff = null): array
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);

        return $request->validate([
            'name' => 'required|string|max:80',
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', Rule::unique('staff', 'phone')->ignore($staff)],
            'role' => 'nullable|string|max:60',
        ], [
            'phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567',
            'phone.unique' => 'هذا الرقم مسجل لموظف آخر',
        ], ['name' => 'الاسم', 'phone' => 'رقم الهاتف']) + ['notify' => $request->boolean('notify')];
    }
}
