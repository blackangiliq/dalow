<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::withCount(['orders', 'favorites'])->latest();
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%"));
        }

        return view('admin.customers', ['customers' => $query->paginate(30)->withQueryString()]);
    }

    /** تأكيد الرقم يدوياً بعد الاتصال بالزبون (لمن ليس عنده تيليجرام) */
    public function verify(Customer $customer)
    {
        $customer->forceFill(['phone_verified_at' => $customer->phone_verified_at ? null : now()])->save();
        if ($customer->phone_verified_at) {
            \App\Models\Order::whereNull('customer_id')->where('phone', $customer->phone)->update(['customer_id' => $customer->id]);
        }

        return back()->with('ok', $customer->phone_verified_at ? 'أُكد رقم '.$customer->name : 'أُلغي تأكيد رقم '.$customer->name);
    }

    /** كلمة مرور مؤقتة يبلغها المدير للزبون بنفسه */
    public function password(Customer $customer)
    {
        $temporary = (string) random_int(10000000, 99999999);
        $customer->forceFill(['password' => $temporary])->save();

        return back()->with('ok', "كلمة المرور الجديدة لـ{$customer->name} هي {$temporary}. أبلغه بها ليدخل ثم يغيّرها من «بياناتي».");
    }
}
