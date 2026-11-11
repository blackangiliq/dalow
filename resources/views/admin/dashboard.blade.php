@extends('layouts.admin')
@section('title', 'الرئيسية')

@section('content')
@php $max = max(1, $days->max('orders')); $peak = $days->max('orders'); @endphp
<div class="head">
    <div><h1>أهلاً {{ auth()->user()->name }}</h1><p>{{ now()->translatedFormat('l j F Y') }}</p></div>
    <div class="head__actions">
        @if (auth()->user()->isAdmin())<a class="btn btn--ghost" href="{{ route('admin.import') }}">@icon('sheet') استيراد قائمة أدوية</a>@endif
        <a class="btn" href="{{ route('admin.products.create') }}">أضف منتجاً</a>
    </div>
</div>

@if (! auth()->user()->isAdmin())
@elseif (! $telegramLinked)
    <div class="hint"><strong>إشعارات تيليجرام غير مفعلة.</strong> اربط حساب تيليجرام من صفحة <a href="{{ route('admin.telegram') }}" style="text-decoration:underline">إشعارات تيليجرام</a> ليصل كل طلب جديد إلى الموظفين.</div>
@elseif (! $recipients)
    <div class="hint"><strong>لم تختر من يستلم الإشعارات.</strong> أضف الموظفين أو اخترهم من جهات الاتصال في <a href="{{ route('admin.staff.index') }}" style="text-decoration:underline">صفحة الموظفين</a>.</div>
@elseif ($failedStaff)
    <div class="hint"><strong>{{ $failedStaff }} موظف لم يصله آخر إشعار.</strong> راجع السبب في <a href="{{ route('admin.staff.index') }}" style="text-decoration:underline">صفحة الموظفين</a>.</div>
@endif

@if ($expired)
    <div class="flash flash--bad">@icon('alert') <span><strong>{{ $expired }} منتج انتهت صلاحيته</strong> وأُخفي من المتجر تلقائياً. <a href="{{ route('admin.products.index', ['expiry' => 'expired']) }}" style="text-decoration:underline">راجعها واسحبها من الرفوف</a></span></div>
@endif

<div class="stats stats--6">
    <a class="stat stat--hot" href="{{ route('admin.orders.index', ['status' => 'new']) }}"><span>طلبات تنتظر التأكيد</span><b class="num" data-new-count>{{ $newOrders }}</b><small>اضغط لعرضها</small></a>
    <div class="stat"><span>طلبات اليوم</span><b class="num">{{ $todayOrders }}</b><small>@iqd($todaySales)</small></div>
    <div class="stat"><span>مبيعات الشهر المسلّمة</span><b>{{ number_format($monthSales) }}</b><small>دينار عراقي</small></div>
    <a class="stat" href="{{ auth()->user()->isAdmin() ? route('admin.analytics') : '#' }}"><span>زوار اليوم</span><b class="num">{{ $visitorsToday }}</b><small>{{ $visitorsMonth }} هذا الشهر</small></a>
    <a class="stat" href="{{ route('admin.messages.index') }}"><span>رسائل غير مقروءة</span><b class="num">{{ $unread }}</b><small>من صفحة تواصل معنا</small></a>
    <a class="stat" href="{{ route('admin.products.index', ['stock' => 'low']) }}"><span>منتجات قاربت على النفاد</span><b class="num">{{ $lowStock->count() }}{{ $lowStock->count() >= 6 ? '+' : '' }}</b><small>من {{ $productCount }} منتج</small></a>
</div>

<div class="cols">
    <div class="card">
        <div class="card__head"><h2>الطلبات في آخر 14 يوماً</h2><span class="t-sub">المجموع <span class="num">{{ $days->sum('orders') }}</span> طلب</span></div>
        <div class="chart" role="img" aria-label="عدد الطلبات لكل يوم في آخر 14 يوماً. المجموع {{ $days->sum('orders') }} طلب.">
            @foreach ($days as $d)
                @php $h = round($d['orders'] / $max * 100); @endphp
                <div @class(['chart__col', 'chart__col--zero' => ! $d['orders']]) style="--h: {{ $h }}%" tabindex="0">
                    @if ($d['orders'] && ($d['orders'] === $peak || $loop->last))<span class="chart__val num">{{ $d['orders'] }}</span>@endif
                    <span class="chart__bar" style="height: {{ $h }}%"></span>
                    <span class="chart__tip">{{ $d['date']->translatedFormat('l j/n') }}: <span class="num">{{ $d['orders'] }}</span> طلب، {{ number_format($d['sales']) }} د.ع</span>
                </div>
            @endforeach
        </div>
        <div class="chart__axis" aria-hidden="true">
            @foreach ($days as $d)<span class="num">{{ $d['date']->format('j/n') }}</span>@endforeach
        </div>
        <details style="margin-top:12px">
            <summary style="cursor:pointer;color:var(--water-deep);font-weight:700">عرض الأرقام كجدول</summary>
            <div class="tablewrap" style="margin-top:10px">
                <table>
                    <thead><tr><th>اليوم</th><th>الطلبات</th><th>المبيعات</th></tr></thead>
                    <tbody>@foreach ($days->reverse() as $d)<tr><td>{{ $d['date']->translatedFormat('l j/n') }}</td><td class="num">{{ $d['orders'] }}</td><td>@iqd($d['sales'])</td></tr>@endforeach</tbody>
                </table>
            </div>
        </details>
    </div>

    <div class="card">
        <h2>الطلبات حسب الحالة</h2>
        <ul class="statuslist">
            @foreach (\App\Models\Order::STATUSES as $key => $label)
                <li><a href="{{ route('admin.orders.index', ['status' => $key]) }}"><span class="pill pill--{{ $key }}">{{ $label }}</span><b class="num">{{ $statusCounts[$key] ?? 0 }}</b></a></li>
            @endforeach
        </ul>
    </div>
