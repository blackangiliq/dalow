@extends('layouts.admin')
@section('title', 'تنبيهات انخفاض السعر')

@section('content')
<div class="head">
    <div><h1>قائمة «أعلمني عند انخفاض السعر»</h1><p>المنتجات مرتبة بعدد الزبائن الذين ينتظرون خصمها، ومع كل منتج أرقامهم</p></div>
    <div class="stat" style="padding:10px 18px"><span>مجموع الطلبات</span><b class="num">{{ $waiting }}</b></div>
</div>

<div class="hint">
    <strong>كيف تستفيد منها:</strong> المنتج الذي في أعلى القائمة هو الأكثر طلباً للخصم. ضع عليه خصماً من صفحة تعديله، فيصل الزبائن إشعار في حساباتهم (ورسالة تيليجرام لمن وافق)، ثم ذكّرهم من هنا على واتساب أو بالاتصال.
</div>

@forelse ($products as $p)
    @php
        $message = $p->discount
            ? "مرحباً، معك {$siteName}. المنتج الذي طلبت التنبيه عليه «{$p->name}» عليه الآن خصم {$p->discount}%: السعر ".\App\Support\Util::money($p->price).' بدل '.\App\Support\Util::money($p->old_price).".\n".route('product', $p)
            : "مرحباً، معك {$siteName} بخصوص «{$p->name}» الذي طلبت التنبيه عليه.\n".route('product', $p);
    @endphp
    <details @class(['alertcard', 'alertcard--focus' => $focus === $p->id]) @if ($focus === $p->id || ($loop->first && ! $focus)) open @endif id="p{{ $p->id }}">
        <summary>
            <span class="bigcount num">{{ $p->favored_by_count }}</span>
            <span class="grow"><b>{{ $p->name }}</b><span class="t-sub">{{ $p->category->name ?? 'بدون قسم' }} · السعر @iqd($p->price)</span></span>
            @if ($p->discount)
                <span class="pill pill--ok">عليه خصم {{ $p->discount }}% الآن</span>
            @else
                <span class="pill pill--new">بلا خصم</span>
            @endif
            <a class="btn btn--sm {{ $p->discount ? 'btn--ghost' : '' }}" href="{{ route('admin.products.edit', $p) }}">{{ $p->discount ? 'تعديل السعر' : 'ضع خصماً' }}</a>
        </summary>
        <div>
            <div class="people">
                @foreach ($p->favoredBy as $c)
                    <div class="person">
                        <b>{{ $c->name }}</b>
                        <a class="num" href="tel:{{ $c->phone }}" style="color:var(--link);font-weight:700">{{ $c->phone }}</a>
                        <span class="t-sub">طلب التنبيه {{ $c->pivot->created_at?->diffForHumans() }}{{ $c->notify_telegram ? '' : ' · أوقف رسائل تيليجرام' }}</span>
                        <div class="inline">
                            <a class="btn btn--sm" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($c->phone, $message) }}">@icon('whatsapp') ذكّره على واتساب</a>
                            <a class="btn btn--sm btn--ghost" href="tel:{{ $c->phone }}">@icon('phone') اتصال</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
@empty
    <div class="card"><p class="empty">لم يطلب أي زبون تنبيهاً بعد. عندما يضغط زبون مسجل على القلب في أي منتج يظهر هنا مع رقم موبايله.</p></div>
@endforelse
@endsection
