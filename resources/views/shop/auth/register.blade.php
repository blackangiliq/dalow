@extends('layouts.shop')
@section('title', 'إنشاء حساب')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <div class="authbox">
        <h1 class="page__title">إنشاء حساب</h1>
        <p class="page__sub">برقم موبايلك فقط، بلا بريد إلكتروني.</p>
        @if (session('error'))<div class="note note--info">@icon('info') <p>{{ session('error') }}</p></div>@endif

        <form class="panel" method="post" action="{{ route('customer.register') }}">
            @csrf
        <div class="hp" aria-hidden="true"><label>اترك هذا الحقل فارغاً <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
            <div class="form">
                <div @class(['field', 'bad' => $errors->has('name')])>
                    <label for="name">الاسم</label>
                    <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                    @error('name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div @class(['field', 'bad' => $errors->has('phone')])>
                    <label for="phone">رقم الموبايل</label>
                    <input id="phone" name="phone" type="tel" inputmode="numeric" dir="ltr" style="text-align:end" value="{{ old('phone') }}" placeholder="07701234567" autocomplete="tel" required>
                    @error('phone')<span class="err">{{ $message }}</span>@else<span class="hint">على هذا الرقم نرسل لك تنبيه انخفاض السعر</span>@enderror
                </div>
                <div @class(['field', 'bad' => $errors->has('password')])>
                    <label for="password">كلمة المرور</label>
                    <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password" minlength="6" required>
                    @error('password')<span class="err">{{ $message }}</span>@else<span class="hint">6 أحرف على الأقل</span>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">تأكيد كلمة المرور</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" dir="ltr" autocomplete="new-password" required>
                </div>
                @if ($on['health_profile'])
                <fieldset @class(['askbox', 'bad' => $errors->hasAny(['allergies', 'health_consent'])]) data-allergy-ask>
                    <legend>هل لديك حساسية من أي دواء؟</legend>
                    <div class="seg2">
                        <label><input type="radio" name="allergy_answer" value="none" @checked(old('allergy_answer') === 'none')> <span>لا توجد حساسية</span></label>
                        <label><input type="radio" name="allergy_answer" value="yes" @checked(old('allergy_answer') === 'yes')> <span>نعم، لدي حساسية</span></label>
                    </div>
                    <div class="askbox__more" data-allergy-more @if (old('allergy_answer') !== 'yes') hidden @endif>
                        <label for="allergies">من ماذا؟ اختر أو اكتب، وافصل بفاصلة</label>
                        <input id="allergies" name="allergies" value="{{ old('allergies') }}" placeholder="مثلاً: البنسلين، الأسبرين">
                        <div class="quickchips">@foreach (\App\Support\AllergyCheck::COMMON as $c)<button type="button" data-chip="{{ $c }}">{{ $c }}</button>@endforeach</div>
                        @error('allergies')<span class="err">{{ $message }}</span>@enderror
                        <label class="checkline"><input type="checkbox" name="health_consent" value="1" @checked(old('health_consent'))> <span>أوافق على حفظ معلوماتي الصحية ليطلع عليها صيدلاني الصيدلية فقط، لسلامتي عند صرف الدواء</span></label>
                        @error('health_consent')<span class="err">{{ $message }}</span>@enderror
                    </div>
                    <span class="hint">يراها الصيدلاني مع كل طلب، وننبهك إن اخترت دواءً يتعارض معها. تكمل باقي ملفك الصحي بعد التسجيل.</span>
                </fieldset>
                @endif
                <label class="checkline"><input type="checkbox" name="notify_telegram" value="1" @checked(old('notify_telegram', true))> <span>أرسلوا لي رسالة على رقمي (تيليجرام) عند انخفاض سعر منتج طلبت التنبيه عليه</span></label>
                <label class="checkline"><input type="checkbox" name="notify_offers" value="1" @checked(old('notify_offers', true))> <span>أظهروا لي العروض الجديدة في إشعارات حسابي</span></label>
            </div>
            <button class="btn btn--buy btn--block" style="margin-top:20px">أنشئ الحساب</button>
        </form>

        <ul class="perks">
            <li>@icon('check') <span>تنبيه على رقمك عند انخفاض سعر أي منتج تختاره</span></li>
            <li>@icon('check') <span>طلباتك محفوظة، وتعيد طلب دوائك الشهري بضغطة</span></li>
            <li>@icon('check') <span>بياناتك وعنوانك يُملآن تلقائياً عند كل طلب</span></li>
            @if ($on['health_profile'])<li>@icon('check') <span>ملف صحي لك ولعائلتك: الحساسية والأدوية والتحاليل، يراه الصيدلاني مع كل طلب</span></li>@endif
        </ul>
        <p class="authswap">عندك حساب؟ <a href="{{ route('customer.login') }}">سجّل الدخول</a></p>
    </div>
</div>
@endsection
