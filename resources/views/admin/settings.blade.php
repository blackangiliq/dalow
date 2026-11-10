@extends('layouts.admin')
@section('title', 'الإعدادات')

@section('content')
<div class="head"><div><h1>الإعدادات</h1></div></div>

<form method="post" action="{{ route('admin.settings.update') }}">
    @csrf @method('put')
    <div class="card">
        <h2>بيانات الصيدلية</h2>
        <div class="form">
            <div class="field"><label for="name">اسم الصيدلية</label><input id="name" type="text" name="name" value="{{ old('name', $values['name']) }}" required></div>
            <div class="field"><label for="address">العنوان</label><input id="address" type="text" name="address" value="{{ old('address', $values['address']) }}" required></div>
            <div class="field"><label for="phone">هاتف الصيدلية</label><input id="phone" type="tel" name="phone" dir="ltr" value="{{ old('phone', $values['phone']) }}" required></div>
            <div class="field"><label for="whatsapp">رقم واتساب</label><input id="whatsapp" type="tel" name="whatsapp" dir="ltr" value="{{ old('whatsapp', $values['whatsapp']) }}"><small>بالصيغة الدولية بدون +، مثل 9647701234567</small></div>
            <div class="field"><label for="hours">أوقات الدوام</label><input id="hours" type="text" name="hours" value="{{ old('hours', $values['hours']) }}"></div>
            <div class="field"><label for="map_url">رابط الموقع على خرائط كوكل</label><input id="map_url" type="url" name="map_url" dir="ltr" value="{{ old('map_url', $values['map_url']) }}"></div>
        </div>
    </div>

    <div class="card">
        <h2>التوصيل</h2>
        <div class="form">
            <div class="field">
                <label for="delivery_areas">المناطق وأجور التوصيل</label>
                <textarea id="delivery_areas" name="delivery_areas" rows="10" required>{{ old('delivery_areas', $values['delivery_areas']) }}</textarea>
                <small>كل منطقة بسطر، بالصيغة: اسم المنطقة|الأجرة بالدينار</small>
            </div>
            <div class="field">
                <label for="free_delivery_over">توصيل مجاني للطلبات فوق (د.ع)</label>
                <input id="free_delivery_over" type="number" name="free_delivery_over" min="0" step="1000" value="{{ old('free_delivery_over', $values['free_delivery_over']) }}">
                <small>اكتب 0 لإلغاء التوصيل المجاني</small>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>إشعارات تيليجرام</h2>
        <p class="card__sub">أنشئ بوتاً من <code>@BotFather</code> في تيليجرام بالأمر <code>/newbot</code> ثم الصق التوكن هنا.</p>
        <div class="form">
            <div class="field">
                <label for="telegram_bot_token">توكن البوت</label>
                <input id="telegram_bot_token" type="password" name="telegram_bot_token" dir="ltr" autocomplete="off" placeholder="{{ $hasToken ? 'محفوظ. اكتب توكناً جديداً لتغييره' : '123456789:AA...' }}">
                <small>@if ($hasToken)<span class="pill pill--ok">التوكن محفوظ</span>@else<span class="pill pill--new">لم يُدخل بعد</span>@endif</small>
            </div>
            <div class="field">
                <label for="telegram_group_chat_id">معرف مجموعة الصيدلية (اختياري)</label>
                <input id="telegram_group_chat_id" type="text" name="telegram_group_chat_id" dir="ltr" value="{{ old('telegram_group_chat_id', $values['telegram_group_chat_id']) }}" placeholder="-1001234567890">
                <small>أضف البوت إلى مجموعة الموظفين لتصل الطلبات إليها أيضاً</small>
            </div>
        </div>
    </div>

    <button class="btn" style="margin-top:18px">حفظ الإعدادات</button>
</form>

@if ($hasToken)
<div class="card" style="margin-top:18px">
    <h2>الربط التلقائي (Webhook)</h2>
    <p class="card__sub">
        @if ($webhookUrl)<span class="pill pill--ok">مفعّل</span> يستقبل الموقع رسائل البوت وأزرار تأكيد الطلب مباشرة.
        @else<span class="pill pill--new">غير مفعّل</span> فعّله بعد رفع الموقع على رابط https. قبل ذلك استخدم زر «مزامنة مع تيليجرام» في صفحة الموظفين.
        @endif
    </p>
    <form method="post" action="{{ route('admin.settings.webhook') }}" class="inline">
        @csrf
        <button class="btn btn--ink" name="action" value="set">تفعيل الربط التلقائي</button>
        @if ($webhookUrl)<button class="btn btn--danger" name="action" value="remove">إلغاء الربط</button>@endif
    </form>
</div>
@endif

<form class="card" method="post" action="{{ route('admin.settings.password') }}" style="margin-top:18px">
    @csrf @method('put')
    <h2>تغيير كلمة المرور</h2>
    <div class="form form--3">
        <div class="field"><label for="current_password">الحالية</label><input id="current_password" type="password" name="current_password" dir="ltr" autocomplete="current-password" required></div>
        <div class="field"><label for="password">الجديدة</label><input id="password" type="password" name="password" dir="ltr" autocomplete="new-password" minlength="8" required></div>
        <div class="field"><label for="password_confirmation">تأكيد الجديدة</label><input id="password_confirmation" type="password" name="password_confirmation" dir="ltr" autocomplete="new-password" required></div>
    </div>
    <button class="btn btn--ghost" style="margin-top:16px">تغيير كلمة المرور</button>
</form>
@endsection
