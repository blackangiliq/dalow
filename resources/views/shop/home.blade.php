@extends('layouts.shop')
@section('heroSearch', '1')

@section('content')
<section class="hero">
    <div class="wrap">
        <div>
            <h1>دواؤك من صيدلية الدلو يوصلك لباب بيتك</h1>
            <p class="hero__lead">ابحث عن دوائك بالاسم التجاري أو العلمي، اطلبه بدقيقة، وادفع عند الاستلام. التوصيل من زيونة إلى كل مناطق بغداد.</p>
            <form class="search" action="{{ route('products') }}" role="search" data-suggest="{{ route('suggest') }}">
                <label class="sr" for="hq">ابحث عن دواء</label>
                <input id="hq" name="q" type="search" placeholder="مثلاً: بنادول، Paracetamol، فيتامين د" autocomplete="off">
                <button>@icon('search') <span>ابحث</span></button>
            </form>
            <div class="chips">
                <span>يبحث الناس عن:</span>
                @foreach (['Panadol', 'Augmentin', 'Vitamin D3', 'حليب أطفال', 'جهاز ضغط'] as $term)
                    <a href="{{ route('products', ['q' => $term]) }}">{{ $term }}</a>
                @endforeach
            </div>
        </div>
        <aside class="rx">
            <div class="rx__sign" aria-hidden="true">℞</div>
            <h2>عندك وصفة طبية؟</h2>
            <p>صوّر الوصفة وارسلها. الصيدلاني يجهز الأدوية ويتصل بك لتأكيد السعر قبل التوصيل.</p>
            <a class="btn btn--block" href="{{ route('prescription') }}">@icon('upload') ارسل الوصفة</a>
        </aside>
    </div>
</section>

<section class="assure" aria-label="مزايا الطلب">
    <div class="wrap">
        <div>@icon('truck') <span>توصيل لكل بغداد<small>زيونة خلال ساعة تقريباً</small></span></div>
        <div>@icon('cash') <span>الدفع عند الاستلام<small>نقداً للمندوب</small></span></div>
        <div>@icon('shield') <span>أدوية أصلية<small>من مذاخر ووكلاء معتمدين</small></span></div>
        <div>@icon('chat') <span>{{ $site['pharmacist_label'] }} تجاوبك<small>استشارة مجانية على واتساب</small></span></div>
    </div>
</section>

<section class="band">
    <div class="wrap">
        <div class="band__head">
            <h2>تسوق حسب القسم</h2>
        </div>
        <div class="cats">
            @foreach ($navCategories as $c)
                <a class="cat" style="--tint: {{ $c->color }}" href="{{ route('category', $c) }}">
                    <span class="cat__ico">@icon($c->icon)</span>
                    <span><b>{{ $c->name }}</b></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if ($offers->count())
<section class="band band--mist">
    <div class="wrap">
        <div class="band__head">
            <div>
                <h2>عروض هذا الأسبوع</h2>
            </div>
            <a class="more" href="{{ route('products', ['offers' => 1]) }}">كل العروض</a>
        </div>
        <div class="grid">
            @foreach ($offers as $p)
                @include('partials.product-card', ['p' => $p])
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="band">
    <div class="wrap">
        <div class="band__head">
            <h2>كيف تطلب</h2>
        </div>
        <ol class="steps">
            <li><h3>اختر ما تحتاجه</h3><p>ابحث عن الدواء أو تصفح الأقسام وأضف للسلة. للأدوية التي تحتاج وصفة أرفق صورتها.</p></li>
            <li><h3>اكتب عنوانك</h3><p>الاسم ورقم الموبايل والمنطقة. لا تحتاج حساباً ولا بطاقة مصرفية.</p></li>
            <li><h3>استلم وادفع</h3><p>يصل الطلب لفريق الصيدلية فوراً، نتصل بك للتأكيد ثم يوصله المندوب وتدفع نقداً.</p></li>
        </ol>
    </div>
</section>

@if ($featured->count())
<section class="band band--mist">
    <div class="wrap">
        <div class="band__head">
            <h2>الأكثر طلباً</h2>
            <a class="more" href="{{ route('products') }}">كل المنتجات</a>
        </div>
        <div class="grid">
            @foreach ($featured as $p)
                @include('partials.product-card', ['p' => $p])
            @endforeach
        </div>
    </div>
</section>
@endif

@if ($faqs->count())
<section class="band">
    <div class="wrap">
        <div class="band__head">
            <h2>أسئلة يسألها زبائننا</h2>
            <a class="more" href="{{ route('faq') }}">كل الأسئلة</a>
        </div>
        <div class="faq">
            @foreach ($faqs as $f)
                <details><summary>{{ $f->question }}</summary><p>{{ $f->answer }}</p></details>
            @endforeach
        </div>
    </div>
</section>
@endif

<section class="band band--mist" id="visit">
    <div class="wrap">
        <div class="place">
            <div>
                <h2>زورنا في زيونة</h2>
                <p>قياس الضغط والسكر مجاناً داخل الصيدلية، واستشارة الصيدلاني متاحة طوال الدوام.</p>
                <div class="place__btns">
                    <a class="btn btn--sun" href="tel:{{ $site['phone'] }}">@icon('phone') اتصل بنا</a>
                    <a class="btn btn--wa" href="{{ $consultUrl }}" target="_blank" rel="noopener">@icon('whatsapp') استشر {{ $site['pharmacist_label'] }}</a>
                    @if ($site['map_url'])
                        <a class="btn btn--ghost" href="{{ $site['map_url'] }}" target="_blank" rel="noopener">@icon('map') الموقع على الخريطة</a>
                    @endif
                </div>
            </div>
            <dl>
                <div><dt>@icon('pin')</dt><dd>{{ $site['address'] }}<small>العنوان</small></dd></div>
                <div><dt>@icon('clock')</dt><dd>{{ $site['hours'] }}<small>أوقات الدوام</small></dd></div>
                <div><dt>@icon('phone')</dt><dd><span class="num">{{ $site['phone'] }}</span><small>الهاتف</small></dd></div>
            </dl>
        </div>
    </div>
</section>
@endsection
