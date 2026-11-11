@extends('layouts.shop')
@section('title', 'استعادة كلمة المرور')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <div class="authbox">
        <h1 class="page__title">استعادة كلمة المرور</h1>

        @if (! $available)
            <p class="page__sub">راسل الصيدلية أو اتصل بها من رقمك المسجل، فتعطيك كلمة مرور مؤقتة.</p>
            <div class="panel" style="display:grid;gap:10px">
                @if ($site['whatsapp'])<a class="btn btn--wa btn--block" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($site['whatsapp'], 'مرحباً، نسيت كلمة مرور حسابي في موقع '.$site['name'].'. رقمي المسجل: ') }}">@icon('whatsapp') راسل الصيدلية</a>@endif
                <a class="btn btn--ghost btn--block" href="tel:{{ $site['phone'] }}">@icon('phone') <span class="num">{{ $site['phone'] }}</span></a>
            </div>
        @elseif ($sent)
            <p class="page__sub">اكتب الكود الذي وصلك على تيليجرام إلى الرقم <span class="num">{{ $sent }}</span>، وكلمة المرور الجديدة.</p>
            <form class="panel" method="post" action="{{ route('customer.reset') }}">
                @csrf
                <div class="form">
                    <div @class(['field', 'bad' => $errors->has('code')])>
                        <label for="code">الكود</label>
                        <input id="code" name="code" inputmode="numeric" dir="ltr" autocomplete="one-time-code" maxlength="6" required autofocus>
                        @error('code')<span class="err">{{ $message }}</span>@enderror
                    </div>
                    <div @class(['field', 'bad' => $errors->has('password')])>
                        <label for="password">كلمة المرور الجديدة</label>
                        <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password" minlength="6" required>
                        @error('password')<span class="err">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="password_confirmation">تأكيدها</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" dir="ltr" autocomplete="new-password" required>
                    </div>
                </div>
                <button class="btn btn--block" style="margin-top:18px">غيّر كلمة المرور</button>
            </form>
        @else
            <p class="page__sub">اكتب رقم موبايلك المسجل ونرسل لك كوداً على تيليجرام.</p>
            <form class="panel" method="post" action="{{ route('customer.forgot') }}">
                @csrf
                <div @class(['field', 'bad' => $errors->has('phone')])>
                    <label for="phone">رقم الموبايل</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" dir="ltr" style="text-align:end" placeholder="07701234567" required autofocus>
                    @error('phone')<span class="err">{{ $message }}</span>@enderror
                </div>
                <button class="btn btn--block" style="margin-top:18px">أرسل الكود</button>
            </form>
        @endif
        <p class="authswap"><a href="{{ route('customer.login') }}">رجوع لتسجيل الدخول</a></p>
    </div>
</div>
@endsection
