@extends('layouts.admin')
@section('title', 'نتيجة الاستيراد')

@section('content')
<div class="head">
    <div><h1>نتيجة الاستيراد</h1><p>الملف: {{ $fileName }}</p></div>
    <div class="head__actions">
        <a class="btn btn--ghost" href="{{ route('admin.import') }}">استيراد ملف آخر</a>
        <a class="btn" href="{{ route('admin.products.index') }}">عرض المنتجات</a>
    </div>
</div>

<ol class="wizard"><li class="did">رفع الملف</li><li class="did">اختيار الحقول</li><li class="on">النتيجة</li></ol>

<div class="stats">
    <div class="stat stat--hot"><span>منتجات جديدة</span><b class="num">{{ $stats['created'] }}</b></div>
    <div class="stat"><span>منتجات حُدّثت</span><b class="num">{{ $stats['updated'] }}</b></div>
    <div class="stat"><span>تم تجاوزها</span><b class="num">{{ $stats['skipped'] }}</b></div>
    <div class="stat"><span>أسطر فيها خطأ</span><b class="num">{{ $stats['failed'] }}</b></div>
</div>

@if ($stats['categories'])
    <div class="flash flash--ok">@icon('check') <span>أُنشئ {{ $stats['categories'] }} قسم جديد من الملف. يمكنك تعديل ألوانها ورموزها من صفحة الأقسام.</span></div>
@endif

@if ($rowErrors)
<div class="card">
    <h2>أسطر لم تُستورد</h2>
    <p class="card__sub">صحّحها في الملف وأعد رفعه، المنتجات المستوردة لن تتكرر.</p>
    <ul style="margin:0;padding-inline-start:20px">
        @foreach ($rowErrors as $e)<li>{{ $e }}</li>@endforeach
    </ul>
    @if ($stats['failed'] > count($rowErrors))<p class="card__sub" style="margin:10px 0 0">وأسطر أخرى عددها {{ $stats['failed'] - count($rowErrors) }}.</p>@endif
</div>
@endif
@endsection
