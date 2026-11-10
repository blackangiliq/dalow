@extends('layouts.shop')
@section('title', $category->name ?? ($q !== '' ? 'نتائج البحث عن '.$q : 'كل المنتجات'))

@section('content')
<div class="page wrap">
    <nav class="crumbs" aria-label="مسار الصفحة">
        <a href="{{ route('home') }}">الرئيسية</a>
        <span>{{ $category->name ?? 'المنتجات' }}</span>
    </nav>
    <h1 class="page__title">
        @if ($q !== '') نتائج البحث عن «{{ $q }}»
        @elseif (request('offers')) العروض
        @else {{ $category->name ?? 'كل المنتجات' }}
        @endif
    </h1>
    <p class="page__sub"><span class="num">{{ $products->total() }}</span> منتج</p>

    <form class="filters" method="get">
        @if ($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
        <label><input type="checkbox" name="available" value="1" @checked(request('available')) onchange="this.form.submit()"> المتوفر فقط</label>
        <label><input type="checkbox" name="offers" value="1" @checked(request('offers')) onchange="this.form.submit()"> عليه خصم</label>
        <label class="push">الترتيب
            <select name="sort" onchange="this.form.submit()">
                <option value="">الأكثر طلباً</option>
                <option value="price_asc" @selected(request('sort') === 'price_asc')>السعر: من الأقل</option>
                <option value="price_desc" @selected(request('sort') === 'price_desc')>السعر: من الأعلى</option>
                <option value="name" @selected(request('sort') === 'name')>الاسم</option>
            </select>
        </label>
        <noscript><button class="btn btn--sm">تطبيق</button></noscript>
    </form>

    @if ($products->count())
        <div class="grid">
            @foreach ($products as $p)
                @include('partials.product-card', ['p' => $p])
            @endforeach
        </div>
        {{ $products->links() }}
    @else
        <div class="empty">
            @icon('search')
            <h2>لم نجد منتجاً مطابقاً</h2>
            <p>جرّب كتابة الاسم العلمي أو جزء من الاسم. وإذا كان الدواء في وصفتك، ارسل صورتها ونحن نوفره لك.</p>
            <a class="btn" href="{{ route('prescription') }}">ارسل الوصفة</a>
            <a class="btn btn--ghost" href="{{ route('products') }}">كل المنتجات</a>
        </div>
    @endif
</div>
@endsection