</div>

<div class="card">
    <div class="card__head"><h2>آخر الطلبات</h2><a href="{{ route('admin.orders.index') }}">كل الطلبات</a></div>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الرقم</th><th>الزبون</th><th>المنطقة</th><th>المجموع</th><th>الحالة</th><th>الوقت</th><th></th></tr></thead>
            <tbody>
            @forelse ($recent as $o)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $o) }}" class="num" style="font-weight:800;color:var(--water-deep)">{{ $o->number }}</a></td>
                    <td>{{ $o->customer_name }}</td>
                    <td>{{ $o->area }}</td>
                    <td>{{ $o->type === 'prescription' && ! $o->total ? 'وصفة طبية' : \App\Support\Util::money($o->total) }}</td>
                    <td><span class="pill pill--{{ $o->status }}">{{ $o->status_label }}</span></td>
                    <td><span class="t-sub">{{ $o->created_at->diffForHumans() }}</span></td>
                    <td>
                        <div class="t-actions">
                            @if ($o->status === 'new')
                                <form method="post" action="{{ route('admin.orders.update', $o) }}">@csrf @method('put')<input type="hidden" name="status" value="confirmed"><button class="btn btn--sm">تأكيد</button></form>
                            @endif
                            <a class="btn btn--sm btn--ghost" href="{{ route('admin.orders.show', $o) }}">فتح</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">لا توجد طلبات بعد. ستظهر هنا أول ما يطلب زبون من الموقع.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid3 grid3--4">
    <div class="card">
        <h2>الأكثر مبيعاً في 30 يوماً</h2>
        @if ($top->count())
            @php $topMax = max(1, $top->max('sold')); @endphp
            <ol class="rank">
                @foreach ($top as $t)
                    <li><span>{{ $t->name }}</span><b class="num">{{ $t->sold }}</b><i style="--w: {{ round($t->sold / $topMax * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">تظهر هنا المنتجات الأكثر طلباً بعد أول الطلبات.</p>
        @endif
    </div>

    <div class="card">
        <div class="card__head"><h2>مخزون قارب على النفاد</h2><a href="{{ route('admin.products.index', ['stock' => 'low']) }}">الكل</a></div>
        <ul class="statuslist">
            @forelse ($lowStock as $p)
                <li><a href="{{ route('admin.products.edit', $p) }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->name }}</span><span @class(['pill', 'pill--bad' => $p->stock < 1, 'pill--new' => $p->stock > 0])>{{ $p->stock < 1 ? 'نفد' : 'بقي '.$p->stock }}</span></a></li>
            @empty
                <li class="empty">كل المنتجات متوفرة بكميات كافية.</li>
            @endforelse
        </ul>
    </div>

    <div class="card">
        <div class="card__head"><h2>تنتهي صلاحيتها قريباً</h2><a href="{{ route('admin.products.index', ['expiry' => 'soon']) }}">الكل</a></div>
        <ul class="statuslist">
            @forelse ($expiringSoon as $p)
                <li><a href="{{ route('admin.products.edit', $p) }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $p->name }}</span><span @class(['pill', 'pill--bad' => $p->expires_at->diffInDays(today(), true) <= 30, 'pill--new' => $p->expires_at->diffInDays(today(), true) > 30])>بعد {{ (int) today()->diffInDays($p->expires_at) }} يوماً</span></a></li>
            @empty
                <li class="empty">لا يوجد منتج ينتهي خلال 90 يوماً. أدخل تواريخ الانتهاء في المنتجات لتصلك التنبيهات.</li>
            @endforelse
        </ul>
    </div>

    <div class="card">
        <h2>الإشعارات والوصول السريع</h2>
        <div @class(['channel', 'channel--on' => $telegramLinked && $recipients])>
            <span class="channel__dot"></span>
            <div>
                <b>{{ $telegramLinked ? 'حساب تيليجرام مربوط' : 'حساب تيليجرام غير مربوط' }}</b>
                <span class="t-sub">{{ $recipients }} موظف يستلم إشعار كل طلب</span>
            </div>
        </div>
        <div class="quick" style="margin-top:16px">
            <a href="{{ route('admin.orders.export') }}">@icon('sheet') تصدير الطلبات</a>
            <a href="{{ route('admin.products.export') }}">@icon('sheet') تصدير المنتجات</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.telegram') }}">@icon('send') تيليجرام</a>
                <a href="{{ route('admin.appearance') }}">@icon('scan') واجهة الموقع</a>
            @endif
        </div>
    </div>
</div>
@endsection
