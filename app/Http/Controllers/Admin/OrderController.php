<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::withCount('items')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($q = trim((string) $request->query('q'))) {
            $query->where(fn ($w) => $w->where('number', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->orWhere('customer_name', 'like', "%{$q}%"));
        }

        return view('admin.orders.index', [
            'orders' => $query->paginate(30)->withQueryString(),
            'counts' => Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load('items')]);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate(['status' => 'required|in:'.implode(',', array_keys(Order::STATUSES))]);

        $order->changeStatus($data['status'], $request->user()->name);

        return back()->with('ok', 'تغيرت حالة الطلب إلى «'.$order->status_label.'»');
    }

    public function prescription(Order $order)
    {
        abort_unless($order->prescription_image && Storage::disk('local')->exists($order->prescription_image), 404);

        return Storage::disk('local')->response($order->prescription_image);
    }
}
