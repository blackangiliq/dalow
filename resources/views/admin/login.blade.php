<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>دخول لوحة التحكم | صيدلية الدلو</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<div class="login">
    <form class="card" method="post" action="{{ route('login') }}">
        @csrf
        <h1>لوحة تحكم صيدلية الدلو</h1>
        <p style="color:var(--ink-soft)">سجّل الدخول لإدارة الطلبات والمنتجات.</p>
        @error('email')<div class="flash flash--bad" style="margin:14px 0 0">{{ $message }}</div>@enderror
        <div class="field">
            <label for="email">البريد الإلكتروني</label>
            <input id="email" name="email" type="email" dir="ltr" value="{{ old('email') }}" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label for="password">كلمة المرور</label>
            <input id="password" name="password" type="password" dir="ltr" autocomplete="current-password" required>
        </div>
        <label class="check" style="margin-top:14px"><input type="checkbox" name="remember" value="1"> تذكرني على هذا الجهاز</label>
        <button class="btn" style="width:100%;margin-top:18px">تسجيل الدخول</button>
    </form>
</div>
</body>
</html>
