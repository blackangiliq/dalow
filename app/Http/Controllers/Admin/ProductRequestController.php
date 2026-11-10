<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductRequest::latest();
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return view('admin.requests', [
            'requests' => $query->paginate(20)->withQueryString(),
            'counts' => ProductRequest::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function update(Request $request, ProductRequest $productRequest)
    {
        $productRequest->update($request->validate(['status' => 'required|in:'.implode(',', array_keys(ProductRequest::STATUSES))]));

        return back()->with('ok', 'تغيرت حالة الطلب إلى «'.$productRequest->status_label.'»');
    }

    public function destroy(ProductRequest $productRequest)
    {
        if ($productRequest->image) {
            Storage::disk('local')->delete($productRequest->image);
        }
        $productRequest->delete();

        return back()->with('ok', 'حُذف الطلب');
    }

    public function image(ProductRequest $productRequest)
    {
        abort_unless($productRequest->image && Storage::disk('local')->exists($productRequest->image), 404);

        return Storage::disk('local')->response($productRequest->image);
    }
}
