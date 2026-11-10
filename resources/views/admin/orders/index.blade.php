@extends('layouts.admin')
@section('title', 'الطلبات')

@section('content')
<div class="head">
    <div><h1>الطلبات</h1></div>
    <a class="btn btn--ghost" href="{{ route('admin.orders.export', request()->only('status')) }}">@icon('sheet') تصدير إلى Excel</a>
</div>

<div class="tabs">
    <a href="{{ route('admin.orders.index') }}" @class(['on' => ! request('status')])>الكل</a>
    @foreach (\App\Models\Order::STATUSES as $key => $label)
        <a href="{{ route('admin.orders.index', ['status' => $key]) }}" @class(['on' => request('status') === $key])>{{ $label }} <span class="num">({{ $counts[$key] ?? 0 }})</span></a>
    @endforeach
</div>

<form class="toolbar" method="get">
    @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <input class="grow" type="search" name="q" value="{{ request('q') }}" placeholder="ابحث برقم الطلب أو الموبايل أو الاسم" aria-label="بحث في الطلبات">
    <button class="btn btn--ghost">ابحث</button>
</form>

<div class="card">
    <div class="tablewrap" style="margin-top:-22px">
        <table>
            <thead><tr><th>الرقم</th><th>الزبون</th><th>المنطقة</th><th>المواد</th><th>المجموع</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td class="num" style="font-weight:600">{{ $o->number }}</td>
                    <td>{{ $o->customer_name }}<span class="t-sub num">{{ $o->phone }}</span></td>
                    <td>{{ $o->area }}</td>
                    <td>{{ $o->type === 'prescription' ? 'وصفة طبية' : $o->items_count.' مادة' }}</td>
                    <td>{{ $o->type === 'prescription' && ! $o->total ? 'يحدد لاحقاً' : \App\Support\Util::money($o->total) }}</td>
                    <td><span class="pill pill--{{ $o->status }}">{{ $o->status_label }}</span></td>
                    <td><span class="t-sub num">{{ $o->created_at->format('Y-m-d H:i') }}</span></td>
                    <td class="t-end"><a class="btn btn--sm btn--ghost" href="{{ route('admin.orders.show', $o) }}">فتح</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="empty">لا توجد طلبات مطابقة.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $orders->links() }}
@endsection
