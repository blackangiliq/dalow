@extends('layouts.admin')
@section('title', 'الرئيسية')

@section('content')
<div class="head">
    <div><h1>أهلاً {{ auth()->user()->name }}</h1><p>ملخص عمل الصيدلية اليوم</p></div>
    <div class="head__actions">
        <a class="btn btn--ghost" href="{{ route('admin.import') }}">@icon('sheet') استيراد قائمة أدوية</a>
        <a class="btn" href="{{ route('admin.products.create') }}">أضف منتجاً</a>
    </div>
</div>

@if (! $telegramReady)
    <div class="hint"><strong>إشعارات تيليجرام غير مفعلة.</strong> أدخل توكن البوت من <a href="{{ route('admin.settings') }}" style="text-decoration:underline">الإعدادات</a> ليصل كل طلب جديد إلى الموظفين.</div>
@elseif (! $linkedStaff)
    <div class="hint"><strong>لا يوجد موظف مربوط بتيليجرام بعد.</strong> افتح <a href="{{ route('admin.staff.index') }}" style="text-decoration:underline">صفحة الموظفين</a> واتبع خطوات الربط.</div>
@endif

<div class="stats">
    <div class="stat stat--hot"><span>طلبات تنتظر التأكيد</span><b class="num">{{ $newOrders }}</b></div>
    <div class="stat"><span>طلبات اليوم</span><b class="num">{{ $todayOrders }}</b></div>
    <div class="stat"><span>مبيعات الشهر المسلّمة</span><b>@iqd($monthSales)</b></div>
    <div class="stat"><span>موظفون يستلمون الإشعارات</span><b class="num">{{ $linkedStaff }} / {{ $staffCount }}</b></div>
</div>

<div class="cols">
    <div class="card">
        <h2>آخر الطلبات</h2>
        <div class="tablewrap">
            <table>
                <thead><tr><th>الرقم</th><th>الزبون</th><th>المجموع</th><th>الحالة</th><th>الوقت</th></tr></thead>
                <tbody>
                @forelse ($recent as $o)
                    <tr>
                        <td><a href="{{ route('admin.orders.show', $o) }}" class="num" style="font-weight:600;color:var(--water-deep)">{{ $o->number }}</a></td>
                        <td>{{ $o->customer_name }}<span class="t-sub">{{ $o->area }}</span></td>
                        <td>{{ $o->type === 'prescription' ? 'وصفة' : \App\Support\Util::money($o->total) }}</td>
                        <td><span class="pill pill--{{ $o->status }}">{{ $o->status_label }}</span></td>
                        <td><span class="t-sub">{{ $o->created_at->diffForHumans() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">لا توجد طلبات بعد. ستظهر هنا أول ما يطلب زبون من الموقع.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2>مخزون قارب على النفاد</h2>
        <div class="tablewrap">
            <table>
                <tbody>
                @forelse ($lowStock as $p)
                    <tr>
                        <td><a href="{{ route('admin.products.edit', $p) }}">{{ $p->name }}</a></td>
                        <td class="t-end"><span @class(['pill', 'pill--bad' => $p->stock < 1, 'pill--new' => $p->stock > 0])>{{ $p->stock < 1 ? 'نفد' : 'بقي '.$p->stock }}</span></td>
                    </tr>
                @empty
                    <tr><td class="empty">كل المنتجات متوفرة بكميات كافية.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
