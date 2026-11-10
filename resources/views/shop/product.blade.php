@extends('layouts.shop')
@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit($product->name.' '.$product->scientific_name.'. '.$product->description, 150))

@section('content')
<div class="page wrap">
    <nav class="crumbs" aria-label="مسار الصفحة">
        <a href="{{ route('home') }}">الرئيسية</a>
        @if ($product->category)<a href="{{ route('category', $product->category) }}">{{ $product->category->name }}</a>@endif
        <span>{{ $product->name }}</span>
    </nav>

    <div class="pview">
        <div class="pview__media" style="--tint: {{ $product->tint }}">
            @if ($product->image_url)
                <img src="{{ $product->image_url }}" alt="{{ $product->name }}">
            @else
                @icon($product->icon)
            @endif
            @if ($product->discount)<span class="tag tag--off">خصم <span class="num">{{ $product->discount }}%</span></span>@endif
        </div>

        <div>
            <h1>{{ $product->name }}</h1>
            <dl class="facts">
                @if ($product->scientific_name)<dt>المادة الفعالة</dt><dd dir="ltr" style="text-align:end">{{ $product->scientific_name }}</dd>@endif
                @if ($product->company)<dt>الشركة</dt><dd>{{ $product->company }}</dd>@endif
                @if ($product->dosage_form)<dt>الشكل</dt><dd>{{ $product->dosage_form }}</dd>@endif
                <dt>التوفر</dt>
                <dd>
                    @if ($product->stock < 1)<span class="stock stock--out">غير متوفر حالياً</span>
                    @elseif ($product->stock <= 5)<span class="stock stock--low">بقيت <span class="num">{{ $product->stock }}</span> فقط</span>
                    @else<span class="stock">متوفر</span>
                    @endif
                </dd>
            </dl>

            <div class="price price--lg">
                @if ($product->discount)<s>@iqd($product->old_price)</s>@endif
                <b>@iqd($product->price)</b>
            </div>

            @if ($product->stock > 0)
                <form class="buy js-add" method="post" action="{{ route('cart.add') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="qty">
                        <button type="button" data-step="1" aria-label="زيادة الكمية">+</button>
                        <input type="number" name="qty" value="1" min="1" max="{{ min(99, $product->stock) }}" aria-label="الكمية">
                        <button type="button" data-step="-1" aria-label="إنقاص الكمية">−</button>
                    </div>
                    <button class="btn">@icon('cart') أضف للسلة</button>
                </form>
            @else
                <div class="buy">
                    <a class="btn btn--wa" target="_blank" rel="noopener"
                       href="https://wa.me/{{ preg_replace('/\D/', '', $site['whatsapp']) }}?text={{ rawurlencode('مرحباً، أريد السؤال عن توفر: '.$product->name) }}">@icon('whatsapp') اسأل عن موعد توفره</a>
                </div>
            @endif

            @if ($product->requires_prescription)
                <div class="note">@icon('alert') <p><strong>هذا الدواء يصرف بوصفة طبية.</strong> سنطلب منك إرفاق صورة الوصفة عند إتمام الطلب.</p></div>
            @endif

            @if ($product->description)
                <div class="note note--info">@icon('info') <p>{{ $product->description }}</p></div>
            @endif
        </div>
    </div>
</div>

@if ($alternatives->count())
<section class="band">
    <div class="wrap">
        <div class="band__head">
            <div>
                <h2>بدائل بنفس المادة الفعالة</h2>
                <p>استشر الصيدلاني قبل تبديل دوائك.</p>
            </div>
        </div>
        <div class="grid">
            @foreach ($alternatives as $p) @include('partials.product-card', ['p' => $p]) @endforeach
        </div>
    </div>
</section>
@endif

@if ($related->count())
<section class="band band--mist">
    <div class="wrap">
        <div class="band__head"><h2>من نفس القسم</h2></div>
        <div class="grid">
            @foreach ($related as $p) @include('partials.product-card', ['p' => $p]) @endforeach
        </div>
    </div>
</section>
@endif
@endsection
