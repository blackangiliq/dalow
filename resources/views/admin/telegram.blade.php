@extends('layouts.admin')
@section('title', 'إشعارات تيليجرام')

@section('content')
@php
    $guide = [
        ['افتح <a href="https://my.telegram.org/apps" target="_blank" rel="noopener" dir="ltr"><strong>my.telegram.org/apps</strong></a> واكتب رقم هاتف الحساب الذي سترسل منه الصيدلية الإشعارات بالصيغة الدولية (مثل <code>+9647701234567</code>)، ثم اضغط <strong>Next</strong>.', 'step-1.jpg'],
        ['يصلك كود التأكيد <strong>داخل تطبيق تيليجرام</strong> نفسه (رسالة من «Telegram»)، وليس برسالة SMS. انسخه ولا ترسله لأي شخص.', 'step-2.jpg'],
        ['الصق الكود في خانة <strong>Confirmation code</strong> ثم اضغط <strong>Sign In</strong>.', 'step-3.jpg'],
        ['من قائمة «Your Telegram Core» اضغط <strong>API development tools</strong> فقط. <span class="warn">لا تضغط Deactivate account، فهو لحذف الحساب.</span>', 'step-4.jpg'],
        ['إذا ظهر نموذج <strong>Create new application</strong> املأه ثم اضغط <strong>Create application</strong>. (إذا كان لديك تطبيق سابق تظهر بياناته مباشرة ولا تحتاج غيره.)'
            .'<ul><li><strong>App title</strong> و <strong>Short name</strong>: حروف إنجليزية وأرقام فقط، والاسم المختصر من 5 إلى 32 حرفاً، مثل <code>aldalupharmacy</code>.</li>'
            .'<li><strong>URL</strong> فارغ، <strong>Platform</strong>: Android، و <strong>Description</strong> وصف قصير بالإنجليزية مثل <code>Pharmacy order notifications</code>.</li>'
            .'<li>لا تكتب telegram أو tg أو bot أو api في الأسماء، ولا حروفاً عربية، فقد يرفض تيليجرام الطلب.</li></ul>', 'step-5.jpg'],
        ['في صفحة <strong>App configuration</strong> انسخ <strong dir="ltr">App api_id</strong> و <strong dir="ltr">App api_hash</strong> (المحاطين بالأحمر) والصقهما في الحقلين أدناه، ثم اضغط «حفظ بيانات الاتصال». باقي الصفحة لا تحتاجه.', 'step-6.jpg'],
    ];
@endphp
<div class="head">
    <div><h1>إشعارات تيليجرام</h1><p>يرسل الموقع إشعار كل طلب جديد من حساب تيليجرام حقيقي تملكه الصيدلية، مباشرة إلى أرقام الموظفين</p></div>
</div>

<div class="steps3" aria-label="مراحل الإعداد">
    <span @class(['did' => $configured, 'on' => ! $configured])>1. بيانات الاتصال</span>
    <span @class(['did' => $linked, 'on' => $configured && ! $linked])>2. ربط الحساب</span>
    <span @class(['did' => $linked && $recipients, 'on' => $linked && ! $recipients])>3. اختيار المستلمين</span>
</div>

<details class="fold" @if (! $configured) open @endif>
    <summary>دليل مصوّر: من أين أحصل على API ID و API Hash؟</summary>
    <div>
        <p style="margin:14px 0;color:var(--ink-soft)">هذه «بيانات تطبيق» مجانية تُنشأ مرة واحدة من حساب تيليجرام نفسه. تحتاج رقماً عليه حساب تيليجرام، ويفضّل أن يكون رقم الصيدلية.</p>
        <p class="inline" style="margin-bottom:18px">
            <a class="btn btn--ink btn--sm" href="https://my.telegram.org/apps" target="_blank" rel="noopener">فتح my.telegram.org/apps</a>
            <a class="btn btn--ghost btn--sm" href="https://core.telegram.org/api/obtaining_api_id" target="_blank" rel="noopener">شرح تيليجرام الرسمي</a>
        </p>
        <ol class="guide">
            @foreach ($guide as [$text, $image])
                <li>
                    <div>
                        {!! $text !!}
                        <a href="{{ asset('images/telegram-guide/'.$image) }}" target="_blank" rel="noopener" title="عرض الصورة بالحجم الكامل">
                            <img src="{{ asset('images/telegram-guide/'.$image) }}" alt="صورة توضيحية للخطوة {{ $loop->iteration }}" loading="lazy">
                        </a>
                    </div>
                </li>
            @endforeach
        </ol>
        <div class="hint" style="margin:20px 0 0">
            <strong>انتبه:</strong>
            <ul style="margin:6px 0 0;padding-inline-start:20px">
                <li>استخدم API ID و API Hash من الحساب نفسه الذي ستربطه، لا من حساب آخر.</li>
                <li>API Hash سرّي مثل كلمة المرور، لا تشاركه مع أحد.</li>
                <li>يفضّل رقم مخصص للصيدلية، لأن الإشعارات تصل للموظفين من هذا الحساب.</li>
            </ul>
        </div>
    </div>
</details>

