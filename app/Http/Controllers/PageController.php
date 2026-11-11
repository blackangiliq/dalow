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

    public function privacy()
    {
        return view('shop.privacy');
    }

    /** ملف يجعل الموقع قابلاً للتثبيت كتطبيق على شاشة الموبايل */
    public function manifest()
    {
        $name = \App\Models\Setting::get('name');

        return response()->json([
            'name' => $name,
            'short_name' => $name,
            'description' => 'اطلب أدويتك من '.$name.' وادفع عند الاستلام',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'dir' => 'rtl',
            'lang' => 'ar',
            'background_color' => '#0b2545',
            'theme_color' => '#0b2545',
            'icons' => [
                ['src' => asset('icons/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => asset('icons/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
                ['src' => asset('icons/icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function robots()
    {
        return response(implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /track',
            'Disallow: /order/',
            'Disallow: /search/',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $urls = collect([
            [route('home'), null, '1.0'], [route('products'), null, '0.8'], [route('products', ['offers' => 1]), null, '0.8'],
            [route('prescription'), null, '0.6'], [route('verify'), null, '0.6'], [route('request'), null, '0.5'],
            [route('faq'), null, '0.6'], [route('contact'), null, '0.6'],
        ])
            ->merge(Category::all()->map(fn ($c) => [route('category', $c), $c->updated_at, '0.8']))
            ->merge(Product::active()->get()->map(fn ($p) => [route('product', $p), $p->updated_at, '0.7']));

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .$urls->map(fn ($u) => '<url><loc>'.e($u[0]).'</loc>'.($u[1] ? '<lastmod>'.$u[1]->toDateString().'</lastmod>' : '').'<priority>'.$u[2].'</priority></url>')->implode('')
            .'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
