@extends('layouts.shop')
@section('title', 'الأسئلة الشائعة')
@section('description', 'إجابات عن الطلب والتوصيل والدفع والوصفات الطبية في '.$site['name'])

@section('content')
<div class="page wrap" style="max-width:880px">
    <h1 class="page__title">الأسئلة الشائعة</h1>
    <p class="page__sub">الطلب، التوصيل، الدفع، والوصفات الطبية.</p>

    <div class="faq" style="margin-top:24px">
        @forelse ($faqs as $i => $f)
            <details @if ($i === 0) open @endif><summary>{{ $f->question }}</summary><p>{{ $f->answer }}</p></details>
        @empty
            <div class="empty">@icon('chat')<h2>لا توجد أسئلة منشورة بعد</h2><p>راسلنا وسنجيبك مباشرة.</p></div>
        @endforelse
    </div>

    <div class="faq-cta">
        <div><b>لم تجد جواب سؤالك؟</b><br><span style="color:var(--ink-soft)">اسأل {{ $site['pharmacist_label'] }} مباشرة أو اترك لنا رسالة.</span></div>
        <div>
            <a class="btn btn--wa" href="{{ $consultUrl }}" target="_blank" rel="noopener">@icon('whatsapp') استشر {{ $site['pharmacist_label'] }}</a>
            <a class="btn btn--ghost" href="{{ route('contact') }}">تواصل معنا</a>
        </div>
    </div>
</div>
@endsection
