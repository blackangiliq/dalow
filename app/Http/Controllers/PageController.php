<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Product;
use App\Services\StaffNotifier;
use App\Support\Util;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function faq()
    {
        return view('shop.faq', ['faqs' => Faq::where('is_active', true)->orderBy('sort')->get()]);
    }

    public function contact()
    {
        return view('shop.contact');
    }

    public function contactStore(Request $request, StaffNotifier $notifier)
    {
        $request->merge(['phone' => Util::phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => 'required|string|min:3|max:80',
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/'],
            'subject' => 'nullable|string|max:120',
            'body' => 'required|string|min:5|max:2000',
        ], ['phone.regex' => 'اكتب رقم موبايل عراقي صحيح، مثل 07701234567'], ['body' => 'الرسالة', 'subject' => 'الموضوع']);

        $message = ContactMessage::create($data);
        defer(fn () => $notifier->contactReceived($message));

        return back()->with('ok', 'وصلت رسالتك. سنتصل بك في أقرب وقت.');
    }

    public function sitemap()
    {
        $urls = collect([route('home'), route('products'), route('faq'), route('contact'), route('prescription')])
            ->merge(Category::all()->map(fn ($c) => route('category', $c)))
            ->merge(Product::active()->pluck('id')->map(fn ($id) => route('product', $id)));

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$urls->map(fn ($u) => '<url><loc>'.e($u).'</loc></url>')->implode('').'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
