@extends('layouts.shop')
@section('title', 'تسجيل الدخول')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <div class="authbox">
        <h1 class="page__title">تسجيل الدخول</h1>
        @if (session('error'))<div class="note note--info">@icon('info') <p>{{ session('error') }}</p></div>@endif

        <form class="panel" method="post" action="{{ route('customer.login') }}">
            @csrf
            <div class="form">
                <div @class(['field', 'bad' => $errors->has('phone')])>
                    <label for="phone">رقم الموبايل</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" dir="ltr" style="text-align:end" value="{{ old('phone') }}" placeholder="07701234567" autocomplete="username" required autofocus>
                    @error('phone')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="password">كلمة المرور</label>
                    <input id="password" name="password" type="password" dir="ltr" autocomplete="current-password" required>
                </div>
            </div>
            <button class="btn btn--block" style="margin-top:20px">دخول</button>
        </form>
        <p class="authswap">ليس عندك حساب؟ <a href="{{ route('customer.register') }}">أنشئ حساباً برقم موبايلك</a></p>
        <p class="authswap">نسيت كلمة المرور؟ <a href="{{ \App\Support\Util::wa($site['whatsapp'], 'مرحباً، نسيت كلمة مرور حسابي في موقع '.$site['name'].'. رقمي المسجل: ') }}" target="_blank" rel="noopener">راسل الصيدلية لتغييرها</a></p>
    </div>
</div>
@endsection
