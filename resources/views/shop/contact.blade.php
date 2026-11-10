@extends('layouts.shop')
@section('title', 'تواصل معنا')

@section('content')
<div class="page wrap">
    <h1 class="page__title">تواصل معنا</h1>
    <p class="page__sub">اختر الطريقة الأسهل لك. نرد خلال أوقات الدوام.</p>

    <div class="ways">
        <a class="way way--wa" href="{{ $consultUrl }}" target="_blank" rel="noopener">
            @icon('whatsapp')
            <b>استشر {{ $site['pharmacist_label'] }}</b>
            <span>سؤال عن دواء أو جرعة أو بديل. تفتح محادثة واتساب برسالة جاهزة.</span>
            <strong>ابدأ المحادثة</strong>
        </a>
        @if ($site['whatsapp'])
        <a class="way way--wa" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($site['whatsapp'], 'مرحباً، أنا قادم من موقع '.$site['name'].' وأريد أن أطلب: ') }}">
            @icon('cart')
            <b>اطلب عبر واتساب</b>
            <span>اكتب ما تحتاجه أو ارسل صورة الوصفة ونحن نجهز الطلب.</span>
            <strong>ارسل طلبك</strong>
        </a>
        @endif
        <a class="way" href="tel:{{ $site['phone'] }}">
            @icon('phone')
            <b>اتصل بالصيدلية</b>
            <span>{{ $site['hours'] }}</span>
            <strong class="num">{{ $site['phone'] }}</strong>
        </a>
    </div>

    <div class="split">
        <form class="panel" method="post" action="{{ route('contact.store') }}">
            @csrf
            <h2>اترك لنا رسالة</h2>
            @if (session('ok'))<div class="note note--ok" style="margin:0 0 16px">@icon('check') <p>{{ session('ok') }}</p></div>@endif
            <div class="form">
                <div @class(['field', 'bad' => $errors->has('name')])>
                    <label for="name">الاسم</label>
                    <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                    @error('name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div @class(['field', 'bad' => $errors->has('phone')])>
                    <label for="phone">رقم الموبايل</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" dir="ltr" style="text-align:end" value="{{ old('phone') }}" placeholder="07701234567" autocomplete="tel" required>
                    @error('phone')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field field--full">
                    <label for="subject">الموضوع (اختياري)</label>
                    <input id="subject" name="subject" value="{{ old('subject') }}" placeholder="استفسار، اقتراح، شكوى، توفير دواء">
                </div>
                <div @class(['field', 'field--full', 'bad' => $errors->has('body')])>
                    <label for="body">رسالتك</label>
                    <textarea id="body" name="body" rows="5" required>{{ old('body') }}</textarea>
                    @error('body')<span class="err">{{ $message }}</span>@enderror
                </div>
            </div>
            <button class="btn" style="margin-top:18px">@icon('send') ارسل الرسالة</button>
        </form>

        <aside class="panel panel--mist">
            <h2>زورنا</h2>
            <dl class="infolist">
                <div><dt>@icon('pin')</dt><dd>{{ $site['address'] }}<small>العنوان</small></dd></div>
                <div><dt>@icon('clock')</dt><dd>{{ $site['hours'] }}<small>أوقات الدوام</small></dd></div>
                <div><dt>@icon('phone')</dt><dd><a class="num" href="tel:{{ $site['phone'] }}">{{ $site['phone'] }}</a><small>الهاتف</small></dd></div>
                @if ($site['email'])<div><dt>@icon('send')</dt><dd><a href="mailto:{{ $site['email'] }}" dir="ltr">{{ $site['email'] }}</a><small>البريد الإلكتروني</small></dd></div>@endif
            </dl>
            <div class="social">
                @if ($site['map_url'])<a class="btn btn--sm btn--ink" href="{{ $site['map_url'] }}" target="_blank" rel="noopener">@icon('map') الموقع على الخريطة</a>@endif
                @if ($site['facebook_url'])<a class="btn btn--sm btn--ghost" style="background:#fff" href="{{ $site['facebook_url'] }}" target="_blank" rel="noopener">فيسبوك</a>@endif
                @if ($site['instagram_url'])<a class="btn btn--sm btn--ghost" style="background:#fff" href="{{ $site['instagram_url'] }}" target="_blank" rel="noopener">إنستغرام</a>@endif
            </div>
        </aside>
    </div>
</div>
@endsection
