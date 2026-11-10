@extends('layouts.admin')
@section('title', 'المنتجات')

@section('content')
<div class="head">
    <div><h1>المنتجات</h1><p><span class="num">{{ $products->total() }}</span> منتج</p></div>
    <div class="head__actions">
        <a class="btn btn--ghost" href="{{ route('admin.import') }}">@icon('sheet') استيراد من Excel</a>
        <a class="btn" href="{{ route('admin.products.create') }}">أضف منتجاً</a>
    </div>
</div>

<form class="toolbar" method="get">
    <input class="grow" type="search" name="q" value="{{ request('q') }}" placeholder="ابحث بالاسم أو الاسم العلمي أو الباركود" aria-label="بحث في المنتجات">
    <select name="category" style="width:auto" aria-label="القسم">
        <option value="">كل الأقسام</option>
        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
    </select>
    <select name="stock" style="width:auto" aria-label="المخزون">
        <option value="">كل الكميات</option>
        <option value="low" @selected(request('stock') === 'low')>قارب على النفاد</option>
    </select>
    <button class="btn btn--ghost">تصفية</button>
</form>

<form method="post" action="{{ route('admin.products.bulk') }}" id="bulk">
    @csrf
    <div class="card">
        <div class="toolbar">
            <label class="check"><input type="checkbox" onclick="document.querySelectorAll('#bulk [name=\'ids[]\']').forEach(c => c.checked = this.checked)"> تحديد الكل</label>
            <select name="action" style="width:auto" aria-label="إجراء على المحدد">
                <option value="activate">إظهار في المتجر</option>
                <option value="deactivate">إخفاء من المتجر</option>
                <option value="delete">حذف</option>
            </select>
            <button class="btn btn--ghost btn--sm" onclick="return this.form.action.value !== 'delete' || confirm('حذف المنتجات المحددة نهائياً؟')">نفّذ على المحدد</button>
        </div>
        <div class="tablewrap">
            <table>
                <thead><tr><th></th><th>المنتج</th><th>القسم</th><th>السعر</th><th>الكمية</th><th>الحالة</th><th></th></tr></thead>
                <tbody>
                @forelse ($products as $p)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $p->id }}" aria-label="تحديد {{ $p->name }}" style="width:18px;height:18px"></td>
                        <td>
                            <div class="withthumb">
                                <span class="thumb">@if ($p->image_url)<img src="{{ $p->image_url }}" alt="">@else @icon($p->icon) @endif</span>
                                <span>{{ $p->name }}<span class="t-sub" dir="ltr" style="text-align:end">{{ $p->scientific_name }}</span></span>
                            </div>
                        </td>
                        <td>{{ $p->category->name ?? 'بدون قسم' }}</td>
                        <td style="white-space:nowrap">@iqd($p->price) @if ($p->discount)<span class="pill pill--bad num">-{{ $p->discount }}%</span>@endif</td>
                        <td><span @class(['pill', 'pill--bad' => $p->stock < 1, 'pill--new' => $p->stock > 0 && $p->stock <= 5]) ><span class="num">{{ $p->stock }}</span></span></td>
                        <td>
                            <span @class(['pill', 'pill--ok' => $p->is_active])>{{ $p->is_active ? 'ظاهر' : 'مخفي' }}</span>
                            @if ($p->requires_prescription)<span class="pill">وصفة</span>@endif
                        </td>
                        <td class="t-end"><a class="btn btn--sm btn--ghost" href="{{ route('admin.products.edit', $p) }}">تعديل</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">لا توجد منتجات. أضف منتجاً أو استورد قائمتك من Excel.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
{{ $products->links() }}
@endsection
