@extends('layouts.admin')
@section('title', 'الطلب '.$order->number)

@section('content')
<div class="head">
    <div>
        <h1>الطلب <span class="num">{{ $order->number }}</span> <span class="pill pill--{{ $order->status }}">{{ $order->status_label }}</span></h1>
        <p><span class="num">{{ $order->created_at->format('Y-m-d H:i') }}</span> ({{ $order->created_at->diffForHumans() }}) @if ($order->handled_by) · آخر تعديل بواسطة {{ $order->handled_by }} @endif</p>
    </div>
    <a class="btn btn--ghost" href="{{ route('admin.orders.index') }}">كل الطلبات</a>
</div>

<div class="cols">
    <div>
        <div class="card">
            <h2>{{ $order->type === 'prescription' ? 'طلب بوصفة طبية' : 'المواد المطلوبة' }}</h2>
            @if ($order->items->count())
            <div class="tablewrap" style="margin-bottom:0">
                <table>
                    <thead><tr><th>المادة</th><th>السعر</th><th>الكمية</th><th class="t-end">المجموع</th></tr></thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr><td>{{ $item->name }}</td><td>@iqd($item->price)</td><td class="num">{{ $item->qty }}</td><td class="t-end">@iqd($item->price * $item->qty)</td></tr>
                    @endforeach
                    <tr><td colspan="3">التوصيل</td><td class="t-end">{{ $order->delivery_fee ? \App\Support\Util::money($order->delivery_fee) : 'مجاني' }}</td></tr>
                    <tr><td colspan="3"><strong>المجموع (الدفع عند الاستلام)</strong></td><td class="t-end"><strong>@iqd($order->total)</strong></td></tr>
                    </tbody>
                </table>
            </div>
            @else
                <p class="card__sub" style="margin:0">لا توجد مواد من المتجر. راجع صورة الوصفة واتصل بالزبون بالسعر.</p>
            @endif
        </div>

        @if ($order->prescription_image)
        <div class="card">
            <h2>صورة الوصفة</h2>
            <a href="{{ route('admin.orders.prescription', $order) }}" target="_blank" rel="noopener">
                <img class="rximg" src="{{ route('admin.orders.prescription', $order) }}" alt="صورة الوصفة الطبية المرفقة">
            </a>
        </div>
        @endif
    </div>

    <div>
        <div class="card">
            <h2>الزبون</h2>
            <dl class="kv">
                <dt>الاسم</dt><dd>{{ $order->customer_name }}</dd>
                <dt>الموبايل</dt><dd><a class="num" href="tel:{{ $order->phone }}" style="color:var(--water-deep)">{{ $order->phone }}</a></dd>
                <dt>المنطقة</dt><dd>{{ $order->area }}</dd>
                <dt>العنوان</dt><dd>{{ $order->address }}</dd>
                @if ($order->notes)<dt>ملاحظات</dt><dd>{{ $order->notes }}</dd>@endif
            </dl>
            <div class="inline" style="margin-top:16px">
                <a class="btn btn--ghost btn--sm" href="tel:{{ $order->phone }}">@icon('phone') اتصال</a>
                <a class="btn btn--ghost btn--sm" target="_blank" rel="noopener" href="https://wa.me/964{{ substr($order->phone, 1) }}?text={{ rawurlencode('مرحباً '.$order->customer_name.'، معك صيدلية الدلو بخصوص طلبك رقم '.$order->number) }}">@icon('whatsapp') واتساب</a>
            </div>
        </div>

        <div class="card">
            <h2>حالة الطلب</h2>
            <form method="post" action="{{ route('admin.orders.update', $order) }}" class="inline">
                @csrf @method('put')
                <label for="status" style="position:absolute;clip-path:inset(50%)">الحالة</label>
                <select id="status" name="status" style="flex:1">
                    @foreach (\App\Models\Order::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected($order->status === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn">حفظ الحالة</button>
            </form>
            <p class="card__sub" style="margin:12px 0 0">عند الإلغاء ترجع الكميات إلى المخزون تلقائياً.</p>
        </div>
    </div>
</div>
@endsection
