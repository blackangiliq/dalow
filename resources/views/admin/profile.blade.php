@extends('layouts.admin')
@section('title', 'حسابي')

@section('content')
<div class="head"><div><h1>حسابي</h1><p><span dir="ltr">{{ $user->email }}</span> · {{ \App\Models\User::ROLES[$user->role ?: 'admin'] }}</p></div></div>

<form class="card" method="post" action="{{ route('admin.profile.update') }}" style="max-width:760px">
    @csrf @method('put')
    <div class="form">
        <div class="field field--full"><label for="name">الاسم</label><input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required></div>
        <div class="field field--full"><label for="current_password">كلمة المرور الحالية</label><input id="current_password" type="password" name="current_password" dir="ltr" autocomplete="current-password"><small>تُطلب فقط إذا أردت تغيير كلمة المرور</small></div>
        <div class="field"><label for="password">كلمة المرور الجديدة</label><input id="password" type="password" name="password" dir="ltr" autocomplete="new-password" minlength="8"></div>
        <div class="field"><label for="password_confirmation">تأكيدها</label><input id="password_confirmation" type="password" name="password_confirmation" dir="ltr" autocomplete="new-password"></div>
    </div>
    <button class="btn" style="margin-top:16px">حفظ</button>
</form>
@endsection
