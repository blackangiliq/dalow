@php
    $pageTitle = trim($__env->yieldContent('title'));
    $metaDescription = trim($__env->yieldContent('description')) ?: $site['name'].' في '.$site['address'].'. صيدلية أونلاين: اطلب الأدوية والمستلزمات الطبية مع توصيل لكل مناطق بغداد والدفع عند الاستلام، واستشر الصيدلانية مجاناً.';
    $canonical = trim($__env->yieldContent('canonical')) ?: url()->current();
    $fullTitle = $pageTitle !== '' ? $pageTitle.' | '.$site['name'] : $site['name'].' زيونة | صيدلية أونلاين وتوصيل أدوية في بغداد';
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    <link rel="canonical" href="{{ $canonical }}">
    @hasSection('noindex')<meta name="robots" content="noindex, follow">@endif
    @if ($site['google_verification'])<meta name="google-site-verification" content="{{ $site['google_verification'] }}">@endif
    <meta name="theme-color" content="#0b2545">
    <meta property="og:site_name" content="{{ $site['name'] }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($metaDescription, 160) }}">
    <meta property="og:type" content="@yield('ogType', 'website')">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:locale" content="ar_IQ">
    <meta property="og:image" content="@yield('ogImage', asset('og-image.png'))">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    {{-- الوضع الداكن يُطبَّق قبل رسم الصفحة حتى لا تومض بالأبيض --}}
    <script>(function(){try{var t=localStorage.getItem('theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=t}catch(e){}})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ filemtime(public_path('css/shop.css')) }}">
    {{-- بيانات منظمة تعرّف محركات البحث بالصيدلية وعنوانها وبحث الموقع --}}
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            array_filter([
                '@type' => 'Pharmacy',
                '@id' => url('/').'#pharmacy',
                'name' => $site['name'],
                'url' => url('/'),
                'image' => asset('og-image.png'),
                'telephone' => $site['phone'],
                'email' => $site['email'] ?: null,
                'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'زيونة، محلة 712', 'addressLocality' => 'بغداد', 'addressCountry' => 'IQ'],
                'areaServed' => ['@type' => 'City', 'name' => 'بغداد'],
                'openingHours' => $site['hours'],
                'currenciesAccepted' => 'IQD',
                'paymentAccepted' => 'الدفع عند الاستلام',
                'hasMap' => $site['map_url'] ?: null,
                'sameAs' => array_values(array_filter([$site['facebook_url'], $site['instagram_url']])) ?: null,
            ]),
            [
                '@type' => 'WebSite',
                'url' => url('/'),
                'name' => $site['name'],
                'inLanguage' => 'ar',
                'potentialAction' => ['@type' => 'SearchAction', 'target' => route('products').'?q={search_term_string}', 'query-input' => 'required name=search_term_string'],
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @if ($customColors)
    {{-- ألوان اختارها المدير من «واجهة الموقع» --}}
    <style>:root {
        @isset($customColors['water']) --water: {{ $customColors['water'] }}; --water-deep: color-mix(in srgb, {{ $customColors['water'] }} 78%, #000); --link: color-mix(in srgb, {{ $customColors['water'] }} 72%, #000); @endisset
        @isset($customColors['navy']) --navy: {{ $customColors['navy'] }}; @endisset
        @isset($customColors['buy']) --buy: {{ $customColors['buy'] }}; --saffron: {{ $customColors['buy'] }}; @endisset
        @isset($customColors['coral']) --coral: {{ $customColors['coral'] }}; @endisset
    }</style>
    @endif
    @stack('head')
</head>
<body data-capsules="{{ $on['capsules'] ? 1 : 0 }}" data-track-url="{{ route('track.event') }}" class="@yield('bodyClass') {{ $on['tabbar'] ? '' : 'no-tabbar' }}">
@if ($on['promo'] && $promo !== '')
<div class="promo"><a href="{{ route('products', ['offers' => 1]) }}" data-track="promo">{{ $promo }}</a></div>
@endif
@if ($on['topbar'])
<div class="topbar">
    <div class="wrap">
        <span>@icon('pin') {{ $site['address'] }}</span>
        <span class="hide-s">@icon('clock') {{ $site['hours'] }}</span>
        <nav aria-label="روابط المساعدة">
            @if ($on['verify'])<a href="{{ route('verify') }}" data-track="verify">تحقق من دوائك</a>@endif
            @if ($on['track'])<a href="{{ route('track') }}">تتبع طلبك</a>@endif
            @if ($on['faq'])<a href="{{ route('faq') }}">الأسئلة الشائعة</a>@endif
            @if ($on['contact'])<a href="{{ route('contact') }}">تواصل معنا</a>@endif
        </nav>
        <a class="push" href="tel:{{ $site['phone'] }}" data-track="call">@icon('phone') <span class="num">{{ $site['phone'] }}</span></a>
    </div>
</div>
@endif

<header class="masthead">
    <div class="wrap">
        <a class="brand" href="{{ route('home') }}">
            <span class="brand__mark">
                <svg viewBox="0 0 30 30" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12.500 8 7.500l5 5 5-5 5 5 4-4" stroke="#5ed3d9"/>
                    <path d="M3 22.500 8 17.500l5 5 5-5 5 5 4-4" stroke="#fff"/>
                </svg>
            </span>
            <span class="brand__name">{{ $site['name'] }}<span class="brand__sub">{{ $ui['brand_sub'] }}</span></span>
        </a>

        @sectionMissing('heroSearch')
        <form class="search" action="{{ route('products') }}" role="search" @if ($on['suggest']) data-suggest="{{ route('suggest') }}" @endif>
            <label class="sr" for="q">ابحث عن دواء</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="ابحث بالعربي أو الإنجليزي: بنادول، Panadol" autocomplete="off">
            <button aria-label="ابحث">@icon('search')</button>
        </form>
        @else
        <span style="flex:1"></span>
        @endif

        <nav class="actions" aria-label="حسابك وسلتك">
            @if ($on['theme'])
            <button class="iconbtn" type="button" data-theme-toggle data-track="theme" aria-label="تبديل الوضع الداكن والفاتح">
                <span class="theme-moon">@icon('moon')</span><span class="theme-sun">@icon('sun')</span>
            </button>
            @endif
            @if ($on['accounts'])
                @if ($me)
                    <a class="iconbtn" href="{{ route('account', ['tab' => 'notifications']) }}" aria-label="الإشعارات{{ $unreadCount ? ' ('.$unreadCount.' جديدة)' : '' }}">@icon('bell') @if ($unreadCount)<b class="num">{{ $unreadCount }}</b>@endif</a>
                @endif
                <a class="iconbtn" href="{{ route('account', ['tab' => 'favorites']) }}" aria-label="المفضلة">@icon('heart') <b class="num" data-fav-count @if (! $favoriteCount) hidden @endif>{{ $favoriteCount }}</b></a>
                <a class="iconbtn" href="{{ $me ? route('account') : route('customer.login') }}" aria-label="{{ $me ? 'حسابي' : 'تسجيل الدخول' }}">@icon('user')</a>
            @endif
            @if ($on['cart'])
                <a class="cart" href="{{ route('cart') }}">@icon('cart') <span class="txt">السلة</span> <b class="num" data-cart-count>{{ $cartCount }}</b></a>
            @endif
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
        @if ($on['prescription'])<a href="{{ route('prescription') }}">اطلب بوصفة</a>@endif
    </div>
</nav>

<main>
    @yield('content')
</main>

<footer class="foot">
    <div class="wrap">
        <div>
            <h3>{{ $site['name'] }}</h3>
            <p>{{ $ui['footer_about'] }}</p>
        </div>
        <div>
            <h3>خدماتنا</h3>
            <ul>
                @if ($on['verify'])<li><a href="{{ route('verify') }}">تحقق من دوائك</a></li>@endif
                @if ($on['requests'])<li><a href="{{ route('request') }}">اطلب منتجاً غير موجود</a></li>@endif
                @if ($on['prescription'])<li><a href="{{ route('prescription') }}">اطلب بوصفة طبية</a></li>@endif
                @if ($on['consult'])<li><a href="{{ $consultUrl }}" target="_blank" rel="noopener" data-track="consult">استشر {{ $site['pharmacist_label'] }}</a></li>@endif
                @if ($on['track'])<li><a href="{{ route('track') }}">تتبع طلبك</a></li>@endif
                <li><a href="{{ route('products', ['offers' => 1]) }}">العروض</a></li>
            </ul>
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
                <li><a class="num" href="tel:{{ $site['phone'] }}" data-track="call">{{ $site['phone'] }}</a></li>
                @if ($on['faq'])<li><a href="{{ route('faq') }}">الأسئلة الشائعة</a></li>@endif
                @if ($on['contact'])<li><a href="{{ route('contact') }}">راسلنا</a></li>@endif
            </ul>
        </div>
        <div class="foot__note">
            <span>© <span class="num">{{ date('Y') }}</span> {{ $site['name'] }} · <a href="{{ route('privacy') }}">الخصوصية</a></span>
            <span>{{ $ui['footer_note'] }}</span>
        </div>
    </div>
</footer>

@if ($on['consult'])
<a class="consult-float" href="{{ $consultUrl }}" target="_blank" rel="noopener" data-track="consult">@icon('whatsapp') استشر {{ $site['pharmacist_label'] }}</a>
@endif

@if ($on['tabbar'])
<nav class="tabbar" aria-label="التنقل السريع">
    <a href="{{ route('home') }}" data-track="tab" @class(['on' => request()->routeIs('home')])>@icon('home') الرئيسية</a>
    <a href="{{ route('products') }}" data-track="tab" @class(['on' => request()->routeIs('products', 'category', 'product')])>@icon('pill') المنتجات</a>
    @if ($on['prescription'])<a href="{{ route('prescription') }}" data-track="tab" @class(['on' => request()->routeIs('prescription')])>@icon('upload') وصفة</a>@endif
    @if ($on['consult'])<a class="wa" href="{{ $consultUrl }}" target="_blank" rel="noopener" data-track="consult">@icon('whatsapp') استشارة</a>@endif
    @if ($on['cart'])
        <a href="{{ route('cart') }}" data-track="tab" @class(['on' => request()->routeIs('cart', 'checkout')])>@icon('cart') السلة <b class="num" data-cart-count @if (! $cartCount) hidden @endif>{{ $cartCount }}</b></a>
    @elseif ($on['accounts'])
        <a href="{{ $me ? route('account') : route('customer.login') }}" data-track="tab" @class(['on' => request()->routeIs('account')])>@icon('user') حسابي</a>
    @endif
</nav>
@endif

<div class="toast @if(session('error')) bad @endif" role="status" aria-live="polite" data-toast data-cart-url="{{ url('/cart') }}"
     @if(session('ok') || session('error')) data-initial="{{ session('ok') ?? session('error') }}" @endif></div>
<script src="{{ asset('js/shop.js') }}?v={{ filemtime(public_path('js/shop.js')) }}" defer></script>
</body>
</html>
