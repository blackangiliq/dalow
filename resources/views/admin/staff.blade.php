@extends('layouts.admin')
@section('title', 'الموظفون')

@section('content')
<div class="head">
    <div><h1>الموظفون ومستلمو الإشعارات</h1><p>كل موظف مفعّل هنا يصله إشعار تيليجرام فور وصول طلب جديد</p></div>
    @if ($linked)
        <a class="btn btn--ghost" href="{{ route('admin.staff.index', ['contacts' => 1]) }}#contacts">@icon('users') اختر من جهات الاتصال</a>
    @endif
</div>

@if (! $linked)
    <div class="hint"><strong>حساب تيليجرام غير مربوط.</strong> تستطيع إضافة الموظفين الآن، لكن الإشعارات تبدأ بعد ربط الحساب من صفحة <a href="{{ route('admin.telegram') }}" style="text-decoration:underline">إشعارات تيليجرام</a>.</div>
@endif

<div class="card">
    <h2>إضافة موظف برقم موبايله</h2>
    <form method="post" action="{{ route('admin.staff.store') }}" class="inline">
        @csrf
        <input type="text" name="name" placeholder="الاسم" aria-label="الاسم" value="{{ old('name') }}" required style="flex:1;min-width:160px">
        <input type="tel" name="phone" placeholder="07701234567" aria-label="رقم الموبايل" dir="ltr" value="{{ old('phone') }}" required style="width:170px">
        <input type="text" name="role" placeholder="الوظيفة (اختياري)" aria-label="الوظيفة" value="{{ old('role') }}" style="width:180px">
        <input type="hidden" name="notify" value="1">
        <button class="btn">أضف الموظف</button>
    </form>
    <p class="card__sub" style="margin:12px 0 0">اكتب الرقم المسجل به الموظف في تيليجرام. لا يحتاج الموظف أن يفعل أي شيء، تصله الرسائل من حساب الصيدلية مباشرة.</p>
</div>

@if ($contactsError)
    <div class="flash flash--bad" id="contacts">@icon('alert') <span>{{ $contactsError }}</span></div>
@elseif ($contacts !== null)
<form class="card" id="contacts" method="post" action="{{ route('admin.staff.import') }}">
    @csrf
    <div class="card__head"><h2>جهات اتصال حساب {{ $account['name'] ?? 'تيليجرام' }}</h2><a href="{{ route('admin.staff.index') }}">إغلاق</a></div>
    @if ($contacts->isEmpty())
        <p class="empty">لا توجد جهات اتصال في هذا الحساب. أضف الموظفين بأرقامهم من الأعلى.</p>
    @else
        <input type="search" placeholder="ابحث بالاسم أو الرقم" aria-label="بحث في جهات الاتصال" data-pick-filter style="margin-bottom:12px">
        <div class="picklist">
            @foreach ($contacts as $c)
                <label @class(['pick', 'is-added' => $c['added']]) data-pick="{{ $c['name'] }} {{ $c['local'] }}">
                    <input type="checkbox" name="pick[]" value="{{ json_encode(['id' => $c['id'], 'name' => $c['name'], 'phone' => $c['local']], JSON_UNESCAPED_UNICODE) }}" @disabled($c['added'])>
                    <span>{{ $c['name'] }}<span class="t-sub num">{{ $c['local'] }}</span>@if ($c['added'])<span class="t-sub">مضاف مسبقاً</span>@endif</span>
                </label>
            @endforeach
        </div>
        <button class="btn" style="margin-top:16px">أضف المحددين كمستلمين</button>
    @endif
</form>
<script>
document.querySelector('[data-pick-filter]')?.addEventListener('input', (e) => {
    const q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('[data-pick]').forEach((el) => { el.hidden = q !== '' && !el.dataset.pick.toLowerCase().includes(q); });
});
</script>
@endif

<div class="card">
    <h2>الموظفون</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الاسم</th><th>الموبايل</th><th>الوظيفة</th><th>تيليجرام</th><th>الإشعارات</th><th></th></tr></thead>
            <tbody>
            @forelse ($staff as $s)
                <tr>
                    <td><input form="st{{ $s->id }}" type="text" name="name" value="{{ $s->name }}" aria-label="الاسم" required></td>
                    <td><input form="st{{ $s->id }}" type="tel" name="phone" value="{{ $s->phone }}" dir="ltr" aria-label="الموبايل" required style="min-width:140px"></td>
                    <td><input form="st{{ $s->id }}" type="text" name="role" value="{{ $s->role }}" aria-label="الوظيفة"></td>
                    <td>
                        @if ($s->telegram_error)
                            <span class="pill pill--bad">لم تصل</span><span class="t-sub">{{ $s->telegram_error }}</span>
                        @elseif ($s->telegram_user_id)
                            <span class="pill pill--ok">تصله الإشعارات</span><span class="t-sub">{{ $s->telegram_name }}</span>
                        @else
                            <span class="pill pill--new">لم يُجرَّب بعد</span>
                        @endif
                    </td>
                    <td><label class="check"><input form="st{{ $s->id }}" type="checkbox" name="notify" value="1" @checked($s->notify)> يستلم</label></td>
                    <td>
                        <div class="t-actions">
                            <form id="st{{ $s->id }}" method="post" action="{{ route('admin.staff.update', $s) }}">@csrf @method('put')<button class="btn btn--sm">حفظ</button></form>
                            <form method="post" action="{{ route('admin.staff.test', $s) }}">@csrf <button class="btn btn--sm btn--ghost">إرسال تجربة</button></form>
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
