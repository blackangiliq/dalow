@extends('layouts.admin')
@section('title', 'استيراد من Excel')

@section('content')
<div class="head">
    <div><h1>استيراد قائمة الأدوية من Excel</h1><p>ارفع قائمتك كما هي، ثم اختر أي عمود يقابل أي حقل</p></div>
    <a class="btn btn--ghost" href="{{ route('admin.import.template') }}">@icon('sheet') تنزيل ملف نموذجي</a>
</div>

<ol class="wizard"><li class="on">رفع الملف</li><li>اختيار الحقول</li><li>النتيجة</li></ol>

<form class="card" method="post" action="{{ route('admin.import.upload') }}" enctype="multipart/form-data">
    @csrf
    <div class="dropzone">
        @icon('upload')
        <h2 style="margin:0">اختر ملف القائمة</h2>
        <p style="color:var(--ink-soft)">xlsx أو xls أو csv، حتى 10 ميغابايت. تُقرأ الورقة الأولى من الملف.</p>
        <label for="file" style="position:absolute;clip-path:inset(50%)">ملف القائمة</label>
        <input id="file" type="file" name="file" accept=".xlsx,.xls,.csv" required>
    </div>
    <button class="btn" style="margin-top:18px">رفع ومتابعة</button>
</form>

<div class="card">
    <h2>قبل الرفع</h2>
    <ul style="margin:0;padding-inline-start:20px;color:var(--ink-soft)">
        <li>لا يشترط ترتيب معين للأعمدة ولا أسماء محددة لها، ستختار ذلك في الخطوة التالية.</li>
        <li>المطلوب فقط عمودان: اسم المنتج والسعر. باقي الحقول اختيارية.</li>
        <li>المنتج الموجود مسبقاً (بنفس الباركود أو الاسم) تُحدَّث بياناته بدل تكراره.</li>
    </ul>
</div>
@endsection
