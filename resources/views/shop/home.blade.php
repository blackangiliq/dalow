@extends('layouts.shop')
@section('heroSearch', '1')
@section('description', $site['name'].' في زيونة، بغداد: صيدلية أونلاين بتوصيل لكل مناطق بغداد والدفع عند الاستلام. ابحث عن دوائك بالعربي أو الإنجليزي، ارسل وصفتك، واستشر الصيدلانية مجاناً.')

@section('content')
<section class="hero" data-section="hero">
    <div class="wrap">
        <div>
            <h1>دواؤك من صيدلية الدلو يوصلك لباب بيتك</h1>
            <p class="hero__lead">ابحث عن دوائك بالاسم التجاري أو العلمي، اطلبه بدقيقة، وادفع عند الاستلام. التوصيل من زيونة إلى كل مناطق بغداد.</p>
            <form class="search" action="{{ route('products') }}" role="search" data-suggest="{{ route('suggest') }}" data-track="hero-search">
                <label class="sr" for="hq">ابحث عن دواء</label>
                <input id="hq" name="q" type="search" placeholder="اكتب بالعربي أو الإنجليزي: بنادول، Paracetamol، صداع" autocomplete="off">
                <button>@icon('search') <span>ابحث</span></button>
            </form>
            <div class="chips">
                <span>يبحث الناس عن:</span>
                @foreach ($trending as $term)
                    <a href="{{ route('products', ['q' => $term]) }}" data-track="chip">{{ $term }}</a>
                @endforeach
            </div>
        </div>
        <aside class="rx">
            <div class="rx__sign" aria-hidden="true">℞</div>
            <h2>عندك وصفة طبية؟</h2>
            <p>صوّر الوصفة وارسلها. الصيدلاني يجهز الأدوية ويتصل بك لتأكيد السعر قبل التوصيل.</p>
            <a class="btn btn--block" href="{{ route('prescription') }}" data-track="rx-card">@icon('upload') ارسل الوصفة</a>
        </aside>
    </div>
</section>

<section class="assure" aria-label="مزايا الطلب" data-section="assure">
    <div class="wrap">
        <div>@icon('truck') <span>توصيل لكل بغداد<small>زيونة خلال ساعة تقريباً</small></span></div>
        <div>@icon('cash') <span>الدفع عند الاستلام<small>نقداً للمندوب</small></span></div>
        <div>@icon('shield') <span>أدوية أصلية<small>من مذاخر ووكلاء معتمدين</small></span></div>
        <div>@icon('chat') <span>{{ $site['pharmacist_label'] }} تجاوبك<small>استشارة مجانية على واتساب</small></span></div>
    </div>
</section>

<section class="band" data-section="cats">
    <div class="wrap">
        <div class="band__head">
            <h2>تسوق حسب القسم</h2>
        </div>
        <div class="cats">
            @foreach ($navCategories as $c)
                <a class="cat" style="--tint: {{ $c->color }}" href="{{ route('category', $c) }}" data-track="category:{{ $c->slug }}">
                    <span class="cat__ico">@icon($c->icon)</span>
                    <span><b>{{ $c->name }}</b></span>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if ($offers->count())
<section class="band band--mist" data-section="offers">
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

<section class="band" data-section="services">
    <div class="wrap">
        <div class="band__head">
            <div>
                <h2>خدمات لا تجدها في كل صيدلية</h2>
                <p>كلها مجانية ومتاحة من الموقع مباشرة.</p>
            </div>
        </div>
        <div class="services">
            <a class="service" href="{{ route('verify') }}" data-track="verify">
                @icon('scan')
                <b>تحقق من دوائك</b>
                <span>اكتب باركود العلبة أو امسحه بالكاميرا لتطابقه مع قائمة الصيدلية، وتعلّم كيف تقرأ لاصق وزارة الصحة.</span>
                <strong>افحص علبة الدواء</strong>
            </a>
            <a class="service" href="{{ route('request') }}" data-track="request">
                @icon('search')
                <b>اطلب منتجاً غير موجود</b>
                <span>لم تجد دواءك؟ اكتب اسمه أو ارفع صورته ونبحث عنه في المذاخر ونتصل بك.</span>
                <strong>اطلب توفيره</strong>
            </a>
            <a class="service" href="{{ $me ? route('account', ['tab' => 'favorites']) : route('customer.register') }}" data-track="favorite">
                @icon('bell')
                <b>أعلمني عند انخفاض السعر</b>
                <span>اضغط القلب على أي منتج، وعند نزول سعره يصلك إشعار على رقم موبايلك.</span>
                <strong>{{ $me ? 'قائمتي' : 'أنشئ حساباً مجاناً' }}</strong>
            </a>
            <a class="service" href="{{ $me ? route('account') : route('customer.register') }}">
                @icon('repeat')
                <b>أعد طلب دوائك الشهري</b>
                <span>لأدوية الضغط والسكري: كل طلباتك محفوظة في حسابك، وتعيدها للسلة بضغطة واحدة.</span>
                <strong>{{ $me ? 'طلباتي السابقة' : 'سجّل لتحفظ طلباتك' }}</strong>
            </a>
        </div>
    </div>
</section>

<section class="band band--mist" data-section="steps">
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
<section class="band" data-section="featured">
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

@if ($recent->count())
<section class="band band--mist" data-section="recent">
    <div class="wrap">
        <div class="band__head"><h2>شاهدته مؤخراً</h2></div>
        <div class="grid">
            @foreach ($recent as $p) @include('partials.product-card', ['p' => $p]) @endforeach
        </div>
    </div>
</section>
@endif

@if ($faqs->count())
<section class="band" data-section="faq">
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

<section class="band band--mist" id="visit" data-section="visit">
    <div class="wrap">
        <div class="place">
            <div>
                <h2>زورنا في زيونة</h2>
                <p>قياس الضغط والسكر مجاناً داخل الصيدلية، واستشارة الصيدلاني متاحة طوال الدوام.</p>
                <div class="place__btns">
                    <a class="btn btn--sun" href="tel:{{ $site['phone'] }}" data-track="call">@icon('phone') اتصل بنا</a>
                    <a class="btn btn--wa" href="{{ $consultUrl }}" target="_blank" rel="noopener" data-track="consult">@icon('whatsapp') استشر {{ $site['pharmacist_label'] }}</a>
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
    </div>
</section>
@endsection
