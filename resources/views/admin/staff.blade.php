@extends('layouts.admin')
@section('title', 'الموظفون والإشعارات')

@section('content')
<div class="head">
    <div><h1>الموظفون والإشعارات</h1><p>كل موظف مربوط بتيليجرام يصله إشعار فور وصول طلب جديد</p></div>
    @if ($configured)
    <form method="post" action="{{ route('admin.staff.sync') }}">@csrf <button class="btn btn--ghost">@icon('send') مزامنة مع تيليجرام</button></form>
    @endif
</div>

@if (! $configured)
    <div class="hint"><strong>البوت غير مضبوط.</strong> أدخل توكن البوت أولاً من <a href="{{ route('admin.settings') }}" style="text-decoration:underline">الإعدادات</a>.</div>
@elseif ($botError)
    <div class="flash flash--bad">@icon('alert') <span>تيليجرام رفض التوكن: {{ $botError }}. راجع التوكن في الإعدادات.</span></div>
@else
    <div class="hint">
        <strong>طريقة ربط الموظف:</strong>
        <ol>
            <li>أضف اسمه ورقم موبايله هنا (نفس الرقم المسجل به في تيليجرام).</li>
            <li>يفتح الموظف البوت @if ($botUsername)<a href="https://t.me/{{ $botUsername }}" target="_blank" rel="noopener" style="text-decoration:underline"><code>&#64;{{ $botUsername }}</code></a>@endif ويضغط «ابدأ» ثم زر «مشاركة رقم هاتفي».</li>
            <li>يُربط حسابه تلقائياً. إذا كان الموقع يعمل محلياً بدون رابط https اضغط «مزامنة مع تيليجرام» بعد الخطوة 2.</li>
        </ol>
    </div>
@endif

<div class="card">
    <h2>موظف جديد</h2>
    <form method="post" action="{{ route('admin.staff.store') }}" class="inline">
        @csrf
        <input type="text" name="name" placeholder="الاسم" aria-label="الاسم" value="{{ old('name') }}" required style="flex:1;min-width:160px">
        <input type="tel" name="phone" placeholder="07701234567" aria-label="رقم الموبايل" dir="ltr" value="{{ old('phone') }}" required style="width:170px">
        <input type="text" name="role" placeholder="الوظيفة (اختياري)" aria-label="الوظيفة" value="{{ old('role') }}" style="width:180px">
        <input type="hidden" name="notify" value="1">
        <button class="btn">أضف الموظف</button>
    </form>
</div>

<div class="card">
    <h2>الموظفون</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الاسم</th><th>الموبايل</th><th>الوظيفة</th><th>معرف تيليجرام</th><th>الإشعارات</th><th></th></tr></thead>
            <tbody>
            @forelse ($staff as $s)
                <tr>
                    <td><input form="st{{ $s->id }}" type="text" name="name" value="{{ $s->name }}" aria-label="الاسم" required></td>
                    <td><input form="st{{ $s->id }}" type="tel" name="phone" value="{{ $s->phone }}" dir="ltr" aria-label="الموبايل" required style="width:150px"></td>
                    <td><input form="st{{ $s->id }}" type="text" name="role" value="{{ $s->role }}" aria-label="الوظيفة"></td>
                    <td>
                        <input form="st{{ $s->id }}" type="text" name="telegram_chat_id" value="{{ $s->telegram_chat_id }}" dir="ltr" placeholder="غير مربوط" aria-label="معرف المحادثة" style="width:140px">
                        <span class="t-sub">@if ($s->telegram_chat_id)<span class="pill pill--ok">مربوط</span>@else<span class="pill pill--new">بانتظار الربط</span>@endif</span>
                    </td>
                    <td><label class="check"><input form="st{{ $s->id }}" type="checkbox" name="notify" value="1" @checked($s->notify)> يستلم</label></td>
                    <td>
                        <div class="t-actions">
                            <form id="st{{ $s->id }}" method="post" action="{{ route('admin.staff.update', $s) }}">@csrf @method('put')<button class="btn btn--sm">حفظ</button></form>
                            @if ($s->telegram_chat_id)
                            <form method="post" action="{{ route('admin.staff.test', $s) }}">@csrf <button class="btn btn--sm btn--ghost">إرسال تجربة</button></form>
                            @endif
                            <form method="post" action="{{ route('admin.staff.destroy', $s) }}" onsubmit="return confirm('حذف {{ $s->name }}؟')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">لا يوجد موظفون. أضف أول موظف من الأعلى.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
