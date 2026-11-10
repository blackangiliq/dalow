@extends('layouts.shop')
@section('heroSearch', '1')
@section('description', $site['name'].' في زيونة، بغداد: صيدلية أونلاين'.($deliveryOn ? ' بتوصيل لكل مناطق بغداد' : '').' والدفع عند الاستلام. ابحث عن دوائك بالعربي أو الإنجليزي، ارسل وصفتك، واستشر الصيدلانية مجاناً.')

@section('content')
{{-- الواجهة ثابتة في الأعلى. باقي الأقسام تظهر وتترتب كما حدد المدير في «واجهة الموقع» --}}
<section class="hero" data-section="hero">
    <div class="wrap" @unless ($on['prescription']) style="grid-template-columns: minmax(0, 1fr)" @endunless>
        <div>
            <h1>{{ $ui['hero_title'] }}</h1>
            <p class="hero__lead">{{ $ui['hero_lead'] }}</p>
            <form class="search" action="{{ route('products') }}" role="search" @if ($on['suggest']) data-suggest="{{ route('suggest') }}" @endif data-track="hero-search">
                <label class="sr" for="hq">ابحث عن دواء</label>
                <input id="hq" name="q" type="search" placeholder="{{ $ui['search_placeholder'] }}" autocomplete="off">
                <button>@icon('search') <span>ابحث</span></button>
            </form>
            @if ($on['trending'] && $trending)
            <div class="chips">
                <span>يبحث الناس عن:</span>
                @foreach ($trending as $term)
                    <a href="{{ route('products', ['q' => $term]) }}" data-track="chip">{{ $term }}</a>
                @endforeach
            </div>
            @endif
        </div>
        @if ($on['prescription'])
        <aside class="rx">
            <div class="rx__sign" aria-hidden="true">℞</div>
            <h2>{{ $ui['rx_title'] }}</h2>
            <p>{{ $ui['rx_text'] }}</p>
            <a class="btn btn--block" href="{{ route('prescription') }}" data-track="rx-card">@icon('upload') ارسل الوصفة</a>
        </aside>
        @endif
    </div>
</section>

@php
    // الأقسام التي ليس فيها ما يُعرض لا تأخذ دوراً في تبادل لون الخلفية
    $shown = collect($sections)->reject(fn ($key) => match ($key) {
        'offers' => $offers->isEmpty(),
        'featured' => $featured->isEmpty(),
        'recent' => $recent->isEmpty() || ! $on['recent'],
        'faq' => $faqs->isEmpty() || ! $on['faq'],
        default => false,
    })->values();
    $services = array_filter([
        $on['verify'] ? ['scan', 'تحقق من دوائك', 'اكتب باركود العلبة أو امسحه بالكاميرا لتطابقه مع قائمة الصيدلية، وتعلّم كيف تقرأ لاصق وزارة الصحة.', 'افحص علبة الدواء', route('verify'), 'verify'] : null,
        $on['requests'] ? ['search', 'اطلب منتجاً غير موجود', 'لم تجد دواءك؟ اكتب اسمه أو ارفع صورته ونبحث عنه في المذاخر ونتصل بك.', 'اطلب توفيره', route('request'), 'request'] : null,
        $on['accounts'] ? ['bell', 'أعلمني عند انخفاض السعر', 'اضغط القلب على أي منتج، وعند نزول سعره يصلك إشعار على رقم موبايلك.', $me ? 'قائمتي' : 'أنشئ حساباً مجاناً', $me ? route('account', ['tab' => 'favorites']) : route('customer.register'), 'favorite'] : null,
        $on['accounts'] ? ['repeat', 'أعد طلب دوائك الشهري', 'لأدوية الضغط والسكري: كل طلباتك محفوظة في حسابك، وتعيدها للسلة بضغطة واحدة.', $me ? 'طلباتي السابقة' : 'سجّل لتحفظ طلباتك', $me ? route('account') : route('customer.register'), 'account'] : null,
    ]);
    $band = 0;
@endphp

