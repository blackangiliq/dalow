@extends('layouts.shop')
@section('title', 'اطلب بوصفة طبية')

@section('content')
<div class="page wrap" style="max-width:820px">
    <h1 class="page__title">اطلب بوصفة طبية</h1>
    <p class="page__sub">ارفع صورة واضحة للوصفة. الصيدلاني يراجعها ويتصل بك بالسعر والبدائل المتوفرة قبل التوصيل.</p>

    <form class="panel" style="margin-top:24px" method="post" action="{{ route('prescription.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="form">
            <div @class(['field', 'field--full', 'bad' => $errors->has('prescription')])>
                <span style="font-weight:500">صورة الوصفة</span>
                <label class="drop">
                    @icon('upload')
                    <b>اختر صورة الوصفة أو التقطها بالكاميرا</b>
                    <small>JPG أو PNG حتى 6 ميغابايت. تأكد أن اسم الدواء والجرعة مقروءان.</small>
                    <input type="file" name="prescription" accept="image/*" required>
                </label>
                @error('prescription')<span class="err">{{ $message }}</span>@enderror
            </div>
            @include('shop._customer')
        </div>
        <button class="btn" style="margin-top:20px">@icon('send') ارسل الوصفة</button>
    </form>
</div>
@endsection
