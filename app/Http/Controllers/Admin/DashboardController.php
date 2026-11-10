<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Staff;
use App\Services\Telegram\TelegramClient;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __invoke(TelegramClient $telegram)
    {
        // عدد الطلبات والمبيعات لكل يوم في آخر 14 يوماً (الملغاة لا تُحسب)
        $from = today()->subDays(13);
        $rows = Order::where('created_at', '>=', $from)->where('status', '!=', 'cancelled')
            ->get(['created_at', 'total'])->groupBy(fn ($o) => $o->created_at->toDateString());
        $days = collect(range(0, 13))->map(function ($i) use ($from, $rows) {
            $date = $from->copy()->addDays($i);
            $group = $rows->get($date->toDateString(), collect());

            return ['date' => $date, 'orders' => $group->count(), 'sales' => (int) $group->sum('total')];
        });

        $today = Order::whereDate('created_at', today())->where('status', '!=', 'cancelled');

        return view('admin.dashboard', [
            'newOrders' => Order::where('status', 'new')->count(),
            'todayOrders' => (clone $today)->count(),
            'todaySales' => (int) (clone $today)->sum('total'),
            'monthSales' => (int) Order::where('status', 'delivered')->where('created_at', '>=', now()->startOfMonth())->sum('total'),
            'unread' => ContactMessage::where('is_read', false)->count(),
            'days' => $days,
            'statusCounts' => Order::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status'),
            'top' => OrderItem::selectRaw('name, sum(qty) as sold')->where('created_at', '>=', now()->subDays(30))
                ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->groupBy('name')->orderByDesc('sold')->take(5)->get(),
            'lowStock' => Product::where('is_active', true)->where('stock', '<=', 5)->orderBy('stock')->take(6)->get(),
            'recent' => Order::latest()->take(7)->get(),
            'telegramLinked' => $telegram->isLinked(),
            'recipients' => Staff::where('notify', true)->count(),
            'failedStaff' => Staff::where('notify', true)->whereNotNull('telegram_error')->count(),
            'productCount' => Product::count(),
            'visitorsToday' => \App\Models\Visit::where('day', today())->distinct('visitor')->count('visitor'),
            'visitorsMonth' => \App\Models\Visit::where('day', '>=', today()->startOfMonth())->distinct('visitor')->count('visitor'),
        ]);
    }

    /** تستعلم عنه لوحة التحكم كل نصف دقيقة لتنبيه الموظف بالطلبات الجديدة دون تحديث الصفحة */
    public function pulse()
    {
        $latest = Order::where('status', 'new')->latest('id')->first();

        return response()->json([
            'new' => Order::where('status', 'new')->count(),
            'latest_id' => $latest?->id ?? 0,
            'latest' => $latest ? ['number' => $latest->number, 'name' => $latest->customer_name, 'url' => route('admin.orders.show', $latest)] : null,
            'unread' => ContactMessage::where('is_read', false)->count(),
        ]);
    }
}
