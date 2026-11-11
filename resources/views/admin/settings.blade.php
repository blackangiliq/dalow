@extends('layouts.admin')
@section('title', 'الإعدادات')

@section('content')
<div class="head"><div><h1>الإعدادات</h1><p>بيانات الصيدلية، واتساب، التوصيل، ومظهر الموقع</p></div></div>

<form method="post" action="{{ route('admin.settings.update') }}">
    @csrf @method('put')
    <div class="card">
        <h2>بيانات الصيدلية</h2>
        <div class="form">
            <div class="field"><label for="name">اسم الصيدلية</label><input id="name" type="text" name="name" value="{{ old('name', $values['name']) }}" required></div>
            <div class="field"><label for="address">العنوان</label><input id="address" type="text" name="address" value="{{ old('address', $values['address']) }}" required></div>
            <div class="field"><label for="phone">هاتف الصيدلية</label><input id="phone" type="tel" name="phone" dir="ltr" value="{{ old('phone', $values['phone']) }}" required></div>
            <div class="field"><label for="hours">أوقات الدوام</label><input id="hours" type="text" name="hours" value="{{ old('hours', $values['hours']) }}"></div>
            <div class="field"><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" dir="ltr" value="{{ old('email', $values['email']) }}"></div>
            <div class="field"><label for="map_url">رابط الموقع على خرائط كوكل</label><input id="map_url" type="url" name="map_url" dir="ltr" value="{{ old('map_url', $values['map_url']) }}"></div>
            <div class="field"><label for="facebook_url">رابط صفحة فيسبوك</label><input id="facebook_url" type="url" name="facebook_url" dir="ltr" value="{{ old('facebook_url', $values['facebook_url']) }}"></div>
            <div class="field"><label for="instagram_url">رابط حساب إنستغرام</label><input id="instagram_url" type="url" name="instagram_url" dir="ltr" value="{{ old('instagram_url', $values['instagram_url']) }}"></div>
        </div>
    </div>

    <div class="card">
        <h2>واتساب</h2>
        <p class="card__sub">يفتح الموقع محادثة واتساب برسالة جاهزة تذكر أن الزبون قادم من الموقع، ومعها اسم المنتج ورابطه.</p>
        <div class="form form--3">
            <div class="field">
                <label for="whatsapp_pharmacist">واتساب قسم الاستشارات</label>
                <input id="whatsapp_pharmacist" type="tel" name="whatsapp_pharmacist" dir="ltr" value="{{ old('whatsapp_pharmacist', $values['whatsapp_pharmacist']) }}" placeholder="9647701234567">
                <small>تصله أسئلة زر «استشر {{ $values['pharmacist_label'] }}». إذا تركته فارغاً يستعمل رقم الطلبات.</small>
            </div>
            <div class="field">
                <label for="pharmacist_label">اسم قسم الاستشارة على الزر</label>
                <input id="pharmacist_label" type="text" name="pharmacist_label" value="{{ old('pharmacist_label', $values['pharmacist_label']) }}" required>
                <small>مثل: الصيدلانية، الصيدلاني، د. سارة</small>
            </div>
            <div class="field">
                <label for="whatsapp">واتساب الطلبات</label>
                <input id="whatsapp" type="tel" name="whatsapp" dir="ltr" value="{{ old('whatsapp', $values['whatsapp']) }}" placeholder="9647701234567">
                <small>تصله طلبات زر «اطلب عبر واتساب» في السلة وصفحة المنتج</small>
            </div>
        </div>
    </div>

    <div class="card" id="delivery">
        <h2>التوصيل والاستلام</h2>
        <p class="card__sub">شغّل وأوقف كل خدمة. يجب أن تبقى واحدة على الأقل من التوصيل أو الاستلام.</p>
        @error('delivery_enabled')<div class="flash flash--bad">{{ $message }}</div>@enderror
        <div class="toggles">
            <label class="toggle">
                <span class="switch"><input type="checkbox" name="delivery_enabled" value="1" @checked(old('delivery_enabled', $switches['delivery_enabled']))><span></span></span>
                <span><b>التوصيل للمنازل</b><small>عند الإيقاف يختفي اختيار المنطقة ويبقى الاستلام من الصيدلية فقط</small></span>
            </label>
            <label class="toggle">
                <span class="switch"><input type="checkbox" name="pickup_enabled" value="1" @checked(old('pickup_enabled', $switches['pickup_enabled']))><span></span></span>
                <span><b>الاستلام من الصيدلية</b><small>خيار بلا أجور، ولا يُطلب فيه عنوان</small></span>
            </label>
            <label class="toggle">
                <span class="switch"><input type="checkbox" name="free_delivery_enabled" value="1" @checked(old('free_delivery_enabled', $switches['free_delivery_enabled']))><span></span></span>
                <span><b>التوصيل المجاني</b><small>للطلبات التي تتجاوز المبلغ المحدد أدناه</small></span>
            </label>
        </div>
        <div class="form" style="margin-top:18px">
            <div class="field">
                <label for="delivery_areas">مناطق التوصيل وأجورها</label>
                <textarea id="delivery_areas" name="delivery_areas" rows="10" required>{{ old('delivery_areas', $values['delivery_areas']) }}</textarea>
                <small>كل منطقة بسطر، بالصيغة: اسم المنطقة|الأجرة بالدينار</small>
            </div>
            <div class="field">
                <label for="free_delivery_over">توصيل مجاني للطلبات فوق (د.ع)</label>
                <input id="free_delivery_over" type="number" name="free_delivery_over" min="0" step="1000" value="{{ old('free_delivery_over', $values['free_delivery_over']) }}">
                <small>يُستعمل فقط إذا كان «التوصيل المجاني» مشغلاً</small>
                <label for="pickup_label" style="margin-top:14px">اسم خيار الاستلام كما يراه الزبون</label>
                <input id="pickup_label" type="text" name="pickup_label" value="{{ old('pickup_label', $values['pickup_label']) }}" required>
                <small>بعد أي تغيير هنا راجع النصوص التي تذكر التوصيل في <a href="{{ route('admin.appearance') }}#texts" style="text-decoration:underline">واجهة الموقع</a> وفي الأسئلة الشائعة.</small>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>التسويق ومحركات البحث</h2>
        <div class="form">
            <div class="field">
                <label for="promo_text">نص شريط العرض أعلى الموقع</label>
                <input id="promo_text" type="text" name="promo_text" value="{{ old('promo_text', $values['promo_text']) }}" placeholder="مثلاً: خصم 10% على كل الفيتامينات هذا الأسبوع">
                <small>يظهر في أعلى كل صفحة ويقود إلى العروض. إن تركته فارغاً يُكتب تلقائياً من حد التوصيل المجاني، ويُخفى كلياً من «واجهة الموقع».</small>
            </div>
            <div class="field">
                <label for="google_verification">رمز التحقق من Google Search Console</label>
                <input id="google_verification" type="text" name="google_verification" dir="ltr" value="{{ old('google_verification', $values['google_verification']) }}">
                <small>من <a href="https://search.google.com/search-console" target="_blank" rel="noopener" style="text-decoration:underline">Search Console</a> اختر «علامة HTML» والصق قيمة content فقط، ثم أرسل خريطة الموقع: <code>{{ route('sitemap') }}</code></small>
            </div>
        </div>
    </div>

    <div class="hint" style="margin-top:18px">النصوص والأقسام والألوان وتشغيل الخصائص في صفحة <a href="{{ route('admin.appearance') }}" style="text-decoration:underline">واجهة الموقع</a>.</div>

    <button class="btn" style="margin-top:18px">حفظ الإعدادات</button>
</form>

<div class="hint" style="margin-top:18px">تغيير كلمة مرورك من صفحة <a href="{{ route('admin.profile') }}" style="text-decoration:underline">حسابي</a>، وإضافة مستخدمين للوحة من <a href="{{ route('admin.users.index') }}" style="text-decoration:underline">مستخدمو اللوحة</a>.</div>
@endsection
