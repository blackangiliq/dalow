@extends('layouts.admin')
@section('title', 'الأسئلة الشائعة')

@section('content')
<div class="head">
    <div><h1>الأسئلة الشائعة</h1><p>تظهر في صفحة الأسئلة، وأول أربعة منها في الصفحة الرئيسية</p></div>
    <a class="btn btn--ghost" href="{{ route('faq') }}" target="_blank" rel="noopener">عرض الصفحة في المتجر</a>
</div>

<form class="card" method="post" action="{{ route('admin.faqs.store') }}">
    @csrf
    <h2>سؤال جديد</h2>
    <div class="form">
        <div class="field field--full"><label for="question">السؤال</label><input id="question" type="text" name="question" value="{{ old('question') }}" required></div>
        <div class="field field--full"><label for="answer">الجواب</label><textarea id="answer" name="answer" required>{{ old('answer') }}</textarea></div>
    </div>
    <input type="hidden" name="is_active" value="1">
    <input type="hidden" name="sort" value="{{ $faqs->count() + 1 }}">
    <button class="btn" style="margin-top:14px">أضف السؤال</button>
</form>

@forelse ($faqs as $f)
    <details class="fold" style="margin-top:12px">
        <summary><span>{{ $f->question }} @if (! $f->is_active)<span class="pill">مخفي</span>@endif</span></summary>
        <div>
            <form method="post" action="{{ route('admin.faqs.update', $f) }}" style="margin-top:14px">
                @csrf @method('put')
                <div class="form">
                    <div class="field field--full"><label for="q{{ $f->id }}">السؤال</label><input id="q{{ $f->id }}" type="text" name="question" value="{{ $f->question }}" required></div>
                    <div class="field field--full"><label for="a{{ $f->id }}">الجواب</label><textarea id="a{{ $f->id }}" name="answer" required>{{ $f->answer }}</textarea></div>
                    <div class="field"><label for="s{{ $f->id }}">الترتيب</label><input id="s{{ $f->id }}" type="number" name="sort" value="{{ $f->sort }}" min="0" style="width:100px"></div>
                    <div class="field"><span>الظهور</span><label class="check"><input type="checkbox" name="is_active" value="1" @checked($f->is_active)> ظاهر في المتجر</label></div>
                </div>
                <button class="btn btn--sm" style="margin-top:14px">حفظ</button>
            </form>
            <form method="post" action="{{ route('admin.faqs.destroy', $f) }}" style="margin-top:8px" onsubmit="return confirm('حذف هذا السؤال؟')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف السؤال</button></form>
        </div>
    </details>
@empty
    <div class="card"><p class="empty">لا توجد أسئلة. أضف أول سؤال من الأعلى.</p></div>
@endforelse
@endsection
