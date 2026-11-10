@extends('layouts.shop')
@section('title', 'السلة')

@section('content')
<div class="page wrap">
    <h1 class="page__title">السلة</h1>

    @if ($items->isEmpty())
        <div class="empty" style="margin-top:24px">
            @icon('cart')
            <h2>سلتك فارغة</h2>
            <p>أضف الأدوية التي تحتاجها، أو ارسل صورة الوصفة ونحن نجهزها.</p>
            <a class="btn" href="{{ route('products') }}">تصفح المنتجات</a>
            <a class="btn btn--ghost" href="{{ route('prescription') }}">ارسل الوصفة</a>
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
                    <div><dt>التوصيل</dt><dd>يحسب حسب المنطقة</dd></div>
                </dl>
                @php $free = (int) $site['free_delivery_over']; @endphp
                @if ($free && $subtotal < $free)
                    <p class="line__meta" style="margin-top:10px">أضف بقيمة @iqd($free - $subtotal) ليصبح التوصيل مجانياً.</p>
                @elseif ($free)
                    <p class="line__meta" style="margin-top:10px;color:var(--ok)">طلبك مؤهل للتوصيل المجاني.</p>
                @endif
                <a class="btn btn--block" style="margin-top:18px" href="{{ route('checkout') }}">إتمام الطلب</a>
                @if ($site['whatsapp'])
                    @php
                        $waText = "مرحباً، أريد طلب هذه المواد من موقع {$site['name']}:\n"
                            .$items->map(fn ($i) => "• {$i->product->name} × {$i->qty} = ".\App\Support\Util::money($i->line))->implode("\n")
                            ."\n\nمجموع المواد: ".\App\Support\Util::money($subtotal)."\n\nالاسم: \nالمنطقة والعنوان: ";
                    @endphp
                    <a class="btn btn--wa-line btn--block" style="margin-top:10px" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($site['whatsapp'], $waText) }}">@icon('whatsapp') اطلب عبر واتساب</a>
                @endif
                <a class="btn btn--ghost btn--block" style="margin-top:10px" href="{{ route('products') }}">متابعة التسوق</a>
            </aside>
        </div>
    @endif
</div>
@endsection
