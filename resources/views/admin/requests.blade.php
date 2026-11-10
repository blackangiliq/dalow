@extends('layouts.admin')
@section('title', 'طلبات توفير منتج')

@section('content')
<div class="head"><div><h1>طلبات توفير منتج</h1><p>منتجات طلبها الزبائن وليست في الموقع</p></div></div>

<div class="tabs">
    <a href="{{ route('admin.requests.index') }}" @class(['on' => ! request('status')])>الكل</a>
    @foreach (\App\Models\ProductRequest::STATUSES as $key => $label)
        <a href="{{ route('admin.requests.index', ['status' => $key]) }}" @class(['on' => request('status') === $key])>{{ $label }} <span class="num">({{ $counts[$key] ?? 0 }})</span></a>
    @endforeach
</div>

@forelse ($requests as $r)
    <article @class(['msg', 'msg--new' => $r->status === 'new'])>
        <div class="msg__head">
            <b>{{ $r->product_name }}</b>
            <span @class(['pill', 'pill--new' => $r->status === 'new', 'pill--confirmed' => $r->status === 'sourcing', 'pill--ok' => $r->status === 'available', 'pill--bad' => $r->status === 'unavailable'])>{{ $r->status_label }}</span>
            <span class="t-sub" style="margin-inline-start:auto">{{ $r->created_at->diffForHumans() }}</span>
        </div>
        @if ($r->details)<p>{{ $r->details }}</p>@endif
        <p class="t-sub" style="margin-top:6px">{{ $r->name }} · <a class="num" href="tel:{{ $r->phone }}" style="color:var(--link)">{{ $r->phone }}</a></p>
        @if ($r->image)<a href="{{ route('admin.requests.image', $r) }}" target="_blank" rel="noopener"><img class="rximg" style="max-height:180px" src="{{ route('admin.requests.image', $r) }}" alt="صورة مرفقة بالطلب"></a>@endif
        <div class="inline">
            <form method="post" action="{{ route('admin.requests.update', $r) }}" class="inline">
                @csrf @method('put')
                <select name="status" aria-label="حالة الطلب">@foreach (\App\Models\ProductRequest::STATUSES as $key => $label)<option value="{{ $key }}" @selected($r->status === $key)>{{ $label }}</option>@endforeach</select>
                <button class="btn btn--sm">حفظ الحالة</button>
            </form>
            <a class="btn btn--sm btn--ghost" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($r->phone, 'مرحباً '.$r->name.'، معك صيدلية الدلو بخصوص طلبك توفير «'.$r->product_name.'».') }}">@icon('whatsapp') واتساب</a>
            <a class="btn btn--sm btn--ghost" href="{{ route('admin.products.create') }}">أضفه كمنتج</a>
            <form method="post" action="{{ route('admin.requests.destroy', $r) }}" onsubmit="return confirm('حذف الطلب؟')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف</button></form>
        </div>
    </article>
@empty
    <div class="card"><p class="empty">لا توجد طلبات. عندما يبحث زبون عن منتج غير موجود نعرض عليه طلب توفيره، ويظهر طلبه هنا.</p></div>
@endforelse
{{ $requests->links() }}
@endsection
