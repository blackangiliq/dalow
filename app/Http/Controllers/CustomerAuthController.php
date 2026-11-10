<?php

namespace App\Http\Controllers;

use App\Models\Customer;
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
        ], [
            'phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567',
            'phone.unique' => 'هذا الرقم مسجل مسبقاً. سجّل الدخول به.',
            'password.min' => 'كلمة المرور 6 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ]);

        $customer = Customer::create($data + [
            'notify_offers' => $request->boolean('notify_offers'),
            'notify_telegram' => $request->boolean('notify_telegram'),
        ]);
        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return redirect()->intended(route('account'))->with('ok', 'أهلاً '.$customer->name.'، تم إنشاء حسابك.');
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
