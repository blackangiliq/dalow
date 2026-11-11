@extends('layouts.shop')
@section('title', 'تأكيد رقم الموبايل')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <div class="authbox">
        <h1 class="page__title">أكّد رقمك</h1>
        <p class="page__sub">أرسلنا كوداً من ستة أرقام إلى تيليجرام على الرقم <span class="num">{{ $customer->phone }}</span>.</p>
        @if (session('error'))<div class="note note--bad">@icon('alert') <p>{{ session('error') }}</p></div>@endif

        <form class="panel" method="post" action="{{ route('customer.verify') }}">
            @csrf
            <div @class(['field', 'bad' => $errors->has('code')])>
                <label for="code">كود التأكيد</label>
                <input id="code" name="code" inputmode="numeric" dir="ltr" autocomplete="one-time-code" maxlength="6" style="text-align:center;font-size:24px;letter-spacing:.3em" required autofocus>
                @error('code')<span class="err">{{ $message }}</span>@else<span class="hint">الكود صالح لعشر دقائق</span>@enderror
            </div>
            <button class="btn btn--block" style="margin-top:18px">تأكيد</button>
        </form>

        <form method="post" action="{{ route('customer.verify.resend') }}" class="authswap">@csrf لم يصلك الكود؟ <button class="link" style="color:var(--link);font-size:inherit;font-weight:700">أرسله مرة أخرى</button></form>
        <p class="authswap"><a href="{{ route('account') }}">أكمل الآن وأكّد لاحقاً</a></p>
        <p class="authswap">ليس عندك تيليجرام على هذا الرقم؟ اتصل بالصيدلية على <a class="num" href="tel:{{ $site['phone'] }}">{{ $site['phone'] }}</a> ليؤكدوا حسابك.</p>
    </div>
</div>
@endsection
