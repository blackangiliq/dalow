@extends('layouts.shop')
@section('title', $product->name.': السعر والتوصيل في بغداد')
@section('ogType', 'product')
@if ($product->image_url) @section('ogImage', $product->image_url) @endif
@section('bodyClass', $product->stock > 0 ? 'has-buybar' : '')
@section('description', $product->name.($product->scientific_name ? ' ('.$product->scientific_name.')' : '').' بسعر '.number_format($product->price).' دينار من '.$site['name'].'. '.$product->description.' توصيل لكل مناطق بغداد والدفع عند الاستلام.')

@section('content')
<div class="page wrap">
    <nav class="crumbs" aria-label="مسار الصفحة">
        <a href="{{ route('home') }}">الرئيسية</a>
        @if ($product->category)<a href="{{ route('category', $product->category) }}">{{ $product->category->name }}</a>@endif
        <span>{{ $product->name }}</span>
    </nav>

    <div class="pview" data-section="details">
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

            @if ($soldWeek >= 3)
                <div class="proof">@icon('fire') طُلب <span class="num">{{ $soldWeek }}</span> مرة هذا الأسبوع</div>
            @endif

            <div class="price price--lg">
                @if ($product->discount)<s>@iqd($product->old_price)</s>@endif
                <b>@iqd($product->price)</b>
            </div>

            @php
                $waOrder = \App\Support\Util::wa($site['whatsapp'], "مرحباً، أريد طلب هذا المنتج من موقع {$site['name']}:\n{$product->name}\n".route('product', $product)."\nالكمية: 1");
            @endphp
            @if ($product->stock > 0)
                <form class="buy js-add" method="post" action="{{ route('cart.add') }}" id="buyform">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="qty">
                        <button type="button" data-step="1" aria-label="زيادة الكمية">+</button>
                        <input type="number" name="qty" value="1" min="1" max="{{ min(99, $product->stock) }}" aria-label="الكمية">
                        <button type="button" data-step="-1" aria-label="إنقاص الكمية">−</button>
                    </div>
                    <button class="btn btn--buy" data-track="add-to-cart">@icon('cart') أضف للسلة</button>
                    @if ($site['whatsapp'])
                        <a class="btn btn--wa-line" href="{{ $waOrder }}" target="_blank" rel="noopener" data-track="wa-order">@icon('whatsapp') اطلب عبر واتساب</a>
                    @endif
                </form>
            @else
                <div class="note note--bad">@icon('alert') <p>هذا المنتج غير متوفر حالياً. <a class="more" href="{{ route('request', ['name' => $product->name]) }}" data-track="request">اطلب توفيره</a> وسنتصل بك عند وصوله، أو اسأل {{ $site['pharmacist_label'] }} عن بديل مناسب.</p></div>
            @endif

            <div class="sharebar">
                <form method="post" action="{{ route('favorite', $product) }}" class="js-fav" style="flex:1 1 auto;display:flex">
                    @csrf
                    <button @class(['btn', 'btn--fav', 'on' => $isFavorite]) style="flex:1" data-track="favorite" data-fav-label>@icon('heart') <span>{{ $isFavorite ? 'سنعلمك عند انخفاض السعر' : 'أعلمني عند انخفاض السعر' }}</span></button>
                </form>
                <a class="btn btn--ghost" target="_blank" rel="noopener" data-track="share"
                   href="https://wa.me/?text={{ rawurlencode($product->name.' بسعر '.\App\Support\Util::money($product->price).' من '.$site['name']."\n".route('product', $product)) }}">@icon('share') شارك المنتج</a>
            </div>

            <div class="ask" data-section="ask">
                <span class="ask__ico">@icon('whatsapp')</span>
                <div><b>عندك سؤال عن هذا المنتج؟</b><span>الجرعة، طريقة الاستعمال، أو البديل المناسب. الاستشارة مجانية.</span></div>
                <a class="btn btn--wa" href="{{ \App\Support\Util::consultLink($product) }}" target="_blank" rel="noopener" data-track="consult">استشر {{ $site['pharmacist_label'] }}</a>
            </div>

            @if ($product->requires_prescription)
                <div class="note">@icon('alert') <p><strong>هذا الدواء يصرف بوصفة طبية.</strong> سنطلب منك إرفاق صورة الوصفة عند إتمام الطلب.</p></div>
            @endif

            @if ($product->description)
                <div class="note note--info">@icon('info') <p>{{ $product->description }}</p></div>
            @endif
        </div>
    </div>
</div>

@if ($product->stock > 0)
<div class="buybar">
    <div class="price"><b>@iqd($product->price)</b></div>
    <button class="btn btn--buy" form="buyform" data-track="add-to-cart">@icon('cart') أضف للسلة</button>
</div>
@endif

@if ($together->count())
<section class="band" data-section="together">
    <div class="wrap">
        <div class="band__head"><div><h2>يُشترى معه غالباً</h2><p>من طلبات زبائن الصيدلية.</p></div></div>
        <div class="grid">
            @foreach ($together as $p) @include('partials.product-card', ['p' => $p]) @endforeach
        </div>
    </div>
</section>
@endif

@if ($alternatives->count())
<section class="band" data-section="alternatives">
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
<section class="band band--mist" data-section="related">
    <div class="wrap">
        <div class="band__head"><h2>من نفس القسم</h2></div>
        <div class="grid">
            @foreach ($related as $p) @include('partials.product-card', ['p' => $p]) @endforeach
        </div>
    </div>
</section>
@endif
@if ($recent->count())
<section class="band" data-section="recent">
    <div class="wrap">
        <div class="band__head"><h2>شاهدته مؤخراً</h2></div>
        <div class="grid">
            @foreach ($recent as $p) @include('partials.product-card', ['p' => $p]) @endforeach
        </div>
    </div>
</section>
@endif
@endsection

@push('head')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        array_filter([
            '@type' => 'Product',
            'name' => $product->name,
            'description' => $product->description ?: $product->name,
            'image' => $product->image_url ?: asset('og-image.png'),
            'sku' => (string) $product->id,
            'gtin' => $product->barcode ?: null,
            'brand' => $product->company ? ['@type' => 'Brand', 'name' => $product->company] : null,
            'category' => $product->category?->name,
            'offers' => [
                '@type' => 'Offer',
                'url' => route('product', $product),
                'priceCurrency' => 'IQD',
                'price' => $product->price,
                'availability' => 'https://schema.org/'.($product->stock > 0 ? 'InStock' : 'OutOfStock'),
                'seller' => ['@id' => url('/').'#pharmacy'],
            ],
        ]),
        [
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_values(array_filter([
                ['@type' => 'ListItem', 'position' => 1, 'name' => 'الرئيسية', 'item' => route('home')],
                $product->category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $product->category->name, 'item' => route('category', $product->category)] : null,
                ['@type' => 'ListItem', 'position' => $product->category ? 3 : 2, 'name' => $product->name],
            ])),
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush
