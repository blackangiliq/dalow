@extends('layouts.admin')
@section('title', 'العناية بالزبائن')

@section('content')
<div class="head">
    <div><h1>العناية بالزبائن</h1><p>ما ينتظر منك اليوم: تحاليل للقراءة، أدوية حان موعدها، وزبائن تسأل عن حالهم</p></div>
    <a class="btn btn--ghost" href="{{ route('admin.appearance') }}#features">@icon('gear') تشغيل وإيقاف الخدمات</a>
</div>

@if (\App\Support\Ui::on('health_documents'))
<div class="card">
    <div class="card__head"><h2>تحاليل وفحوصات بانتظار قراءتك</h2><span class="pill @if ($documents->count()) pill--new @else pill--ok @endif">{{ $documents->count() ?: 'لا شيء' }}</span></div>
    @forelse ($documents->groupBy(fn ($d) => $d->profile->id) as $docs)
        @php $profile = $docs->first()->profile->load(['allergies', 'documents']); @endphp
        <div class="carebox">
            <div class="carebox__who">
                <a href="{{ route('admin.customers.show', $profile->customer) }}"><b>{{ $profile->customer->name }}</b></a>
                <span class="t-sub num">{{ $profile->customer->phone }}</span>
            </div>
            @include('admin.partials.patient', ['profile' => $profile])
        </div>
    @empty
        <p class="empty">قرأت كل ما أُرسل. عندما يرفع زبون فحصاً يظهر هنا ويصلك إشعار على تيليجرام.</p>
    @endforelse
</div>
@endif

@if (\App\Support\Ui::on('refill_reminders'))
<div class="grid2" style="margin-top:18px">
    <div class="card">
        <h2>أدوية يحين موعدها خلال 3 أيام</h2>
        <p class="card__sub">جهّزها، أو ذكّر الزبون بنفسك إن لم يكن على تيليجرام.</p>
        @forelse ($upcoming as $r)
            <div class="person" style="margin-top:8px">
                <b>{{ $r->product->name }}</b>
                <span class="t-sub"><a href="{{ route('admin.customers.show', $r->customer) }}" style="color:var(--link)">{{ $r->customer->name }}</a> · <span class="num">{{ $r->customer->phone }}</span> · {{ $r->interval_label }} · {{ $r->next_at->isToday() ? 'اليوم' : $r->next_at->translatedFormat('l j/n') }}</span>
                <div class="inline">
                    <a class="btn btn--sm" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($r->customer->phone, 'مرحباً '.$r->customer->name.'، معك صيدلية الدلو. حان موعد «'.$r->product->name.'». هل نجهزه لك؟') }}">@icon('whatsapp') ذكّره</a>
                    <a class="btn btn--sm btn--ghost" href="tel:{{ $r->customer->phone }}">@icon('phone') اتصال</a>
                </div>
            </div>
        @empty
            <p class="empty">لا توجد تذكيرات قريبة.</p>
        @endforelse
    </div>
    <div class="card">
        <h2>ذُكّروا هذا الأسبوع</h2>
        <p class="card__sub">وصلهم تذكير في حساباتهم (وعلى رقمهم لمن وافق). تابع من لم يطلب.</p>
        <ul class="statuslist">
            @forelse ($reminded as $r)
                <li><a href="{{ route('admin.customers.show', $r->customer) }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $r->customer->name }}: {{ $r->product->name }}</span><span class="t-sub">{{ $r->last_reminded_at->diffForHumans() }}</span></a></li>
            @empty
                <li class="empty">لم يُرسل تذكير هذا الأسبوع.</li>
            @endforelse
        </ul>
    </div>
</div>
@endif

@if (\App\Support\Ui::on('followup'))
<div class="card" style="margin-top:18px">
    <h2>اسأل عن حالهم</h2>
    <p class="card__sub">طلبات سُلّمت قبل يومين إلى أسبوع. سؤال قصير بعد الدواء يبني ثقة الزبون ويكشف أي عرض جانبي مبكراً.</p>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الطلب</th><th>الزبون</th><th>سُلّم</th><th>المواد</th><th></th></tr></thead>
            <tbody>
            @forelse ($followups as $o)
                <tr>
                    <td><a class="num" style="font-weight:800;color:var(--link)" href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td>
                    <td>{{ $o->customer_name }}<span class="t-sub num">{{ $o->phone }}</span></td>
                    <td><span class="t-sub">{{ $o->updated_at->diffForHumans() }}</span></td>
                    <td>{{ $o->type === 'prescription' ? 'وصفة طبية' : $o->items()->pluck('name')->implode('، ') }}</td>
                    <td>
                        <div class="t-actions">
                            <a class="btn btn--sm" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($o->phone, 'مرحباً '.$o->customer_name.'، معك صيدلية الدلو. نطمئن عليك بعد طلبك رقم '.$o->number.': هل تحسنت؟ وإن كان لديك أي سؤال عن الدواء نحن هنا.') }}">@icon('whatsapp') اسأل عن حاله</a>
                            <form method="post" action="{{ route('admin.care.followup', $o) }}">@csrf @method('put')<button class="btn btn--sm btn--ghost">تمت المتابعة</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">لا يوجد من يحتاج متابعة الآن.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@unless (\App\Support\Ui::on('health_documents') || \App\Support\Ui::on('refill_reminders') || \App\Support\Ui::on('followup'))
    <div class="card"><p class="empty">كل خدمات العناية متوقفة. شغّلها من <a href="{{ route('admin.appearance') }}#features" style="text-decoration:underline">واجهة الموقع</a>.</p></div>
@endunless
@endsection
