<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Staff;
use App\Services\TelegramService;

class DashboardController extends Controller
{
    public function __invoke(TelegramService $telegram)
    {
        return view('admin.dashboard', [
            'newOrders' => Order::where('status', 'new')->count(),
            'todayOrders' => Order::whereDate('created_at', today())->count(),
            'monthSales' => Order::where('status', 'delivered')->where('created_at', '>=', now()->startOfMonth())->sum('total'),
            'productCount' => Product::count(),
            'lowStock' => Product::where('is_active', true)->where('stock', '<=', 5)->orderBy('stock')->take(8)->get(),
            'recent' => Order::latest()->take(8)->get(),
            'telegramReady' => $telegram->configured(),
            'linkedStaff' => Staff::whereNotNull('telegram_chat_id')->where('notify', true)->count(),
            'staffCount' => Staff::count(),
        ]);
    }
}
