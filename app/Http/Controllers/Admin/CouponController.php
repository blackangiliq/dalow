<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        return view('admin.coupons', [
            'coupons' => Coupon::latest()->get(),
            'saved' => Order::whereNotNull('coupon_code')->where('status', '!=', 'cancelled')
                ->selectRaw('coupon_code, count(*) as orders, sum(discount) as discount, sum(total) as sales')->groupBy('coupon_code')->get()->keyBy('coupon_code'),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z0-9]{3,20}$/', Rule::unique('coupons', 'code')],
            'type' => 'required|in:percent,fixed',
            'value' => ['required', 'integer', 'min:1', $request->input('type') === 'percent' ? 'max:90' : 'max:10000000'],
            'min_total' => 'nullable|integer|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after_or_equal:today',
        ], [
            'code.regex' => 'الكود حروف إنجليزية وأرقام فقط، من 3 إلى 20، مثل DALU10',
            'code.unique' => 'هذا الكود موجود مسبقاً',
            'value.max' => 'نسبة الخصم لا تتجاوز 90%',
            'expires_at.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون اليوم أو بعده',
        ], ['code' => 'الكود', 'value' => 'قيمة الخصم', 'min_total' => 'الحد الأدنى للطلب', 'max_uses' => 'عدد مرات الاستعمال', 'expires_at' => 'تاريخ الانتهاء']);

        Coupon::create(['min_total' => $data['min_total'] ?? 0] + $data);

        return back()->with('ok', 'أضيف الكود '.$data['code'].'. انشره لزبائنك ليكتبوه في السلة.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('ok', $coupon->is_active ? 'شُغّل الكود' : 'أُوقف الكود');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return back()->with('ok', 'حُذف الكود');
    }
}
