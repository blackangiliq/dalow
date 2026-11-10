@extends('layouts.admin')
@section('title', 'الرسائل')

@section('content')
<div class="head"><div><h1>رسائل الزبائن</h1><p>الرسائل الواردة من صفحة «تواصل معنا»</p></div></div>

@forelse ($messages as $m)
    <article @class(['msg', 'msg--new' => ! $m->is_read])>
        <div class="msg__head">
            <b>{{ $m->name }}</b>
            <a class="num" href="tel:{{ $m->phone }}" style="color:var(--water-deep)">{{ $m->phone }}</a>
            @if ($m->subject)<span class="pill">{{ $m->subject }}</span>@endif
            @if (! $m->is_read)<span class="pill pill--new">جديدة</span>@endif
            <span class="t-sub" style="margin-inline-start:auto">{{ $m->created_at->diffForHumans() }}</span>
        </div>
        <p>{{ $m->body }}</p>
        <div class="inline">
            <a class="btn btn--sm" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($m->phone, 'مرحباً '.$m->name.'، معك صيدلية الدلو بخصوص رسالتك في الموقع.') }}">@icon('whatsapp') رد على واتساب</a>
            <a class="btn btn--sm btn--ghost" href="tel:{{ $m->phone }}">@icon('phone') اتصال</a>
            <form method="post" action="{{ route('admin.messages.toggle', $m) }}">@csrf @method('put')<button class="btn btn--sm btn--ghost">{{ $m->is_read ? 'علّمها غير مقروءة' : 'علّمها مقروءة' }}</button></form>
            <form method="post" action="{{ route('admin.messages.destroy', $m) }}" onsubmit="return confirm('حذف الرسالة؟')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف</button></form>
        </div>
    </article>
@empty
    <div class="card"><p class="empty">لا توجد رسائل بعد. تظهر هنا كل رسالة يرسلها زبون من صفحة «تواصل معنا».</p></div>
@endforelse
{{ $messages->links() }}
@endsection
