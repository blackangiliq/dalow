<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductRequest;
use App\Models\SearchLog;
use App\Models\SearchSynonym;
use App\Models\TrackEvent;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /** أسماء مفهومة لما نتتبعه من نقرات وأقسام */
    public const LABELS = [
        'hero-search' => 'مربع البحث الرئيسي', 'rx-card' => 'بطاقة «عندك وصفة طبية؟»', 'chip' => 'كلمات البحث الشائعة',
        'category' => 'بطاقات الأقسام', 'add-to-cart' => 'زر أضف للسلة', 'consult' => 'استشر الصيدلانية',
        'wa-order' => 'اطلب عبر واتساب', 'favorite' => 'زر المفضلة', 'share' => 'مشاركة المنتج', 'verify' => 'تحقق من دوائك',
        'theme' => 'تبديل الوضع الداكن', 'promo' => 'شريط العرض العلوي', 'request' => 'طلب توفير منتج', 'call' => 'اتصال هاتفي',
        'map' => 'الموقع على الخريطة', 'product' => 'فتح صفحة منتج', 'tab' => 'شريط التنقل السفلي',
        'hero' => 'الواجهة والبحث', 'assure' => 'شريط المزايا', 'cats' => 'تسوق حسب القسم', 'offers' => 'العروض',
        'steps' => 'كيف تطلب', 'featured' => 'الأكثر طلباً', 'services' => 'خدمات الصيدلية', 'faq' => 'الأسئلة الشائعة',
        'visit' => 'زورنا في زيونة', 'recent' => 'شاهدته مؤخراً', 'details' => 'تفاصيل المنتج', 'ask' => 'صندوق الاستشارة',
        'alternatives' => 'بدائل بنفس المادة', 'together' => 'يُشترى معه', 'related' => 'من نفس القسم',
    ];

    public function index(Request $request)
    {
        $days = in_array((int) $request->query('days'), [7, 30, 90], true) ? (int) $request->query('days') : 30;
        $from = today()->subDays($days - 1);

        // زيارات وزوار كل يوم
        $perDay = Visit::where('day', '>=', $from)->selectRaw('day, count(*) as views, count(distinct visitor) as visitors')
            ->groupBy('day')->get()->keyBy(fn ($r) => $r->day->toDateString());
        $series = collect(range(0, $days - 1))->map(function ($i) use ($from, $perDay) {
            $date = $from->copy()->addDays($i);
            $row = $perDay->get($date->toDateString());

            return ['date' => $date, 'views' => (int) ($row->views ?? 0), 'visitors' => (int) ($row->visitors ?? 0)];
        });

        // آخر ستة أشهر
        $months = collect(range(5, 0))->map(function ($i) {
            $start = today()->startOfMonth()->subMonths($i);
            $end = $start->copy()->endOfMonth();
            $visits = Visit::whereBetween('day', [$start, $end]);

            return [
                'label' => $start->translatedFormat('F Y'),
                'views' => (clone $visits)->count(),
                'visitors' => (clone $visits)->distinct('visitor')->count('visitor'),
                'orders' => Order::whereBetween('created_at', [$start, $end->copy()->endOfDay()])->where('status', '!=', 'cancelled')->count(),
            ];
        });

        $range = Visit::where('day', '>=', $from);
        $visitors = (clone $range)->distinct('visitor')->count('visitor');
        $orders = Order::where('created_at', '>=', $from)->where('status', '!=', 'cancelled')->count();

        $names = Category::pluck('name', 'id');
        $topCategories = (clone $range)->where('type', 'category')->whereNotNull('ref_id')
            ->selectRaw('ref_id, count(*) as views, count(distinct visitor) as visitors')->groupBy('ref_id')->orderByDesc('views')->take(8)->get()
            ->map(fn ($r) => ['name' => $names[$r->ref_id] ?? 'قسم محذوف', 'views' => $r->views, 'visitors' => $r->visitors]);

        $viewed = (clone $range)->where('type', 'product')->whereNotNull('ref_id')
            ->selectRaw('ref_id, count(*) as views')->groupBy('ref_id')->orderByDesc('views')->take(10)->get();
        $sold = OrderItem::where('created_at', '>=', $from)->whereIn('product_id', $viewed->pluck('ref_id'))
            ->selectRaw('product_id, sum(qty) as qty')->groupBy('product_id')->pluck('qty', 'product_id');
        $products = Product::whereIn('id', $viewed->pluck('ref_id'))->get()->keyBy('id');
        $topProducts = $viewed->filter(fn ($r) => $products->has($r->ref_id))
            ->map(fn ($r) => ['product' => $products[$r->ref_id], 'views' => $r->views, 'sold' => (int) ($sold[$r->ref_id] ?? 0)])->values();

        $searches = SearchLog::where('day', '>=', $from);
        $label = fn (string $name) => self::LABELS[explode(':', $name)[0]] ?? $name;

        return view('admin.analytics', [
            'days' => $days,
            'series' => $series,
            'months' => $months,
            'today' => ['views' => Visit::where('day', today())->count(), 'visitors' => Visit::where('day', today())->distinct('visitor')->count('visitor')],
            'total' => ['views' => (clone $range)->count(), 'visitors' => $visitors, 'orders' => $orders, 'rate' => $visitors ? round($orders * 100 / $visitors, 1) : 0],
            'topCategories' => $topCategories,
            'topProducts' => $topProducts,
            'topSearches' => (clone $searches)->selectRaw('normalized, max(term) as term, count(*) as c, min(results) as results')
                ->groupBy('normalized')->orderByDesc('c')->take(12)->get(),
            'failedSearches' => (clone $searches)->where('results', 0)->selectRaw('normalized, max(term) as term, count(*) as c')
                ->groupBy('normalized')->orderByDesc('c')->take(12)->get(),
            'clicks' => TrackEvent::where('day', '>=', $from)->where('kind', 'click')
                ->selectRaw("name, count(*) as c, count(distinct visitor) as visitors")->groupBy('name')->orderByDesc('c')->take(40)->get()
                ->groupBy(fn ($r) => $label($r->name))->map(fn ($g) => ['c' => $g->sum('c'), 'visitors' => $g->max('visitors')])->sortByDesc('c')->take(10),
            'dwell' => TrackEvent::where('day', '>=', $from)->where('kind', 'dwell')
                ->selectRaw('name, avg(value) as seconds, count(*) as c')->groupBy('name')->orderByDesc('seconds')->take(12)->get()
                ->map(fn ($r) => ['name' => $label($r->name), 'seconds' => round($r->seconds), 'c' => $r->c]),
            'devices' => (clone $range)->selectRaw('device, count(distinct visitor) as c')->groupBy('device')->pluck('c', 'device'),
            'referrers' => (clone $range)->whereNotNull('referrer')->selectRaw('referrer, count(distinct visitor) as c')->groupBy('referrer')->orderByDesc('c')->take(8)->get(),
            // المنتجات الأكثر إضافة للمفضلة وليس عليها خصم الآن: أقوى مرشحين لعرض جديد
            'wanted' => Product::withCount('favoredBy')->get()->where('favored_by_count', '>', 0)->sortByDesc('favored_by_count')->take(10)->values(),
            'customers' => Customer::count(),
            'requests' => ProductRequest::where('created_at', '>=', $from)->selectRaw('product_name, count(*) as c')->groupBy('product_name')->orderByDesc('c')->take(8)->get(),
            'synonyms' => SearchSynonym::orderBy('term')->get(),
        ]);
    }

    public function synonymStore(Request $request)
    {
        SearchSynonym::create($request->validate([
            'term' => 'required|string|max:200',
            'expands_to' => 'required|string|max:500',
        ], [], ['term' => 'كلمة الزبون', 'expands_to' => 'تُفهم على أنها']));

        return back()->with('ok', 'أضيف المرادف وسيعمل في البحث فوراً');
    }

    public function synonymDestroy(SearchSynonym $synonym)
    {
        $synonym->delete();

        return back()->with('ok', 'حُذف المرادف');
    }
}
