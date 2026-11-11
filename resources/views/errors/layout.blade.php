<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | صيدلية الدلو</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <script>(function(){try{var t=localStorage.getItem('theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light');document.documentElement.dataset.theme=t}catch(e){}})()</script>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --ink: #0b2545; --soft: #46597a; --bg: #edf5f6; --card: #fff; --water: #13838f; --line: #d6e4e7; }
        html[data-theme=dark] { --ink: #e7eef6; --soft: #a3b6cb; --bg: #0b1626; --card: #142439; --line: #28425f; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 20px; font-family: "Tajawal", system-ui, sans-serif; background: var(--bg); color: var(--ink); line-height: 1.7; }
        main { width: min(520px, 100%); background: var(--card); border: 1.5px solid var(--line); border-radius: 20px; padding: 40px 32px; text-align: center; }
        .mark { width: 64px; height: 64px; border-radius: 18px; background: #0b2545; display: grid; place-items: center; margin: 0 auto 18px; }
        .code { font-size: 15px; font-weight: 700; color: var(--water); direction: ltr; }
        h1 { margin: 6px 0 8px; font-size: 28px; font-weight: 800; line-height: 1.4; }
        p { margin: 0 0 22px; color: var(--soft); font-size: 18px; }
        .btns { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a { display: inline-flex; align-items: center; min-height: 46px; padding: 0 22px; border-radius: 10px; font-weight: 700; text-decoration: none; background: var(--water); color: #fff; }
        a.ghost { background: transparent; color: var(--ink); box-shadow: inset 0 0 0 1.5px var(--line); }
        a:focus-visible { outline: 3px solid #f4b63f; outline-offset: 2px; }
    </style>
</head>
<body>
<main>
    <div class="mark"><svg width="42" height="42" viewBox="0 0 30 30" fill="none" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12.5 8 7.5l5 5 5-5 5 5 4-4" stroke="#5ed3d9"/><path d="M3 22.5 8 17.5l5 5 5-5 5 5 4-4" stroke="#fff"/></svg></div>
    <div class="code">@yield('code')</div>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <div class="btns">@yield('actions')</div>
</main>
</body>
</html>