@foreach ($shown as $key)
    @if ($key === 'assure')
        <section class="assure" aria-label="مزايا الطلب" data-section="assure">
            <div class="wrap">
                @foreach (['truck', 'cash', 'shield', 'chat'] as $i => $icon)
                    <div>@icon($icon) <span>{{ $ui['assure'.($i + 1).'_title'] }}<small>{{ $ui['assure'.($i + 1).'_sub'] }}</small></span></div>
                @endforeach
            </div>
        </section>
        @continue
    @endif
    @if ($key === 'services' && ! $services) @continue @endif
    @php $mist = $band++ % 2 === 1; @endphp

    <section @class(['band', 'band--mist' => $mist]) data-section="{{ $key }}" @if ($key === 'visit') id="visit" @endif>
        <div class="wrap">
        @switch($key)
            @case('cats')
                <div class="band__head"><h2>{{ $ui['cats_title'] }}</h2></div>
                <div class="cats">
                    @foreach ($navCategories as $c)
                        <a class="cat" style="--tint: {{ $c->color }}" href="{{ route('category', $c) }}" data-track="category:{{ $c->slug }}">
                            <span class="cat__ico">@icon($c->icon)</span>
                            <span><b>{{ $c->name }}</b></span>
                        </a>
                    @endforeach
                </div>
                @break

            @case('offers')
                <div class="band__head">
                    <h2>{{ $ui['offers_title'] }}</h2>
                    <a class="more" href="{{ route('products', ['offers' => 1]) }}">كل العروض</a>
                </div>
                <div class="grid">
                    @foreach ($offers as $p) @include('partials.product-card', ['p' => $p]) @endforeach
                </div>
                @break

            @case('services')
                <div class="band__head"><div><h2>{{ $ui['services_title'] }}</h2><p>{{ $ui['services_sub'] }}</p></div></div>
                <div class="services" style="--cols: {{ min(4, count($services)) }}">
                    @foreach ($services as [$icon, $title, $text, $cta, $url, $track])
                        <a class="service" href="{{ $url }}" data-track="{{ $track }}" @if ($track === 'consult') target="_blank" rel="noopener" @endif>
                            @icon($icon)
                            <b>{{ $title }}</b>
                            <span>{{ $text }}</span>
                            <strong>{{ $cta }}</strong>
                        </a>
                    @endforeach
                </div>
                @break

            @case('steps')
                <div class="band__head"><h2>{{ $ui['steps_title'] }}</h2></div>
                <ol class="steps">
                    @foreach ([1, 2, 3] as $n)
                        <li><h3>{{ $ui["step{$n}_title"] }}</h3><p>{{ $ui["step{$n}_text"] }}</p></li>
                    @endforeach
                </ol>
                @break

            @case('featured')
                <div class="band__head">
                    <h2>{{ $ui['featured_title'] }}</h2>
                    <a class="more" href="{{ route('products') }}">كل المنتجات</a>
                </div>
                <div class="grid">
                    @foreach ($featured as $p) @include('partials.product-card', ['p' => $p]) @endforeach
                </div>
                @break

            @case('recent')
                <div class="band__head"><h2>{{ $ui['recent_title'] }}</h2></div>
                <div class="grid">
                    @foreach ($recent as $p) @include('partials.product-card', ['p' => $p]) @endforeach
                </div>
                @break

            @case('faq')
                <div class="band__head">
                    <h2>{{ $ui['faq_title'] }}</h2>
                    <a class="more" href="{{ route('faq') }}">كل الأسئلة</a>
                </div>
                <div class="faq">
                    @foreach ($faqs as $f)
                        <details><summary>{{ $f->question }}</summary><p>{{ $f->answer }}</p></details>
                    @endforeach
                </div>
                @break

            @case('visit')
                <div class="place">
                    <div>
                        <h2>{{ $ui['visit_title'] }}</h2>
                        <p>{{ $ui['visit_text'] }}</p>
                        <div class="place__btns">
                            <a class="btn btn--sun" href="tel:{{ $site['phone'] }}" data-track="call">@icon('phone') اتصل بنا</a>
                            @if ($on['consult'])
                                <a class="btn btn--wa" href="{{ $consultUrl }}" target="_blank" rel="noopener" data-track="consult">@icon('whatsapp') استشر {{ $site['pharmacist_label'] }}</a>
                            @endif
                            @if ($site['map_url'])
                                <a class="btn btn--ghost" href="{{ $site['map_url'] }}" target="_blank" rel="noopener" data-track="map">@icon('map') الموقع على الخريطة</a>
                            @endif
                        </div>
                    </div>
                    <dl>
                        <div><dt>@icon('pin')</dt><dd>{{ $site['address'] }}<small>العنوان</small></dd></div>
                        <div><dt>@icon('clock')</dt><dd>{{ $site['hours'] }}<small>أوقات الدوام</small></dd></div>
                        <div><dt>@icon('phone')</dt><dd><span class="num">{{ $site['phone'] }}</span><small>الهاتف</small></dd></div>
                    </dl>
                </div>
                @break
        @endswitch
        </div>
    </section>
@endforeach
@endsection
