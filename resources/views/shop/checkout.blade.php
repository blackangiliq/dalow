@extends('layouts.shop')
@section('title', 'إتمام الطلب')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <nav class="crumbs" aria-label="مسار الصفحة"><a href="{{ route('cart') }}">السلة</a><span>إتمام الطلب</span></nav>
    <h1 class="page__title">إتمام الطلب</h1>

    @error('cart')<div class="note note--bad">@icon('alert') <p>{{ $message }} <a class="more" href="{{ route('cart') }}">راجع السلة</a></p></div>@enderror

    <form class="split" method="post" action="{{ route('checkout.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel">
            <h2>{{ $deliveryOn ? 'بيانات التوصيل' : 'بيانات الاستلام' }}</h2>
            <div class="form">
                @include('shop._customer')

                @if ($needsRx)
                <div @class(['field', 'field--full', 'bad' => $errors->has('prescription')])>
                    <span style="font-weight:500">صورة الوصفة الطبية</span>
                    <label class="drop">
                        @icon('upload')
                        <b>اختر صورة الوصفة أو التقطها بالكاميرا</b>
                        <small>في سلتك دواء يصرف بوصفة. JPG أو PNG حتى 6 ميغابايت.</small>
                        <input type="file" name="prescription" accept="image/*" required>
                    </label>
                    @error('prescription')<span class="err">{{ $message }}</span>@enderror
                </div>
                @endif
            </div>
        </div>

        <aside class="panel panel--mist">
            <h2>طلبك</h2>
            @foreach ($items as $item)
                <div class="sum" style="margin-bottom:8px">
                    <div><dt>{{ $item->product->name }} × <span class="num">{{ $item->qty }}</span></dt><dd style="white-space:nowrap">@iqd($item->line)</dd></div>
                </div>
            @endforeach
            <dl class="sum" style="border-top:1.5px solid var(--line);padding-top:12px;margin-top:12px">
                <div><dt>مجموع المواد</dt><dd>@iqd($subtotal)</dd></div>
                <div><dt>{{ $deliveryOn ? 'التوصيل' : 'الاستلام' }}</dt><dd data-fee-out>اختر المنطقة</dd></div>
                <div class="total"><dt>المجموع</dt><dd data-total-out>@iqd($subtotal)</dd></div>
            </dl>
            <div class="note note--info" style="background:#fff">@icon('cash') <p>الدفع نقداً عند الاستلام.</p></div>
            <button class="btn btn--buy btn--block" style="margin-top:18px">تأكيد الطلب</button>
            @unless ($customer)
                <p class="line__meta" style="margin-top:10px;text-align:center"><a class="more" href="{{ route('customer.login') }}">سجّل الدخول</a> لتُملأ بياناتك تلقائياً ويُحفظ الطلب في حسابك.</p>
            @endunless
        </aside>
    </form>
</div>
@endsection
