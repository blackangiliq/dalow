<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>فاتورة الطلب {{ $order->number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Tajawal", system-ui, sans-serif; color: #0b2545; margin: 0; padding: 24px; background: #f1f6f7; line-height: 1.6; }
        .sheet { max-width: 720px; margin: 0 auto; background: #fff; padding: 32px; border-radius: 12px; }
        header { display: flex; justify-content: space-between; gap: 16px; border-bottom: 2px solid #0b2545; padding-bottom: 14px; margin-bottom: 18px; }
        h1 { margin: 0; font-size: 26px; } h2 { margin: 0 0 6px; font-size: 16px; color: #55678a; }
        .num { direction: ltr; unicode-bidi: isolate; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 18px; }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 9px 8px; border-bottom: 1px solid #dbe6e9; text-align: start; }
        th { color: #55678a; font-size: 14px; } td:last-child, th:last-child { text-align: end; white-space: nowrap; }
        tfoot td { border: 0; } tfoot tr:last-child td { font-weight: 800; font-size: 19px; border-top: 2px solid #0b2545; }
        .foot { margin-top: 22px; color: #55678a; font-size: 14px; text-align: center; }
        .bar { max-width: 720px; margin: 0 auto 14px; display: flex; gap: 8px; }
        .bar button, .bar a { font: inherit; font-weight: 700; padding: 8px 18px; border-radius: 9px; border: 0; background: #13838f; color: #fff; cursor: pointer; text-decoration: none; }
        .bar a { background: #fff; color: #0b2545; box-shadow: inset 0 0 0 1.5px #dbe6e9; }
        @media print { body { background: #fff; padding: 0; } .bar { display: none; } .sheet { padding: 0; } }
        @media (max-width: 560px) { body { padding: 10px; } .sheet { padding: 18px; } .cols, header { grid-template-columns: 1fr; display: grid; } }
    </style>
</head>
<body>
<div class="bar"><button onclick="print()">طباعة الفاتورة</button><a href="{{ route('admin.orders.show', $order) }}">رجوع للطلب</a></div>
<div class="sheet">
    <header>
        <div><h1>{{ \App\Models\Setting::get('name') }}</h1>{{ \App\Models\Setting::get('address') }}<br><span class="num">{{ \App\Models\Setting::get('phone') }}</span></div>
        <div style="text-align:end"><h2>فاتورة طلب</h2><strong class="num" style="font-size:22px">{{ $order->number }}</strong><br><span class="num">{{ $order->created_at->format('Y-m-d H:i') }}</span></div>
    </header>
    <div class="cols">
        <div><h2>الزبون</h2>{{ $order->customer_name }}<br><span class="num">{{ $order->phone }}</span></div>
        <div><h2>عنوان التوصيل</h2>{{ $order->area }}<br>{{ $order->address }}</div>
    </div>
    @if ($order->notes)<p><strong>ملاحظات:</strong> {{ $order->notes }}</p>@endif
    @if ($order->items->count())
    <table>
        <thead><tr><th>المادة</th><th>السعر</th><th>الكمية</th><th>المجموع</th></tr></thead>
        <tbody>
        @foreach ($order->items as $item)
            <tr><td>{{ $item->name }}</td><td>{{ number_format($item->price) }}</td><td class="num">{{ $item->qty }}</td><td>@iqd($item->price * $item->qty)</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="3">مجموع المواد</td><td>@iqd($order->subtotal)</td></tr>
            @if ($order->discount)<tr><td colspan="3">خصم الكود {{ $order->coupon_code }}</td><td>-@iqd($order->discount)</td></tr>@endif
            <tr><td colspan="3">التوصيل</td><td>{{ $order->delivery_fee ? \App\Support\Util::money($order->delivery_fee) : 'مجاني' }}</td></tr>
            <tr><td colspan="3">المطلوب دفعه عند الاستلام</td><td>@iqd($order->total)</td></tr>
        </tfoot>
    </table>
    @else
        <p>طلب بوصفة طبية. تُضاف المواد والأسعار بعد مراجعة الصيدلاني للوصفة.</p>
    @endif
    <p class="foot">شكراً لتعاملكم مع {{ \App\Models\Setting::get('name') }}. للاستفسار: <span class="num">{{ \App\Models\Setting::get('phone') }}</span></p>
</div>
</body>
</html>
