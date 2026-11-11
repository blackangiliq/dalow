@extends('layouts.shop')
@section('title', 'السلة')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <h1 class="page__title">السلة</h1>

    @if ($items->isEmpty())
        <div class="empty" style="margin-top:24px">
            @icon('cart')
            <h2>سلتك فارغة</h2>
            <p>أضف الأدوية التي تحتاجها، أو ارسل صورة الوصفة ونحن نجهزها.</p>
            <a class="btn" href="{{ route('products') }}">تصفح المنتجات</a>
            @if ($on['prescription'])<a class="btn btn--ghost" href="{{ route('prescription') }}">ارسل الوصفة</a>@endif
        </div>
    @else
        <div class="split">
            <div class="panel">
                @foreach ($items as $item)
                    <div class="line">
                        <a class="line__ico" style="--tint: {{ $item->product->tint }}" href="{{ route('product', $item->product) }}" tabindex="-1" aria-hidden="true">
                            @if ($item->product->image_url)<img src="{{ $item->product->image_url }}" alt="">@else @icon($item->product->icon) @endif
                        </a>
                        <div>
                            <a class="line__name" href="{{ route('product', $item->product) }}">{{ $item->product->name }}</a>
                            <div class="line__meta">@iqd($item->product->price) للقطعة @if ($item->product->requires_prescription) · يحتاج وصفة @endif</div>
                            @foreach ($conflicts[$item->product->id] ?? [] as $hit)
                                <div class="line__warn">@icon('alert') حساسية {{ $hit['profile']->relation === 'self' ? 'مسجلة لك' : 'مسجلة لـ'.$hit['profile']->name }} من {{ $hit['substance'] }}</div>
                            @endforeach
                        </div>
                        <div class="line__end">
                            <strong>@iqd($item->line)</strong>
                            <form method="post" action="{{ route('cart.update') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $item->product->id }}">
                                <label class="sr" for="qty{{ $item->product->id }}">الكمية</label>
                                <select id="qty{{ $item->product->id }}" name="qty" onchange="this.form.submit()">
                                    @for ($i = 1; $i <= min(20, max($item->qty, $item->product->stock)); $i++)
                                        <option value="{{ $i }}" @selected($i === $item->qty)>{{ $i }}</option>
                                    @endfor
                                </select>
                                <button class="link" name="qty" value="0">حذف</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <aside class="panel panel--mist">
                <h2>ملخص الطلب</h2>
                <dl class="sum">
                    <div><dt>مجموع المواد</dt><dd>@iqd($subtotal)</dd></div>
                    @if ($discount)
                        <div><dt>خصم الكود <span class="num">{{ $coupon->code }}</span></dt><dd style="color:var(--ok)">-@iqd($discount)</dd></div>
                    @endif
                    <div><dt>{{ $deliveryOn ? 'التوصيل' : 'الاستلام' }}</dt><dd>{{ $deliveryOn ? 'يحسب حسب المنطقة' : 'من الصيدلية، بدون أجور' }}</dd></div>
                </dl>
                @if ($on['coupons'])
                    <form method="post" action="{{ route('cart.coupon') }}" class="couponrow">
                        @csrf
                        @if ($coupon)
                            <span class="couponrow__on">@icon('tag') {{ $coupon->label }}</span>
                            <button class="link" name="action" value="remove">إزالة الكود</button>
                        @else
                            <label class="sr" for="code">كود الخصم</label>
                            <input id="code" name="code" value="{{ old('code') }}" placeholder="عندك كود خصم؟" dir="ltr" autocomplete="off" required>
                            <button class="btn btn--sm btn--ghost">تطبيق</button>
                        @endif
                    </form>
                    @error('code')<span class="err" style="color:var(--coral);font-size:14.5px;font-weight:500">{{ $message }}</span>@enderror
                @endif
                @php $free = $freeOver; $afterDiscount = $subtotal - $discount; @endphp
                @if ($free)
                    <div class="freebar">
                        <i style="--w: {{ min(100, round($afterDiscount * 100 / $free)) }}%"></i>
                        <p>@if ($afterDiscount < $free) أضف بقيمة @iqd($free - $afterDiscount) ليصبح التوصيل مجانياً. @else طلبك مؤهل للتوصيل المجاني. @endif</p>
                    </div>
                @endif
                <a class="btn btn--buy btn--block" style="margin-top:18px" href="{{ route('checkout') }}">إتمام الطلب</a>
                @if ($site['whatsapp'] && $on['wa_order'])
                    @php
                        $waText = "مرحباً، أريد طلب هذه المواد من موقع {$site['name']}:\n"
                            .$items->map(fn ($i) => "• {$i->product->name} × {$i->qty} = ".\App\Support\Util::money($i->line))->implode("\n")
                            ."\n\nمجموع المواد: ".\App\Support\Util::money($subtotal)."\n\nالاسم: \nالمنطقة والعنوان: ";
                    @endphp
                    <a class="btn btn--wa-line btn--block" style="margin-top:10px" target="_blank" rel="noopener" data-track="wa-order" href="{{ \App\Support\Util::wa($site['whatsapp'], $waText) }}">@icon('whatsapp') اطلب عبر واتساب</a>
                @endif
                <a class="btn btn--ghost btn--block" style="margin-top:10px" href="{{ route('products') }}">متابعة التسوق</a>
            </aside>
        </div>
    @endif
</div>
@endsection
