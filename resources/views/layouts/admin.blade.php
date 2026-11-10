<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | لوحة تحكم صيدلية الدلو</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@500;600;700&family=IBM+Plex+Sans+Arabic:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<div class="shell">
    <aside class="side">
        <a class="side__brand" href="{{ route('admin.dashboard') }}">
            <svg viewBox="0 0 30 30" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 12.500 8 7.500l5 5 5-5 5 5 4-4" stroke="#5ed3d9"/><path d="M3 22.500 8 17.500l5 5 5-5 5 5 4-4" stroke="#fff"/>
            </svg>
            صيدلية الدلو
        </a>
        <nav aria-label="القائمة">
            <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>@icon('home') الرئيسية</a>
            <a href="{{ route('admin.orders.index') }}" @class(['on' => request()->routeIs('admin.orders.*')])>@icon('list') الطلبات @if ($pendingOrders)<span class="count num">{{ $pendingOrders }}</span>@endif</a>
            <a href="{{ route('admin.products.index') }}" @class(['on' => request()->routeIs('admin.products.*')])>@icon('pill') المنتجات</a>
            <a href="{{ route('admin.import') }}" @class(['on' => request()->routeIs('admin.import*')])>@icon('sheet') استيراد من Excel</a>
            <a href="{{ route('admin.categories.index') }}" @class(['on' => request()->routeIs('admin.categories.*')])>@icon('tag') الأقسام</a>
            <a href="{{ route('admin.staff.index') }}" @class(['on' => request()->routeIs('admin.staff.*')])>@icon('users') الموظفون والإشعارات</a>
            <a href="{{ route('admin.settings') }}" @class(['on' => request()->routeIs('admin.settings')])>@icon('gear') الإعدادات</a>
        </nav>
        <div class="side__end">
            <a href="{{ route('home') }}" target="_blank" rel="noopener">@icon('cart') عرض المتجر</a>
            <form method="post" action="{{ route('admin.logout') }}">@csrf <button>@icon('logout') تسجيل الخروج</button></form>
        </div>
    </aside>

    <main class="main">
        @if (session('ok'))<div class="flash flash--ok" role="status">@icon('check') <span>{{ session('ok') }}</span></div>@endif
        @if (session('error'))<div class="flash flash--bad" role="alert">@icon('alert') <span>{{ session('error') }}</span></div>@endif
        @if ($errors->any())
            <div class="flash flash--bad" role="alert">@icon('alert')
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
</body>
</html>
