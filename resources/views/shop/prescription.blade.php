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
            @if ($profiles->count() > 1 && $on['family_profiles'])
            <div class="field field--full">
                <label for="health_profile_id">لمن هذه الوصفة؟</label>
                <select id="health_profile_id" name="health_profile_id">
                    @foreach ($profiles as $p)<option value="{{ $p->id }}">{{ $p->relation === 'self' ? 'لي أنا' : $p->name }}</option>@endforeach
                </select>
                <span class="hint">يرى الصيدلاني حساسيته وأمراضه مع الوصفة</span>
            </div>
            @endif
            @include('shop._customer')
        </div>
        <button class="btn" style="margin-top:20px">@icon('send') ارسل الوصفة</button>
    </form>
</div>
@endsection
