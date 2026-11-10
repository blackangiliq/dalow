@extends('layouts.admin')
@section('title', 'الأقسام')

@section('content')
<div class="head"><div><h1>الأقسام</h1><p>ترتب المنتجات في المتجر وتحدد لون ورمز كل قسم</p></div></div>

<div class="card">
    <h2>قسم جديد</h2>
    <form method="post" action="{{ route('admin.categories.store') }}" class="inline">
        @csrf
        <input type="text" name="name" placeholder="اسم القسم" aria-label="اسم القسم" required style="flex:1;min-width:180px">
        <select name="icon" aria-label="الرمز">@foreach (\App\Models\Category::ICONS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
        <input type="color" name="color" value="#13838f" aria-label="اللون">
        <input type="number" name="sort" value="{{ $categories->count() + 1 }}" min="0" style="width:80px" aria-label="الترتيب">
        <button class="btn">أضف القسم</button>
    </form>
</div>

<div class="card">
    <h2>الأقسام الحالية</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الاسم</th><th>الرمز</th><th>اللون</th><th>الترتيب</th><th>المنتجات</th><th></th></tr></thead>
            <tbody>
            @forelse ($categories as $c)
                <tr>
                    <td><input form="cat{{ $c->id }}" type="text" name="name" value="{{ $c->name }}" aria-label="اسم القسم" required></td>
                    <td><select form="cat{{ $c->id }}" name="icon" aria-label="الرمز">@foreach (\App\Models\Category::ICONS as $k => $l)<option value="{{ $k }}" @selected($c->icon === $k)>{{ $l }}</option>@endforeach</select></td>
                    <td><input form="cat{{ $c->id }}" type="color" name="color" value="{{ $c->color }}" aria-label="اللون"></td>
                    <td><input form="cat{{ $c->id }}" type="number" name="sort" value="{{ $c->sort }}" min="0" style="width:80px" aria-label="الترتيب"></td>
                    <td class="num">{{ $c->products_count }}</td>
                    <td>
                        <div class="t-actions">
                            <form id="cat{{ $c->id }}" method="post" action="{{ route('admin.categories.update', $c->id) }}">@csrf @method('put')<button class="btn btn--sm">حفظ</button></form>
                            <form method="post" action="{{ route('admin.categories.destroy', $c->id) }}" onsubmit="return confirm('حذف القسم؟ ستبقى منتجاته بدون قسم.')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">لا توجد أقسام. أضف أول قسم من الأعلى.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
