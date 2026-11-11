<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Faq;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SearchLog;
use App\Search\ProductSearch;
use App\Support\Util;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ShopController extends Controller
{
    public function home()
    {
        return view('shop.home', [
            'offers' => Product::active()->with('category')->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')
                ->where('stock', '>', 0)->orderByRaw('(old_price - price) * 1.0 / old_price desc')->take(8)->get(),
            'featured' => Product::active()->with('category')->where('is_featured', true)->where('stock', '>', 0)->orderBy('id')->take(8)->get(),
            'faqs' => Faq::where('is_active', true)->orderBy('sort')->take(4)->get(),
            'trending' => $this->trending(),
            'recent' => $this->recentlyViewed(),
            'sections' => \App\Support\Ui::visibleSections(),
        ]);
    }

    public function products(Request $request, ProductSearch $engine, ?Category $category = null)
    {
        $q = trim((string) $request->query('q'));
        $query = Product::active()->with('category');
        $search = null;

        if ($q !== '') {
            $search = $engine->search($q);
            $query->whereIn('id', $search['ids']);
        }
        if ($category) {
            $query->where('category_id', $category->id);
        }
        if ($request->boolean('offers')) {
            $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price');
        }
        if ($request->boolean('available')) {
            $query->where('stock', '>', 0);
        }
        $sort = $request->query('sort');
        match ($sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };

        if ($search && ! in_array($sort, ['price_asc', 'price_desc', 'name'], true)) {
            // ترتيب الأقرب للبحث أولاً، كما رتّبه محرك البحث
            $rank = array_flip($search['ids']);
            $all = $query->get()->sortBy(fn (Product $p) => $rank[$p->id])->values();
            $page = max(1, (int) $request->query('page', 1));
            $products = new LengthAwarePaginator($all->forPage($page, 24), $all->count(), 24, $page, ['path' => $request->url(), 'query' => $request->query()]);
        } else {
            $products = $query->paginate(24)->withQueryString();
        }

        if ($q !== '' && $products->currentPage() === 1 && ! auth('web')->check()) {
            SearchLog::create([
                'visitor' => $request->attributes->get('visitor'),
                'term' => mb_substr($q, 0, 120),
                'normalized' => mb_substr(ProductSearch::normalize($q), 0, 120),
                'results' => $products->total(),
                'day' => today(),
            ]);
        }

        return view('shop.products', [
            'products' => $products,
            'category' => $category,
            'q' => $q,
            'search' => $search,
            'trending' => $products->total() ? [] : $this->trending(),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        abort_unless($product->is_sellable, 404);
        // رابط قديم بلا اسم المنتج يُحوَّل للرابط الكامل (عنوان واحد لكل منتج أمام محركات البحث)
        if ($request->route()->originalParameter('product') !== $product->getRouteKey()) {
            return redirect()->route('product', $product, 301);
        }
        $product->load('category');

        $alternatives = $product->scientific_name
            ? Product::active()->with('category')->where('scientific_name', $product->scientific_name)
                ->whereKeyNot($product->id)->orderBy('price')->take(4)->get()
            : collect();

        // ما يُشترى مع هذا المنتج في نفس الطلب
        $orderIds = OrderItem::where('product_id', $product->id)->latest('id')->take(200)->pluck('order_id');
        $togetherIds = OrderItem::whereIn('order_id', $orderIds)->where('product_id', '!=', $product->id)->whereNotNull('product_id')
            ->selectRaw('product_id, count(*) as c')->groupBy('product_id')->orderByDesc('c')->take(4)->pluck('product_id');
        $together = Product::active()->with('category')->whereIn('id', $togetherIds)->where('stock', '>', 0)->get()
            ->sortBy(fn ($p) => $togetherIds->search($p->id))->values();

        $related = Product::active()->with('category')->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)->whereNotIn('id', $alternatives->pluck('id'))->whereNotIn('id', $together->pluck('id'))
            ->inRandomOrder()->take(4)->get();

        $recent = $this->recentlyViewed($product->id);
        session(['recent' => array_slice(array_values(array_unique([$product->id, ...session('recent', [])])), 0, 8)]);

        return view('shop.product', [
            'product' => $product,
            'alternatives' => $alternatives,
            'together' => $together,
            'related' => $related,
            'recent' => $recent,
            'soldWeek' => (int) OrderItem::where('product_id', $product->id)->where('created_at', '>=', now()->subDays(7))->sum('qty'),
            'isFavorite' => auth('customer')->check() && auth('customer')->user()->favorites()->whereKey($product->id)->exists(),
        ]);
    }

    public function suggest(Request $request, ProductSearch $engine)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $ids = array_slice($engine->search($q)['ids'], 0, 7);
        $rank = array_flip($ids);

        return response()->json(
            Product::active()->whereIn('id', $ids)->get()->sortBy(fn ($p) => $rank[$p->id])->values()->map(fn (Product $p) => [
                'name' => $p->name,
                'sub' => $p->scientific_name,
                'price' => Util::money($p->price),
                'url' => route('product', $p),
            ])
        );
    }

    /** خدمة «تحقق من دوائك»: مطابقة الباركود مع قائمة الصيدلية */
    public function verify(Request $request)
    {
        $code = preg_replace('/\D/', '', Util::digits((string) $request->query('code')));
        $product = null;
        if (strlen($code) >= 6) {
            $product = Product::active()->with('category')->where('barcode', $code)->first();
        }

        return view('shop.verify', ['code' => $code, 'product' => $product, 'searched' => $request->filled('code')]);
    }

    /** أكثر ما بحث عنه الزوار ووجدوه خلال آخر 30 يوماً، وإن لم توجد بيانات فكلمات افتراضية */
    private function trending(): array
    {
        $terms = SearchLog::where('day', '>=', today()->subDays(30))->where('results', '>', 0)
            ->selectRaw('normalized, count(*) as c, max(term) as term')->groupBy('normalized')
            ->havingRaw('count(*) >= 2')->orderByDesc('c')->take(6)->pluck('term')->all();

        return count($terms) >= 3 ? $terms : ['Panadol', 'Augmentin', 'فيتامين د', 'حليب أطفال', 'جهاز ضغط'];
    }

    private function recentlyViewed(?int $except = null)
    {
        $ids = array_values(array_diff(session('recent', []), [$except]));
        if (! $ids) {
            return collect();
        }

        return Product::active()->with('category')->whereIn('id', $ids)->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))->take(4)->values();
    }
}
