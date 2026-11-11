@extends('layouts.shop')
@section('title', 'اطلب منتجاً غير موجود')
@section('description', 'لم تجد دواءك في '.$site['name'].'؟ اكتب اسمه أو ارفع صورته ونبحث عنه في المذاخر ونتصل بك عند توفيره.')

@section('content')
<div class="page wrap" style="max-width:820px">
    <h1 class="page__title">اطلب منتجاً غير موجود</h1>
    <p class="page__sub">اكتب اسم الدواء أو المنتج كما تعرفه. نبحث عنه في المذاخر ونتصل بك بالسعر وموعد التوفر، بلا أي التزام.</p>

    @if (session('ok'))
        <div class="note note--ok">@icon('check') <p>{{ session('ok') }}</p></div>
    @endif

    <form class="panel" style="margin-top:24px" method="post" action="{{ route('request.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="hp" aria-hidden="true"><label>اترك هذا الحقل فارغاً <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form">
            <div @class(['field', 'field--full', 'bad' => $errors->has('product_name')])>
                <label for="product_name">اسم المنتج</label>
                <input id="product_name" name="product_name" value="{{ old('product_name', $prefill) }}" placeholder="مثلاً: Januvia 100mg" required>
                @error('product_name')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field field--full">
                <label for="details">تفاصيل تساعدنا (اختياري)</label>
                <textarea id="details" name="details" placeholder="التركيز، عدد العلب، الشركة المفضلة، أو بديل تقبله">{{ old('details') }}</textarea>
            </div>
            <div @class(['field', 'field--full', 'bad' => $errors->has('image')])>
                <span style="font-weight:500">صورة العلبة أو الوصفة (اختياري)</span>
                <label class="drop">
                    @icon('upload')
                    <b>اختر صورة أو التقطها بالكاميرا</b>
                    <small>JPG أو PNG حتى 6 ميغابايت</small>
                    <input type="file" name="image" accept="image/*">
                </label>
                @error('image')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'bad' => $errors->has('name')])>
                <label for="name">اسمك</label>
                <input id="name" name="name" value="{{ old('name', $me->name ?? '') }}" autocomplete="name" required>
                @error('name')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div @class(['field', 'bad' => $errors->has('phone')])>
                <label for="phone">رقم الموبايل</label>
                <input id="phone" name="phone" type="tel" inputmode="numeric" dir="ltr" style="text-align:end" value="{{ old('phone', $me->phone ?? '') }}" placeholder="07701234567" autocomplete="tel" required>
                @error('phone')<span class="err">{{ $message }}</span>@else<span class="hint">نتصل بك عليه عند توفر المنتج</span>@enderror
            </div>
        </div>
        <button class="btn btn--buy" style="margin-top:20px">@icon('send') ارسل الطلب</button>
    </form>
</div>
@endsection
