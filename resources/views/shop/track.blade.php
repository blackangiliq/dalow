@extends('layouts.shop')
@section('title', 'تتبع طلبك')

@section('content')
<div class="page wrap" style="max-width:720px">
    <h1 class="page__title">تتبع طلبك</h1>
    <p class="page__sub">اكتب رقم الطلب ورقم الموبايل الذي طلبت به.</p>

    <form class="panel" style="margin-top:22px" method="get">
        <div class="form">
            <div class="field">
                <label for="number">رقم الطلب</label>
                <input id="number" name="number" dir="ltr" style="text-align:end" value="{{ request('number') }}" placeholder="D123456" required>
            </div>
            <div class="field">
                <label for="tphone">رقم الموبايل</label>
                <input id="tphone" name="phone" type="tel" dir="ltr" style="text-align:end" value="{{ request('phone') }}" placeholder="07701234567" required>
            </div>
        </div>
        <button class="btn" style="margin-top:18px">اعرض حالة الطلب</button>
    </form>

    @if ($order)
        <h2 style="margin-top:34px;font-size:20px">الطلب <span class="num">{{ $order->number }}</span></h2>
        @include('shop._order')
    @elseif ($searched)
        <div class="note note--bad">@icon('alert') <p>لم نجد طلباً بهذا الرقم مع رقم الموبايل المكتوب. تأكد من الرقمين وحاول مرة أخرى.</p></div>
    @endif
</div>
@endsection
