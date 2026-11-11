<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Services\PhoneCodes;
use App\Services\Telegram\TelegramDeliveryException;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerAuthController extends Controller
{
    public function showRegister()
    {
        return view('shop.auth.register', ['areas' => Util::areas()]);
    }

    public function register(Request $request)
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => 'required|string|min:3|max:80',
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', Rule::unique('customers', 'phone')],
            'password' => 'required|string|min:6|confirmed',
            'allergy_answer' => 'nullable|in:none,yes',
            'allergies' => 'nullable|required_if:allergy_answer,yes|string|max:500',
            'health_consent' => 'accepted_if:allergy_answer,yes',
        ], [
            'allergies.required_if' => 'اكتب الأدوية التي لديك حساسية منها، أو اختر «لا توجد حساسية»',
            'health_consent.accepted_if' => 'وافق على حفظ معلوماتك الصحية لنحفظ حساسيتك',
            'phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567',
            'phone.unique' => 'هذا الرقم مسجل مسبقاً. سجّل الدخول به.',
            'password.min' => 'كلمة المرور 6 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        $health = array_intersect_key($data, array_flip(['allergy_answer', 'allergies', 'health_consent']));
        $data = array_diff_key($data, $health);
        $customer = Customer::create($data + [
            'notify_offers' => $request->boolean('notify_offers'),
            'notify_telegram' => $request->boolean('notify_telegram'),
        ]);
        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        // سؤال الحساسية عند التسجيل يبدأ ملفه الصحي
        if (\App\Support\Ui::on('health_profile')) {
            $profile = $customer->selfProfile();
            if (($health['allergy_answer'] ?? null) === 'none') {
                $profile->update(['no_known_allergies' => true]);
            } elseif (($health['allergy_answer'] ?? null) === 'yes') {
                foreach (array_filter(array_map('trim', preg_split('/[،,]+/u', (string) $health['allergies']))) as $substance) {
                    $profile->allergies()->firstOrCreate(['substance' => mb_substr($substance, 0, 120)], ['severity' => 'moderate']);
                }
                $customer->forceFill(['health_consent_at' => now()])->save();
            }
            // بعد التسجيل يكمل ملفه الصحي، إلا إن كان في طريقه لصفحة أخرى (منتج أراد متابعة سعره مثلاً)
            if (! session()->has('url.intended')) {
                session(['url.intended' => route('account', ['tab' => 'health', 'welcome' => 1])]);
            }
        }

        // إن كانت خاصية التأكيد مشغلة نرسل كوداً على رقمه قبل أي شيء آخر
        if (app(PhoneCodes::class)->available()) {
            return $this->sendVerification($customer, 'أهلاً '.$customer->name.'، تم إنشاء حسابك. ');
        }

        return redirect()->intended(route('account'))->with('ok', 'أهلاً '.$customer->name.'، تم إنشاء حسابك.');
    }

    // ---------- تأكيد رقم الموبايل ----------

    public function showVerify(Request $request, PhoneCodes $codes)
    {
        $customer = $request->user('customer');
        if ($customer->phone_verified_at || ! $codes->available()) {
            return redirect()->route('account');
        }

        return view('shop.auth.verify-phone', ['customer' => $customer]);
    }

    public function resendVerify(Request $request)
    {
        return $this->sendVerification($request->user('customer'));
    }

    public function confirmVerify(Request $request, PhoneCodes $codes)
    {
        $customer = $request->user('customer');
        $data = $request->validate(['code' => ['required', 'regex:/^\d{6}$/']], ['code.regex' => 'الكود ستة أرقام']);

        if (! $codes->check($customer->phone, 'verify', $data['code'])) {
            return back()->withErrors(['code' => 'الكود غير صحيح أو انتهت مدته. اطلب كوداً جديداً.']);
        }
        $customer->forceFill(['phone_verified_at' => now()])->save();
        // الرقم مؤكد الآن: طلباته السابقة بهذا الرقم (قبل التسجيل) تنضم لحسابه
        Order::whereNull('customer_id')->where('phone', $customer->phone)->update(['customer_id' => $customer->id]);

        return redirect()->intended(route('account'))->with('ok', 'تم تأكيد رقمك.');
    }

    private function sendVerification(\App\Models\Customer $customer, string $prefix = '')
    {
        try {
            app(PhoneCodes::class)->send($customer->phone, 'verify', $customer->name);
        } catch (TelegramDeliveryException) {
            return redirect()->route('customer.verify')->with('error', $prefix.'لم نستطع إرسال الكود إلى هذا الرقم على تيليجرام. تأكد أن الرقم مسجل في تيليجرام، أو اتصل بالصيدلية لتأكيد حسابك.');
        }

        return redirect()->route('customer.verify')->with('ok', $prefix.'أرسلنا كود التأكيد إلى تيليجرام على رقمك.');
    }

    // ---------- استعادة كلمة المرور ----------

    public function showForgot(PhoneCodes $codes)
    {
        return view('shop.auth.forgot', ['available' => $codes->available(), 'sent' => session('reset_phone')]);
    }

    public function sendReset(Request $request, PhoneCodes $codes)
    {
        abort_unless($codes->available(), 404);
        $phone = Util::phone($request->input('phone'));
        $request->merge(['phone' => $phone])->validate(['phone' => ['required', 'regex:/^07[3-9]\d{8}$/']], ['phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567']);

        // الرد واحد سواء كان الرقم مسجلاً أم لا، حتى لا يُعرف من عنده حساب
        if ($customer = Customer::where('phone', $phone)->first()) {
            try {
                $codes->send($phone, 'reset', $customer->name);
            } catch (TelegramDeliveryException) {
                // نتجاهل: الرد نفسه في الحالتين
            }
        }
        session(['reset_phone' => $phone]);

        return redirect()->route('customer.forgot')->with('ok', 'إذا كان الرقم مسجلاً فقد أرسلنا كوداً إلى تيليجرام عليه.');
    }

    public function reset(Request $request, PhoneCodes $codes)
    {
        $phone = (string) session('reset_phone');
        $data = $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
            'password' => 'required|string|min:6|confirmed',
        ], ['code.regex' => 'الكود ستة أرقام', 'password.min' => 'كلمة المرور 6 أحرف على الأقل', 'password.confirmed' => 'تأكيد كلمة المرور غير مطابق']);

        $customer = Customer::where('phone', $phone)->first();
        if (! $customer || ! $codes->check($phone, 'reset', $data['code'])) {
            return back()->withErrors(['code' => 'الكود غير صحيح أو انتهت مدته. اطلب كوداً جديداً.']);
        }
        // من استلم الكود على الرقم فهو صاحبه
        $customer->forceFill(['password' => $data['password'], 'phone_verified_at' => $customer->phone_verified_at ?? now()])->save();
        session()->forget('reset_phone');
        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return redirect()->route('account')->with('ok', 'تغيرت كلمة المرور.');
    }

    public function showLogin()
    {
        return view('shop.auth.login');
    }

    public function login(Request $request)
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);
        $data = $request->validate(['phone' => 'required|string', 'password' => 'required|string']);

        if (! Auth::guard('customer')->attempt($data, true)) {
            return back()->withInput($request->only('phone'))->withErrors(['phone' => 'رقم الموبايل أو كلمة المرور غير صحيحة']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('account'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('ok', 'تم تسجيل الخروج');
    }
}
