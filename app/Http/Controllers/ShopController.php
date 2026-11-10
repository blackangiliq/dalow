<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function home()
    {
        return view('shop.home', [
            'offers' => Product::active()->with('category')->whereNotNull('old_price')->whereColumn('old_price', '>', 'price')
                ->where('stock', '>', 0)->orderByRaw('(old_price - price) * 1.0 / old_price desc')->take(8)->get(),
            'faqs' => \App\Models\Faq::where('is_active', true)->orderBy('sort')->take(4)->get(),
            'featured' => Product::active()->with('category')->where('is_featured', true)->where('stock', '>', 0)->orderBy('id')->take(8)->get(),
        ]);
    }

    public function products(Request $request, ?Category $category = null)
    {
        $query = Product::active()->with('category')->search($request->query('q'));
        if ($category) {
            $query->where('category_id', $category->id);
        }
        if ($request->boolean('offers')) {
            $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price');
        }
        if ($request->boolean('available')) {
            $query->where('stock', '>', 0);
        }
        match ($request->query('sort')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->latest('id'),
        };

        return view('shop.products', [
            'products' => $query->paginate(24)->withQueryString(),
            'category' => $category,
            'q' => trim((string) $request->query('q')),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);
        $product->load('category');

        $alternatives = $product->scientific_name
            ? Product::active()->with('category')->where('scientific_name', $product->scientific_name)
                ->whereKeyNot($product->id)->orderBy('price')->take(4)->get()
            : collect();

        $related = Product::active()->with('category')->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)->whereNotIn('id', $alternatives->modelKeys())->inRandomOrder()->take(4)->get();

        return view('shop.product', compact('product', 'alternatives', 'related'));
    }

    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Product::active()->search($q)->orderBy('name')->take(7)->get()->map(fn (Product $p) => [
                'name' => $p->name,
                'sub' => $p->scientific_name,
                'price' => \App\Support\Util::money($p->price),
                'url' => route('product', $p),
            ])
        );
    }
}
