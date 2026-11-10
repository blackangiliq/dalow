<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') | @endif{{ $site['name'] }}</title>
    <meta name="description" content="@yield('description', $site['name'].' في '.$site['address'].'. اطلب الأدوية والمستلزمات الطبية أونلاين مع توصيل لكل مناطق بغداد والدفع عند الاستلام.')">
    <meta name="theme-color" content="#0b2545">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ filemtime(public_path('css/shop.css')) }}">
</head>
<body>
<div class="topbar">
    <div class="wrap">
        <span>@icon('pin') {{ $site['address'] }}</span>
        <span class="hide-s">@icon('clock') {{ $site['hours'] }}</span>
        <a class="push" href="tel:{{ $site['phone'] }}">@icon('phone') <span class="num">{{ $site['phone'] }}</span></a>
    </div>
</div>

<header class="masthead">
    <div class="wrap">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand__mark">
                <svg viewBox="0 0 30 30" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12.500 8 7.500l5 5 5-5 5 5 4-4" stroke="#5ed3d9"/>
                    <path d="M3 22.500 8 17.500l5 5 5-5 5 5 4-4" stroke="#fff"/>
                </svg>
            </span>
            <span class="brand__name">{{ $site['name'] }}<span class="brand__sub">زيونة، محلة 712</span></span>
        </a>

        @sectionMissing('heroSearch')
        <form class="search" action="{{ route('products') }}" role="search" data-suggest="{{ route('suggest') }}">
            <label class="sr" for="q">ابحث عن دواء</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="ابحث بالاسم التجاري أو العلمي" autocomplete="off">
            <button aria-label="ابحث">@icon('search')</button>
        </form>
        @else
        <span style="flex:1"></span>
        @endif

        <nav class="actions" aria-label="روابط سريعة">
            <a href="{{ route('prescription') }}">@icon('upload') <span class="txt">اطلب بوصفة</span></a>
            <a href="{{ route('track') }}">@icon('truck') <span class="txt">تتبع طلبك</span></a>
            <a class="cart" href="{{ route('cart') }}">@icon('cart') <span class="txt">السلة</span> <b class="num" data-cart-count>{{ $cartCount }}</b></a>
        </nav>
    </div>
</header>

<nav class="catbar" aria-label="الأقسام">
    <div class="wrap">
        <a href="{{ route('products') }}" @class(['on' => request()->routeIs('products') && ! request('offers')])>كل المنتجات</a>
        @foreach ($navCategories as $c)
            <a href="{{ route('category', $c) }}" @class(['on' => request()->route('category')?->id === $c->id])>{{ $c->name }}</a>
        @endforeach
        <a class="hot" href="{{ route('products', ['offers' => 1]) }}">العروض</a>
    </div>
</nav>

<main>
    @yield('content')
</main>

<footer class="foot">
    <div class="wrap">
        <div>
            <h3>{{ $site['name'] }}</h3>
            <p>صيدلية الحي في زيونة. نجهز طلبك بيد صيدلاني مختص ونوصله إلى باب بيتك في كل مناطق بغداد.</p>
        </div>
        <div>
            <h3>تسوق</h3>
            <ul>
                @foreach ($navCategories->take(5) as $c)
                    <li><a href="{{ route('category', $c) }}">{{ $c->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h3>تواصل معنا</h3>
            <ul>
                <li>{{ $site['address'] }}</li>
                <li>{{ $site['hours'] }}</li>
                <li><a class="num" href="tel:{{ $site['phone'] }}">{{ $site['phone'] }}</a></li>
                <li><a href="{{ route('track') }}">تتبع طلبك</a></li>
            </ul>
        </div>
        <div class="foot__note">
            <span>© <span class="num">{{ date('Y') }}</span> {{ $site['name'] }}</span>
            <span>المعلومات في الموقع لا تغني عن استشارة الطبيب أو الصيدلاني.</span>
        </div>
    </div>
</footer>

@if ($site['whatsapp'])
<a class="wa-float" href="https://wa.me/{{ preg_replace('/\D/', '', $site['whatsapp']) }}" target="_blank" rel="noopener" aria-label="راسلنا على واتساب">@icon('whatsapp')</a>
@endif

<div class="toast @if(session('error')) bad @endif" role="status" aria-live="polite" data-toast data-cart-url="{{ route('cart') }}"
     @if(session('ok') || session('error')) data-initial="{{ session('ok') ?? session('error') }}" @endif></div>
<script src="{{ asset('js/shop.js') }}?v={{ filemtime(public_path('js/shop.js')) }}" defer></script>
</body>
</html>
