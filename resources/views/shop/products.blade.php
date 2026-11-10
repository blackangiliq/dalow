@extends('layouts.shop')
@section('title', $category ? $category->name.': الأسعار والتوصيل في بغداد' : ($q !== '' ? 'نتائج البحث عن '.$q : (request('offers') ? 'عروض وخصومات الأدوية' : 'كل الأدوية والمنتجات')))
@section('description', $category ? 'تسوق '.$category->name.' من '.$site['name'].' بأسعار واضحة وتوصيل لكل مناطق بغداد والدفع عند الاستلام.' : '')
@if ($q !== '' || request()->hasAny(['sort', 'available', 'page']))
    @section('noindex', '1')
@endif

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

    @if ($search && $products->total())
        @if ($search['corrected'])
            <div class="searchnote">@icon('info') <span>لم نجد «{{ $q }}» حرفياً، فعرضنا أقرب ما يطابقها: <strong dir="ltr">{{ $search['corrected'] }}</strong></span></div>
        @elseif ($search['via'] === 'synonym')
            <div class="searchnote">@icon('info') <span>هذه منتجات يبحث عنها من يكتب «{{ $q }}». استشر {{ $site['pharmacist_label'] }} قبل اختيار الدواء: <a href="{{ $consultUrl }}" target="_blank" rel="noopener" data-track="consult">اسألها الآن</a></span></div>
        @elseif ($search['via'] === 'partial')
            <div class="searchnote">@icon('info') <span>لم نجد منتجاً يطابق كل كلمات بحثك، فعرضنا ما يطابق بعضها.</span></div>
        @endif
    @endif

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
            @if ($q !== '')
                <h2>«{{ $q }}» غير موجود في الموقع حالياً</h2>
                <p>نستطيع توفيره لك. اطلبه بضغطة وسنبحث عنه في المذاخر ونتصل بك، أو ارسل صورة الوصفة.</p>
                @if ($on['requests'])
                <a class="btn btn--buy" href="{{ route('request', ['name' => $q]) }}" data-track="request">اطلب توفير «{{ \Illuminate\Support\Str::limit($q, 30) }}»</a>
                @endif
                <a class="btn btn--wa" href="{{ \App\Support\Util::wa(\App\Support\Util::pharmacistNumber(), 'مرحباً، أنا قادم من موقع '.$site['name'].' وأبحث عن: '.$q) }}" target="_blank" rel="noopener" data-track="consult">@icon('whatsapp') اسأل {{ $site['pharmacist_label'] }}</a>
                @if ($on['prescription'])<a class="btn btn--ghost" href="{{ route('prescription') }}">ارسل الوصفة</a>@endif
                @if ($trending)
                    <div class="chips"><span>جرّب أيضاً:</span>@foreach ($trending as $term)<a href="{{ route('products', ['q' => $term]) }}" data-track="chip">{{ $term }}</a>@endforeach</div>
                @endif
            @else
                <h2>لا توجد منتجات هنا حالياً</h2>
                <p>جرّب إلغاء التصفية أو تصفح كل المنتجات.</p>
                <a class="btn" href="{{ route('products') }}">كل المنتجات</a>
            @endif
        </div>
    @endif
</div>
@endsection
