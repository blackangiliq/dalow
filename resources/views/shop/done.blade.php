@extends('layouts.shop')
@section('title', 'تم استلام طلبك')

@section('content')
<div class="page wrap">
    <div class="done">
        <div class="done__mark">@icon('check')</div>
        <h1 class="page__title">استلمنا طلبك يا {{ \Illuminate\Support\Str::before($order->customer_name, ' ') }}</h1>
        <p class="page__sub">وصل الطلب لفريق الصيدلية الآن. سنتصل بك على <span class="num">{{ $order->phone }}</span> للتأكيد.</p>
        <div class="done__num">{{ $order->number }}</div>
        <p class="page__sub">احتفظ برقم الطلب لتتبعه لاحقاً.</p>

        @include('shop._order')

        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:24px">
            <a class="btn" href="{{ route('track', ['number' => $order->number, 'phone' => $order->phone]) }}">تتبع الطلب</a>
            <a class="btn btn--ghost" href="{{ route('home') }}">العودة للرئيسية</a>
        </div>
    </div>
</div>
@endsection
