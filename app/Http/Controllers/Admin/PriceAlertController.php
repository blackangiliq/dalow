<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * قائمة «أعلمني عند انخفاض السعر»: المنتجات مرتبة بعدد من ينتظرون خصمها،
 * ومع كل منتج أسماء المنتظرين وأرقام هواتفهم لتذكيرهم عند وضع الخصم.
 */
class PriceAlertController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'favoredBy' => fn ($q) => $q->orderByPivot('created_at', 'desc')])
            ->withCount('favoredBy')->has('favoredBy')->orderByDesc('favored_by_count')->orderBy('name')->get();

        return view('admin.price-alerts', [
            'products' => $products,
            'focus' => (int) $request->query('product'),
            'waiting' => $products->sum('favored_by_count'),
            'siteName' => \App\Models\Setting::get('name'),
        ]);
    }
}
