@extends('layouts.admin')
@section('title', 'الزبائن')

@section('content')
<div class="head"><div><h1>الزبائن المسجلون</h1><p><span class="num">{{ $customers->total() }}</span> زبون أنشأ حساباً برقم موبايله</p></div></div>

<form class="toolbar" method="get">
    <input class="grow" type="search" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو رقم الموبايل" aria-label="بحث في الزبائن">
    <button class="btn btn--ghost">ابحث</button>
</form>

<div class="card">
    <div class="tablewrap" style="margin-top:-22px">
        <table>
            <thead><tr><th>الاسم</th><th>الموبايل</th><th>المنطقة</th><th>الطلبات</th><th>تنبيهات السعر</th><th>سجّل</th><th></th></tr></thead>
            <tbody>
            @forelse ($customers as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td><a class="num" href="tel:{{ $c->phone }}" style="color:var(--link)">{{ $c->phone }}</a><span class="t-sub">@if ($c->phone_verified_at)<span class="pill pill--ok">رقم مؤكد</span>@else<span class="pill pill--new">غير مؤكد</span>@endif</span></td>
                    <td>{{ $c->area ?: 'غير محددة' }}</td>
                    <td class="num">{{ $c->orders_count }}</td>
                    <td class="num">{{ $c->favorites_count }}</td>
                    <td><span class="t-sub">{{ $c->created_at->diffForHumans() }}</span></td>
                    <td><div class="t-actions"><a class="btn btn--sm btn--ghost" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($c->phone, 'مرحباً '.$c->name.'، معك صيدلية الدلو.') }}">@icon('whatsapp') واتساب</a>
                        <form method="post" action="{{ route('admin.customers.verify', $c) }}">@csrf @method('put')<button class="btn btn--sm btn--ghost">{{ $c->phone_verified_at ? 'إلغاء التأكيد' : 'تأكيد الرقم' }}</button></form>
                        <form method="post" action="{{ route('admin.customers.password', $c) }}" onsubmit="return confirm('إنشاء كلمة مرور جديدة لـ{{ $c->name }}؟ القديمة تتوقف.')">@csrf @method('put')<button class="btn btn--sm btn--ghost">كلمة مرور جديدة</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">لا يوجد زبائن مسجلون بعد. يسجل الزبون من المتجر ليطلب تنبيه انخفاض السعر ويحفظ طلباته.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $customers->links() }}
@endsection
