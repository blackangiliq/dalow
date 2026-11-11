@extends('layouts.admin')
@section('title', 'الطلب '.$order->number)

@section('content')
<div class="head">
    <div>
        <h1>الطلب <span class="num">{{ $order->number }}</span> <span class="pill pill--{{ $order->status }}">{{ $order->status_label }}</span></h1>
        <p><span class="num">{{ $order->created_at->format('Y-m-d H:i') }}</span> ({{ $order->created_at->diffForHumans() }}) @if ($order->handled_by) · آخر تعديل بواسطة {{ $order->handled_by }} @endif</p>
    </div>
    <div class="head__actions">
        <a class="btn btn--ghost" href="{{ route('admin.orders.invoice', $order) }}" target="_blank" rel="noopener">@icon('sheet') طباعة الفاتورة</a>
        <a class="btn btn--ghost" href="{{ route('admin.orders.index') }}">كل الطلبات</a>
    </div>
</div>

@if ($conflicts || $order->allergy_ack)
    <div class="flash flash--bad">@icon('alert') <span><strong>تعارض مع الحساسية.</strong> {{ $order->allergy_ack ? 'أقرّ الزبون بعلمه: '.$order->allergy_ack.'.' : '' }} راجع الدواء واتصل بالزبون قبل الصرف.</span></div>
@endif

<div class="cols">
    <div>
        @if ($order->healthProfile && \App\Support\Ui::on('health_profile'))
        <div class="card card--patient">
            <div class="card__head">
                <h2>ملف المريض {{ $order->healthProfile->relation === 'self' ? '' : '(طلب لـ'.$order->healthProfile->name.')' }}</h2>
                @if ($order->customer)<a href="{{ route('admin.customers.show', $order->customer) }}">الملف الكامل وطلباته السابقة</a>@endif
            </div>
            @include('admin.partials.patient', ['profile' => $order->healthProfile])
        </div>
        @elseif ($order->customer)
        <div class="hint">الزبون <a href="{{ route('admin.customers.show', $order->customer) }}" style="text-decoration:underline">{{ $order->customer->name }}</a> لم يعبئ ملفه الصحي. اسأله عن الحساسية قبل الصرف.</div>
        @endif

        <div class="card">
            <h2>{{ $order->type === 'prescription' ? 'طلب بوصفة طبية' : 'المواد المطلوبة' }}</h2>
            @if ($order->items->count())
            <div class="tablewrap" style="margin-bottom:0">
                <table>
                    <thead><tr><th>المادة</th><th>السعر</th><th>الكمية</th><th class="t-end">المجموع</th></tr></thead>
                    <tbody>
                    @foreach ($order->items as $item)
                        <tr @if (isset($conflicts[$item->id])) class="row-warn" @endif><td>{{ $item->name }}@foreach ($conflicts[$item->id] ?? [] as $hit)<span class="t-sub warn">⚠ يتعارض مع حساسية {{ $hit['allergy']->substance }} ({{ $hit['allergy']->severity_label }})@if ($hit['via']) · مجموعة {{ $hit['via'] }}@endif</span>@endforeach</td><td>@iqd($item->price)</td><td class="num">{{ $item->qty }}</td><td class="t-end">@iqd($item->price * $item->qty)</td></tr>
                    @endforeach
                    @if ($order->discount)<tr><td colspan="3">خصم الكود <span class="num">{{ $order->coupon_code }}</span></td><td class="t-end">-@iqd($order->discount)</td></tr>@endif
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
                <dt>الاسم</dt><dd>@if ($order->customer)<a href="{{ route('admin.customers.show', $order->customer) }}" style="color:var(--link)">{{ $order->customer_name }}</a> <span class="pill pill--ok">له حساب</span>@else{{ $order->customer_name }}@endif</dd>
                <dt>الموبايل</dt><dd><a class="num" href="tel:{{ $order->phone }}" style="color:var(--water-deep)">{{ $order->phone }}</a></dd>
                <dt>المنطقة</dt><dd>{{ $order->area }}</dd>
                <dt>العنوان</dt><dd>{{ $order->address }}</dd>
                @if ($order->notes)<dt>ملاحظات</dt><dd>{{ $order->notes }}</dd>@endif
            </dl>
            <div class="inline" style="margin-top:16px">
                <a class="btn btn--ghost btn--sm" href="tel:{{ $order->phone }}">@icon('phone') اتصال</a>
                <a class="btn btn--ghost btn--sm" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($order->phone, 'مرحباً '.$order->customer_name.'، معك صيدلية الدلو بخصوص طلبك رقم '.$order->number) }}">@icon('whatsapp') واتساب</a>
            </div>
        </div>

        @if ($previous->count())
        <div class="card">
            <h2>طلباته السابقة</h2>
            <ul class="statuslist">
                @foreach ($previous as $p)
                    <li><a href="{{ route('admin.orders.show', $p) }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><span class="num">{{ $p->created_at->format('j/n') }}</span> · {{ $p->type === 'prescription' ? 'وصفة' : $p->items->pluck('name')->implode('، ') }}</span><span class="pill pill--{{ $p->status }}">{{ $p->status_label }}</span></a></li>
                @endforeach
            </ul>
        </div>
        @endif

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
            @php
                $statusText = match ($order->status) {
                    'confirmed' => 'أكدنا طلبك ونبدأ تجهيزه.',
                    'preparing' => 'طلبك قيد التجهيز الآن.',
                    'shipping' => \App\Support\Util::isPickup($order->area) ? 'طلبك جاهز للاستلام من الصيدلية.' : 'طلبك خرج للتوصيل وسيصلك قريباً.',
                    'delivered' => 'تم تسليم طلبك. نتمنى لك السلامة.',
                    'cancelled' => 'نعتذر، أُلغي طلبك.',
                    default => 'استلمنا طلبك وسنتصل بك للتأكيد.',
                };
            @endphp
            <a class="btn btn--ghost btn--sm" style="margin-top:12px" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($order->phone, 'مرحباً '.$order->customer_name.'، معك صيدلية الدلو. طلبك رقم '.$order->number.': '.$statusText."\n".route('track', ['number' => $order->number, 'phone' => $order->phone])) }}">@icon('whatsapp') أبلغ الزبون بالحالة على واتساب</a>
            <p class="card__sub" style="margin:12px 0 0">عند الإلغاء ترجع الكميات إلى المخزون تلقائياً. الزبون المسجل يصله إشعار بكل تغيير في حسابه.</p>
        </div>
    </div>
</div>
@endsection
