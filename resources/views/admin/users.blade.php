@extends('layouts.admin')
@section('title', 'مستخدمو اللوحة')

@section('content')
<div class="head"><div><h1>مستخدمو لوحة التحكم</h1><p>من يدخل اللوحة وما يستطيع فعله</p></div></div>

<div class="hint">
    <strong>الصلاحيات:</strong> «المدير» يرى كل شيء. «الموظف» يعمل على الطلبات، المنتجات، الزبائن، تنبيهات السعر، طلبات التوفير، والرسائل فقط، ولا يرى الإحصاءات ولا الإعدادات ولا الاستيراد.
</div>

<form class="card" method="post" action="{{ route('admin.users.store') }}">
    @csrf
    <h2>مستخدم جديد</h2>
    <div class="form form--4">
        <div class="field"><label for="name">الاسم</label><input id="name" type="text" name="name" value="{{ old('name') }}" required></div>
        <div class="field"><label for="email">البريد الإلكتروني</label><input id="email" type="email" name="email" dir="ltr" value="{{ old('email') }}" required></div>
        <div class="field"><label for="password">كلمة المرور</label><input id="password" type="text" name="password" dir="ltr" minlength="8" autocomplete="off" required><small>8 أحرف على الأقل</small></div>
        <div class="field"><label for="role">الصلاحية</label><select id="role" name="role">@foreach (\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected(old('role', 'staff') === $k)>{{ $l }}</option>@endforeach</select></div>
    </div>
    <button class="btn" style="margin-top:16px">أضف المستخدم</button>
</form>

<div class="card">
    <h2>المستخدمون</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الاسم</th><th>البريد</th><th>الصلاحية</th><th>كلمة مرور جديدة</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $u)
                <tr>
                    <td>{{ $u->name }} @if ($u->is(auth()->user()))<span class="pill pill--ok">أنت</span>@endif</td>
                    <td><span dir="ltr">{{ $u->email }}</span></td>
                    <td><select form="u{{ $u->id }}" name="role" aria-label="الصلاحية">@foreach (\App\Models\User::ROLES as $k => $l)<option value="{{ $k }}" @selected(($u->role ?: 'admin') === $k)>{{ $l }}</option>@endforeach</select></td>
                    <td><input form="u{{ $u->id }}" type="text" name="password" dir="ltr" placeholder="اتركه فارغاً لإبقائها" aria-label="كلمة مرور جديدة" autocomplete="off"></td>
                    <td>
                        <div class="t-actions">
                            <form id="u{{ $u->id }}" method="post" action="{{ route('admin.users.update', $u) }}">@csrf @method('put')<button class="btn btn--sm">حفظ</button></form>
                            @unless ($u->is(auth()->user()))
                                <form method="post" action="{{ route('admin.users.destroy', $u) }}" onsubmit="return confirm('حذف {{ $u->name }}؟')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف</button></form>
                            @endunless
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