<form class="card" style="margin-top:18px" method="post" action="{{ route('admin.telegram.credentials') }}">
    @csrf @method('put')
    <h2>1. بيانات الاتصال</h2>
    <div class="form">
        <div class="field">
            <label for="tg_api_id">API ID</label>
            <input id="tg_api_id" type="text" name="tg_api_id" dir="ltr" inputmode="numeric" value="{{ old('tg_api_id', $apiId) }}" placeholder="12345678" required>
            <small>الرقم الظاهر بجانب App api_id</small>
        </div>
        <div class="field">
            <label for="tg_api_hash">API Hash</label>
            <input id="tg_api_hash" type="password" name="tg_api_hash" dir="ltr" autocomplete="off" placeholder="{{ $hasHash ? 'محفوظ. اكتب قيمة جديدة لتغييره' : '32 حرفاً ورقماً' }}" @if (! $hasHash) required @endif>
            <small>@if ($hasHash)<span class="pill pill--ok">محفوظ</span>@else القيمة الظاهرة بجانب App api_hash @endif</small>
        </div>
    </div>
    <button class="btn" style="margin-top:16px">حفظ بيانات الاتصال</button>
</form>

<div class="card">
    <h2>2. ربط الحساب</h2>
    @if ($linked)
        <div class="account">
            <span class="account__av">{{ mb_substr($account['name'] ?: 'ت', 0, 1) }}</span>
            <div class="grow">
                <b>{{ $account['name'] ?: 'حساب تيليجرام' }}</b>
                <span class="t-sub"><span class="num">+{{ $account['phone'] }}</span> @if ($account['username']) · <span dir="ltr">&#64;{{ $account['username'] }}</span> @endif · مربوط منذ {{ \Illuminate\Support\Carbon::parse($account['linked_at'])->diffForHumans() }}</span>
            </div>
            <form method="post" action="{{ route('admin.telegram.test') }}">@csrf <button class="btn btn--sm">إرسال رسالة تجريبية</button></form>
            <form method="post" action="{{ route('admin.telegram.unlink') }}" onsubmit="return confirm('فصل الحساب؟ ستتوقف إشعارات الطلبات حتى تربطه من جديد.')">@csrf @method('delete')<button class="btn btn--sm btn--danger">فصل الحساب</button></form>
        </div>
        <p class="card__sub" style="margin:14px 0 0">الرسالة التجريبية تصل إلى «الرسائل المحفوظة» في الحساب نفسه.</p>
    @elseif (! $configured)
        <p class="card__sub" style="margin:0">احفظ API ID و API Hash أولاً، ثم يظهر هنا نموذج ربط الحساب.</p>
    @elseif ($step === 'code')
        <form method="post" action="{{ route('admin.telegram.code') }}" class="inline">
            @csrf
            <label for="code" style="font-weight:700">الكود الذي وصلك في تطبيق تيليجرام على <span class="num">{{ session('telegram_login_phone') }}</span></label>
            <input id="code" type="text" name="code" dir="ltr" inputmode="numeric" autocomplete="one-time-code" placeholder="12345" style="width:140px" required autofocus>
            <button class="btn">تأكيد الكود</button>
        </form>
        <form method="post" action="{{ route('admin.telegram.cancel') }}" style="margin-top:10px">@csrf <button class="btn btn--sm btn--ghost">رجوع لتغيير الرقم</button></form>
    @elseif ($step === 'password')
        <form method="post" action="{{ route('admin.telegram.password') }}" class="inline">
            @csrf
            <label for="tgpass" style="font-weight:700">كلمة مرور التحقق بخطوتين للحساب</label>
            <input id="tgpass" type="password" name="password" dir="ltr" autocomplete="off" style="width:220px" required autofocus>
            <button class="btn">إكمال الربط</button>
        </form>
        <form method="post" action="{{ route('admin.telegram.cancel') }}" style="margin-top:10px">@csrf <button class="btn btn--sm btn--ghost">إلغاء</button></form>
    @else
        <form method="post" action="{{ route('admin.telegram.link') }}" class="inline">
            @csrf
            <label for="tgphone" style="font-weight:700">رقم هاتف الحساب</label>
            <input id="tgphone" type="tel" name="phone" dir="ltr" value="{{ old('phone') }}" placeholder="+9647701234567" style="width:200px" required>
            <button class="btn">إرسال كود التحقق</button>
        </form>
        <p class="card__sub" style="margin:12px 0 0">يرسل تيليجرام كوداً إلى التطبيق على هذا الرقم، تكتبه هنا في الخطوة التالية. وإذا كان الحساب محمياً بالتحقق بخطوتين نطلب كلمة المرور بعدها.</p>
    @endif
</div>

<div class="card">
    <h2>3. من يستلم الإشعارات</h2>
    <p class="card__sub" style="margin:0 0 14px">{{ $recipients ? $recipients.' موظف يستلم إشعار كل طلب جديد وكل رسالة من صفحة تواصل معنا.' : 'لم تختر أحداً بعد.' }} تضيف الموظفين بأرقامهم، أو تختارهم من جهات اتصال الحساب المرتبط.</p>
    <a class="btn btn--ghost" href="{{ route('admin.staff.index') }}">@icon('users') إدارة المستلمين</a>
</div>
@endsection
