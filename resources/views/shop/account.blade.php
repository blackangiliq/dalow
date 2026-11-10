@extends('layouts.shop')
@section('title', 'حسابي')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:end">
        <div>
            <h1 class="page__title">أهلاً {{ \Illuminate\Support\Str::before($customer->name, ' ') }}</h1>
            <p class="page__sub"><span class="num">{{ $customer->phone }}</span></p>
        </div>
        <form method="post" action="{{ route('customer.logout') }}">@csrf <button class="btn btn--ghost btn--sm">تسجيل الخروج</button></form>
    </div>

    <nav class="tabs" aria-label="أقسام الحساب">
        <a href="{{ route('account') }}" @class(['on' => $tab === 'orders'])>@icon('list') طلباتي</a>
        <a href="{{ route('account', ['tab' => 'favorites']) }}" @class(['on' => $tab === 'favorites'])>@icon('heart') تنبيهات السعر @if ($favoriteCount)<span class="num">({{ $favoriteCount }})</span>@endif</a>
        <a href="{{ route('account', ['tab' => 'notifications']) }}" @class(['on' => $tab === 'notifications'])>@icon('bell') الإشعارات @if ($unreadCount && $tab !== 'notifications')<b class="num">{{ $unreadCount }}</b>@endif</a>
        <a href="{{ route('account', ['tab' => 'profile']) }}" @class(['on' => $tab === 'profile'])>@icon('user') بياناتي</a>
    </nav>

    @if ($tab === 'orders')
        @forelse ($orders as $o)
            <div class="orow">
                <div>
                    <b class="num">{{ $o->number }}</b>
                    <span @class(['badge', 'badge--go' => in_array($o->status, ['confirmed', 'preparing', 'shipping']), 'badge--done' => $o->status === 'delivered', 'badge--off' => $o->status === 'cancelled'])>{{ $o->status === 'new' ? 'استلمنا الطلب' : $o->status_label }}</span>
                    <small>{{ $o->created_at->translatedFormat('j F Y') }} · {{ $o->type === 'prescription' ? 'طلب بوصفة طبية' : $o->items->pluck('name')->take(3)->implode('، ').($o->items->count() > 3 ? ' وغيرها' : '') }}</small>
                </div>
                <div class="acts">
                    @if ($o->total)<strong>@iqd($o->total)</strong>@endif
                    <a class="btn btn--sm btn--ghost" href="{{ route('track', ['number' => $o->number, 'phone' => $o->phone]) }}">تتبع</a>
                    @if ($o->items->count())
                        <form method="post" action="{{ route('account.reorder', $o) }}">@csrf <button class="btn btn--sm">@icon('repeat') أعد الطلب</button></form>
                    @endif
                </div>
            </div>
        @empty
            <div class="empty">@icon('cart')<h2>لا توجد طلبات في حسابك بعد</h2><p>كل طلب تسجله وأنت داخل حسابك يُحفظ هنا لتعيد طلبه بضغطة.</p><a class="btn" href="{{ route('products') }}">تصفح المنتجات</a></div>
        @endforelse

    @elseif ($tab === 'favorites')
        @if ($favorites->count())
            <p class="page__sub" style="margin-bottom:16px">عند انخفاض سعر أي منتج هنا يصلك إشعار على رقمك. اضغط القلب لإزالة المنتج من القائمة.</p>
            <div class="grid">
                @foreach ($favorites as $p) @include('partials.product-card', ['p' => $p]) @endforeach
            </div>
        @else
            <div class="empty">@icon('heart')<h2>لم تطلب تنبيهاً على أي منتج بعد</h2><p>اضغط القلب على أي منتج، وعند انخفاض سعره نرسل لك إشعاراً على رقمك.</p><a class="btn" href="{{ route('products') }}">تصفح المنتجات</a></div>
        @endif

    @elseif ($tab === 'notifications')
        @forelse ($notifications as $n)
            <a @class(['notice', 'notice--new' => ! $n->read_at]) href="{{ $n->url ?: '#' }}">
                @icon('tag')
                <div><b>{{ $n->title }}</b><br>{{ $n->body }}<br><small>{{ $n->created_at->diffForHumans() }}</small></div>
            </a>
        @empty
            <div class="empty">@icon('bell')<h2>لا توجد إشعارات</h2><p>تظهر هنا تنبيهات انخفاض السعر والعروض الجديدة.</p></div>
        @endforelse

    @else
        <form class="panel" method="post" action="{{ route('account.update') }}" style="max-width:760px">
            @csrf @method('put')
            <div class="form">
                <div @class(['field', 'bad' => $errors->has('name')])>
                    <label for="name">الاسم</label>
                    <input id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                    @error('name')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="area">المنطقة</label>
                    <select id="area" name="area">
                        <option value="">غير محددة</option>
                        @foreach ($areas as $name => $fee)<option value="{{ $name }}" @selected(old('area', $customer->area) === $name)>{{ $name }}</option>@endforeach
                    </select>
                </div>
                <div class="field field--full">
                    <label for="address">العنوان</label>
                    <input id="address" name="address" value="{{ old('address', $customer->address) }}" placeholder="المحلة، الزقاق، رقم الدار، أقرب نقطة دالة">
                    <span class="hint">يُملأ تلقائياً عند كل طلب</span>
                </div>
                <label class="checkline field--full"><input type="checkbox" name="notify_telegram" value="1" @checked(old('notify_telegram', $customer->notify_telegram))> <span>أرسلوا لي رسالة على رقمي (تيليجرام) عند انخفاض سعر منتج طلبت التنبيه عليه</span></label>
                <label class="checkline field--full"><input type="checkbox" name="notify_offers" value="1" @checked(old('notify_offers', $customer->notify_offers))> <span>أظهروا لي العروض الجديدة في إشعارات حسابي</span></label>
                <div @class(['field', 'bad' => $errors->has('password')])>
                    <label for="password">كلمة مرور جديدة (اختياري)</label>
                    <input id="password" name="password" type="password" dir="ltr" autocomplete="new-password">
                    @error('password')<span class="err">{{ $message }}</span>@enderror
                </div>
                <div class="field">
                    <label for="password_confirmation">تأكيدها</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" dir="ltr" autocomplete="new-password">
                </div>
            </div>
            <button class="btn" style="margin-top:18px">حفظ بياناتي</button>
        </form>
    @endif
</div>
@endsection
