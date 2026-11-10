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

    /** فاتورة قابلة للطباعة ترافق الطلب عند التوصيل */
    public function invoice(Order $order)
    {
        return view('admin.orders.invoice', ['order' => $order->load('items')]);
    }

    /** تصدير الطلبات (حسب التصفية الحالية) إلى ملف CSV يفتح في Excel */
    public function export(Request $request)
    {
        $query = Order::withCount('items')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // ليقرأ Excel العربية بشكل صحيح
            fputcsv($out, ['رقم الطلب', 'التاريخ', 'الزبون', 'الموبايل', 'المنطقة', 'العنوان', 'النوع', 'عدد المواد', 'التوصيل', 'المجموع', 'الحالة']);
            $query->chunk(500, function ($orders) use ($out) {
                foreach ($orders as $o) {
                    fputcsv($out, [
                        $o->number, $o->created_at->format('Y-m-d H:i'), $o->customer_name, "\t".$o->phone, $o->area, $o->address,
                        $o->type === 'prescription' ? 'وصفة' : 'سلة', $o->items_count, $o->delivery_fee, $o->total, $o->status_label,
                    ]);
                }
            });
            fclose($out);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function prescription(Order $order)
    {
        abort_unless($order->prescription_image && Storage::disk('local')->exists($order->prescription_image), 404);

        return Storage::disk('local')->response($order->prescription_image);
    }
}
