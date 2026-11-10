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
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<header class="topbar">
    <button type="button" data-nav-toggle aria-label="فتح القائمة" aria-expanded="false">
        <svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>
    <span>@yield('title')</span>
    <a href="{{ route('admin.orders.index', ['status' => 'new']) }}">طلبات جديدة <span class="count num" data-new-count>{{ $pendingOrders }}</span></a>
</header>

<div class="shell">
    <button class="scrim" type="button" data-nav-toggle aria-label="إغلاق القائمة"></button>
    <aside class="side" id="side">
        <button class="side__close" type="button" data-nav-toggle aria-label="إغلاق القائمة">✕</button>
        <a class="side__brand" href="{{ route('admin.dashboard') }}">
            <svg viewBox="0 0 30 30" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 12.500 8 7.500l5 5 5-5 5 5 4-4" stroke="#5ed3d9"/><path d="M3 22.500 8 17.500l5 5 5-5 5 5 4-4" stroke="#fff"/>
            </svg>
            صيدلية الدلو
        </a>
        <nav aria-label="القائمة">
            <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>@icon('home') الرئيسية</a>
            <a href="{{ route('admin.orders.index') }}" @class(['on' => request()->routeIs('admin.orders.*')])>@icon('list') الطلبات <span class="count num" data-new-count @if (! $pendingOrders) hidden @endif>{{ $pendingOrders }}</span></a>
            <a href="{{ route('admin.products.index') }}" @class(['on' => request()->routeIs('admin.products.*')])>@icon('pill') المنتجات</a>
            <a href="{{ route('admin.import') }}" @class(['on' => request()->routeIs('admin.import*')])>@icon('sheet') استيراد من Excel</a>
            <a href="{{ route('admin.categories.index') }}" @class(['on' => request()->routeIs('admin.categories.*')])>@icon('tag') الأقسام</a>
            <a href="{{ route('admin.messages.index') }}" @class(['on' => request()->routeIs('admin.messages.*')])>@icon('chat') الرسائل <span class="count num" data-unread-count @if (! $unreadMessages) hidden @endif>{{ $unreadMessages }}</span></a>
            <a href="{{ route('admin.faqs.index') }}" @class(['on' => request()->routeIs('admin.faqs.*')])>@icon('info') الأسئلة الشائعة</a>
            <a href="{{ route('admin.staff.index') }}" @class(['on' => request()->routeIs('admin.staff.*')])>@icon('users') الموظفون</a>
            <a href="{{ route('admin.telegram') }}" @class(['on' => request()->routeIs('admin.telegram*')])>@icon('send') إشعارات تيليجرام</a>
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

<div class="livetoast" role="status" aria-live="polite" data-live></div>
<script>
(() => {
    // القائمة المنزلقة في التابلت والموبايل
    const toggles = document.querySelectorAll('[data-nav-toggle]');
    toggles.forEach((b) => b.addEventListener('click', () => {
        const open = document.body.classList.toggle('nav-open');
        toggles[0].setAttribute('aria-expanded', open);
    }));
    addEventListener('keydown', (e) => e.key === 'Escape' && document.body.classList.remove('nav-open'));

    // في الموبايل يتحول كل جدول إلى بطاقات، وعنوان كل خانة يؤخذ من رأس الجدول
    document.querySelectorAll('table').forEach((table) => {
        const heads = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());
        if (!heads.length) return;
        table.classList.add('stack');
        table.querySelectorAll('tbody tr').forEach((tr) => [...tr.children].forEach((td, i) => {
            if (td.colSpan === 1) td.dataset.label = heads[i] ?? '';
        }));
    });

    // تنبيه بالطلبات الجديدة دون تحديث الصفحة
    const live = document.querySelector('[data-live]');
    let lastId = null;
    const setCount = (sel, n) => document.querySelectorAll(sel).forEach((el) => { el.textContent = n; if (el.closest('.side')) el.hidden = !n; });
    const check = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(@json(route('admin.pulse')), { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            setCount('[data-new-count]', data.new);
            setCount('[data-unread-count]', data.unread);
            if (lastId !== null && data.latest_id > lastId && data.latest) {
                live.textContent = 'طلب جديد ' + data.latest.number + ' من ' + data.latest.name;
                const a = document.createElement('a');
                a.href = data.latest.url;
                a.textContent = 'فتح الطلب';
                live.append(a);
                live.classList.add('show');
                document.title = '(' + data.new + ') ' + document.title.replace(/^\(\d+\) /, '');
            }
            lastId = data.latest_id;
        } catch {}
    };
    check();
    setInterval(check, 30000);
})();
</script>
</body>
</html>
