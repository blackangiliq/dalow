<?php

namespace App\Http\Controllers;

use App\Models\ProductRequest;
use App\Services\StaffNotifier;
use App\Support\Util;
use Illuminate\Http\Request;

/** طلب توفير منتج غير موجود في الموقع */
class ProductRequestController extends Controller
{
    public function form(Request $request)
    {
        return view('shop.request', ['prefill' => mb_substr(trim((string) $request->query('name')), 0, 160)]);
    }

    public function store(Request $request, StaffNotifier $notifier)
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);
        $data = $request->validate([
            'product_name' => 'required|string|min:2|max:160',
            'details' => 'nullable|string|max:1000',
            'name' => 'required|string|min:3|max:80',
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/'],
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
        ], [
            'phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567',
            'image.image' => 'المرفق يجب أن يكون صورة (JPG أو PNG)',
            'image.max' => 'حجم الصورة أكبر من 6 ميغابايت',
        ], ['product_name' => 'اسم المنتج', 'details' => 'التفاصيل', 'image' => 'الصورة']);
        unset($data['image']);

        $productRequest = ProductRequest::create($data + [
            'customer_id' => auth('customer')->id(),
            'image' => $request->file('image')?->store('requests', 'local'),
        ]);
        defer(fn () => $notifier->productRequested($productRequest));

        return redirect()->route('request')->with('ok', 'وصل طلبك. سنبحث عن «'.$productRequest->product_name.'» ونتصل بك عند توفيره.');
    }
}
