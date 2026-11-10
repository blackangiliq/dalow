@extends('layouts.shop')
@section('title', 'تحقق من دوائك: فحص باركود الدواء')
@section('description', 'افحص علبة دوائك: اكتب الباركود أو امسحه بالكاميرا لمطابقته مع قائمة '.$site['name'].'، وتعلّم كيف تتأكد من لاصق وزارة الصحة وتاريخ الصلاحية.')

@section('content')
<div class="page wrap">
    <h1 class="page__title">تحقق من دوائك</h1>
    <p class="page__sub">اكتب رقم الباركود المطبوع على العلبة أو امسحه بكاميرا الموبايل.</p>

    <div class="verify">
        <div>
            <form class="panel" method="get" action="{{ route('verify') }}" data-track="verify">
                <label for="code" style="font-weight:700;display:block;margin-bottom:8px">رقم الباركود</label>
                <div class="scanrow">
                    <input id="code" name="code" inputmode="numeric" dir="ltr" value="{{ $code }}" placeholder="6281234567890" autocomplete="off" required>
                    <button class="btn">افحص</button>
                    <button class="btn btn--ghost" type="button" data-scan hidden>@icon('scan') امسح بالكاميرا</button>
                </div>
                <div class="scanner" data-scanner hidden><video playsinline muted></video></div>
                <p class="line__meta" data-scan-note style="margin-top:10px">الباركود هو الأرقام تحت الخطوط السوداء على جانب العلبة، من 8 إلى 14 رقماً.</p>
            </form>

            @if ($searched)
                @if ($product)
                    <div class="result" role="status">
                        <h2>@icon('shield') هذا الباركود مطابق لمنتج في قائمة الصيدلية</h2>
                        <dl class="facts">
                            <dt>المنتج</dt><dd>{{ $product->name }}</dd>
                            @if ($product->scientific_name)<dt>المادة الفعالة</dt><dd dir="ltr" style="text-align:end">{{ $product->scientific_name }}</dd>@endif
                            @if ($product->company)<dt>الشركة</dt><dd>{{ $product->company }}</dd>@endif
                            @if ($product->dosage_form)<dt>الشكل</dt><dd>{{ $product->dosage_form }}</dd>@endif
                            <dt>سعره عندنا</dt><dd>@iqd($product->price)</dd>
                        </dl>
                        <p class="line__meta">قارن الاسم والشركة والشكل بما على علبتك. إن اختلف شيء فلا تستعمل الدواء واسأل {{ $site['pharmacist_label'] }}.</p>
                        <div class="place__btns" style="margin-top:14px">
                            <a class="btn" href="{{ route('product', $product) }}">صفحة المنتج</a>
                            <a class="btn btn--wa" target="_blank" rel="noopener" data-track="consult" href="{{ \App\Support\Util::wa(\App\Support\Util::pharmacistNumber(), 'مرحباً، أنا قادم من موقع '.$site['name'].' وأريد التأكد من علبة دواء: '.$product->name.' (باركود '.$code.'). سأرسل صورتها.') }}">@icon('whatsapp') ارسل صورة العلبة للتأكد</a>
                        </div>
                    </div>
                @else
                    <div class="result result--no" role="status">
                        <h2>@icon('alert') هذا الباركود غير موجود في قائمتنا</h2>
                        <p>هذا لا يعني أن الدواء مغشوش: قد يكون منتجاً لا نبيعه أو عبوة بمنشأ آخر. ارسل صورة العلبة من كل الجهات إلى {{ $site['pharmacist_label'] }} لتفحصها لك مجاناً.</p>
                        <div class="place__btns" style="margin-top:14px">
                            <a class="btn btn--wa" target="_blank" rel="noopener" data-track="consult" href="{{ \App\Support\Util::wa(\App\Support\Util::pharmacistNumber(), 'مرحباً، أنا قادم من موقع '.$site['name'].' وأريد التأكد من علبة دواء باركودها '.$code.' غير موجود في الموقع. سأرسل صورتها.') }}">@icon('whatsapp') ارسل صورة العلبة</a>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <aside class="panel panel--mist">
            <h2>كيف تتأكد من علبة الدواء بنفسك</h2>
            <ol class="checklist">
                <li><div><b>لاصق وزارة الصحة</b><span>الدواء النظامي في الصيدليات الأهلية يحمل لاصق الوزارة وعليه السعر الرسمي. علبة بلا لاصق تستحق السؤال.</span></div></li>
                <li><div><b>تاريخ الصلاحية ورقم الوجبة</b><span>مطبوعان أو محفوران على العلبة والشريط معاً، ويجب أن يتطابقا.</span></div></li>
                <li><div><b>سلامة العبوة</b><span>ختم غير مفتوح، طباعة واضحة بلا أخطاء إملائية، ونشرة داخلية.</span></div></li>
                <li><div><b>شكل الدواء</b><span>لون ورائحة وشكل الحبة كما اعتدت. أي تغيّر اسأل عنه قبل الاستعمال.</span></div></li>
                <li><div><b>مصدر الشراء</b><span>اشترِ من صيدلية مجازة وخذ وصلاً. دواء بسعر أقل كثيراً من المعتاد علامة خطر.</span></div></li>
            </ol>
            <p class="line__meta" style="margin-top:16px">فحص الباركود هنا يطابق العلبة مع قائمة {{ $site['name'] }} فقط، وليس فحصاً رسمياً من وزارة الصحة.</p>
        </aside>
    </div>
</div>
@endsection
