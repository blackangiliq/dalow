<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    private const KEYS = [
        'name', 'address', 'phone', 'hours', 'map_url', 'email', 'facebook_url', 'instagram_url',
        'whatsapp', 'whatsapp_pharmacist', 'pharmacist_label',
        'free_delivery_over', 'delivery_areas', 'capsules', 'google_verification', 'promo_text',
    ];

    public function edit()
    {
        return view('admin.settings', ['values' => collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])]);
    }

    public function update(Request $request)
    {
        $wa = ['nullable', 'regex:/^\+?[\d\s-]{10,20}$/'];
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'address' => 'required|string|max:160',
            'phone' => 'required|string|max:20',
            'hours' => 'nullable|string|max:160',
            'map_url' => 'nullable|url|max:500',
            'email' => 'nullable|email|max:120',
            'facebook_url' => 'nullable|url|max:300',
            'instagram_url' => 'nullable|url|max:300',
            'whatsapp' => $wa,
            'whatsapp_pharmacist' => $wa,
            'pharmacist_label' => 'required|string|max:40',
            'free_delivery_over' => 'nullable|integer|min:0',
            'delivery_areas' => 'required|string|max:5000',
            'google_verification' => ['nullable', 'regex:/^[\w-]{10,100}$/'],
            'promo_text' => 'nullable|string|max:120',
        ], [
            'whatsapp.regex' => 'رقم واتساب الطلبات غير صحيح. مثال: 9647701234567',
            'google_verification.regex' => 'الصق قيمة content فقط من وسم التحقق، بدون الوسم نفسه',
            'whatsapp_pharmacist.regex' => 'رقم واتساب الصيدلانية غير صحيح. مثال: 9647701234567',
        ], ['pharmacist_label' => 'اسم قسم الاستشارة']);

        $data['capsules'] = $request->boolean('capsules') ? '1' : '0';
        foreach (self::KEYS as $key) {
            Setting::put($key, isset($data[$key]) ? (string) $data[$key] : null);
        }
        // الحقل الفارغ يعني «معطّل» هنا، وليس الرجوع للقيمة الافتراضية
        if (($data['free_delivery_over'] ?? null) === null) {
            Setting::put('free_delivery_over', '0');
        }
        if (($data['promo_text'] ?? null) === null) {
            Setting::put('promo_text', ' ');
        }

        return back()->with('ok', 'حُفظت الإعدادات');
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
