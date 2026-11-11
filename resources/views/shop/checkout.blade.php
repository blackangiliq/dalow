@extends('layouts.shop')
@section('title', 'إتمام الطلب')
@section('noindex', '1')

@section('content')
<div class="page wrap">
    <nav class="crumbs" aria-label="مسار الصفحة"><a href="{{ route('cart') }}">السلة</a><span>إتمام الطلب</span></nav>
    <h1 class="page__title">إتمام الطلب</h1>

    @error('cart')<div class="note note--bad">@icon('alert') <p>{{ $message }} <a class="more" href="{{ route('cart') }}">راجع السلة</a></p></div>@enderror

    <form class="split" method="post" action="{{ route('checkout.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="panel">
            <h2>{{ $deliveryOn ? 'بيانات التوصيل' : 'بيانات الاستلام' }}</h2>
            <div class="form">
                @if ($profiles->count() > 1 && $on['family_profiles'])
                <div class="field field--full">
                    <label for="health_profile_id">لمن هذا الطلب؟</label>
                    <select id="health_profile_id" name="health_profile_id">
                        @foreach ($profiles as $p)<option value="{{ $p->id }}" @selected((int) old('health_profile_id') === $p->id)>{{ $p->relation === 'self' ? 'لي أنا' : $p->name.' ('.\App\Models\HealthProfile::RELATIONS[$p->relation].')' }}{{ $p->allergies->count() ? ' · لديه حساسية' : '' }}</option>@endforeach
                    </select>
                    <span class="hint">يرى الصيدلاني ملفه الصحي وحساسيته مع الطلب</span>
                </div>
                @endif
                @include('shop._customer')

                @if ($needsRx)
                <div @class(['field', 'field--full', 'bad' => $errors->has('prescription')])>
                    <span style="font-weight:500">صورة الوصفة الطبية</span>
                    <label class="drop">
                        @icon('upload')
                        <b>اختر صورة الوصفة أو التقطها بالكاميرا</b>
                        <small>في سلتك دواء يصرف بوصفة. JPG أو PNG حتى 6 ميغابايت.</small>
                        <input type="file" name="prescription" accept="image/*" required>
                    </label>
                    @error('prescription')<span class="err">{{ $message }}</span>@enderror
                </div>
                @endif
            </div>
        </div>

        <aside class="panel panel--mist">
            <h2>طلبك</h2>
            @foreach ($items as $item)
                <div class="sum" style="margin-bottom:8px">
                    <div><dt>{{ $item->product->name }} × <span class="num">{{ $item->qty }}</span></dt><dd style="white-space:nowrap">@iqd($item->line)</dd></div>
                </div>
            @endforeach
            <dl class="sum" style="border-top:1.5px solid var(--line);padding-top:12px;margin-top:12px">
                <div><dt>مجموع المواد</dt><dd>@iqd($subtotal)</dd></div>
                @if ($discount)
                    <div><dt>خصم الكود <span class="num">{{ $coupon->code }}</span></dt><dd style="color:var(--ok)">-@iqd($discount)</dd></div>
                @endif
                <div><dt>{{ $deliveryOn ? 'التوصيل' : 'الاستلام' }}</dt><dd data-fee-out>اختر المنطقة</dd></div>
                <div class="total"><dt>المجموع</dt><dd data-total-out>@iqd($subtotal - $discount)</dd></div>
            </dl>
            <div class="note note--info" style="background:#fff">@icon('cash') <p>الدفع نقداً عند الاستلام.</p></div>
            @if ($conflicts)
                <div @class(['note', 'note--bad', 'ackbox']) role="alert">@icon('alert')
                    <div>
                        <p><strong>في سلتك دواء يتعارض مع حساسية مسجلة:</strong></p>
                        <ul>@foreach ($conflicts as $productId => $hits)<li>{{ $items->firstWhere('product.id', $productId)->product->name }} ← {{ collect($hits)->map(fn ($h) => ($h['profile']->relation === 'self' ? 'أنت' : $h['profile']->name).': '.$h['substance'])->unique()->implode('، ') }}</li>@endforeach</ul>
                        <label class="checkline"><input type="checkbox" name="allergy_ack" value="1" @checked(old('allergy_ack'))> <span>أعلم بذلك، ليراجعه الصيدلاني ويتصل بي قبل الصرف</span></label>
                        @error('allergy_ack')<span class="err">{{ $message }}</span>@enderror
                    </div>
                </div>
            @endif
            <button class="btn btn--buy btn--block" style="margin-top:18px">تأكيد الطلب</button>
            @unless ($customer)
                <p class="line__meta" style="margin-top:10px;text-align:center"><a class="more" href="{{ route('customer.login') }}">سجّل الدخول</a> لتُملأ بياناتك تلقائياً ويُحفظ الطلب في حسابك.</p>
            @endunless
        </aside>
    </form>
</div>
@endsection
