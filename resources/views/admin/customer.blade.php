@extends('layouts.admin')
@section('title', $customer->name)

@section('content')
<div class="head">
    <div>
        <h1>{{ $customer->name }}</h1>
        <p><a class="num" href="tel:{{ $customer->phone }}" style="color:var(--link);font-weight:700">{{ $customer->phone }}</a>
            @if ($customer->phone_verified_at) · <span class="pill pill--ok">رقم مؤكد</span>@elseif (\App\Support\Ui::on('phone_verify')) · <span class="pill pill--new">رقم غير مؤكد</span>@endif
            · {{ $customer->area ?: 'المنطقة غير محددة' }} · سجّل {{ $customer->created_at->diffForHumans() }}</p>
    </div>
    <div class="head__actions">
        <a class="btn" target="_blank" rel="noopener" href="{{ \App\Support\Util::wa($customer->phone, 'مرحباً '.$customer->name.'، معك صيدلية الدلو.') }}">@icon('whatsapp') واتساب</a>
        <a class="btn btn--ghost" href="tel:{{ $customer->phone }}">@icon('phone') اتصال</a>
        <a class="btn btn--ghost" href="{{ route('admin.customers.index') }}">كل الزبائن</a>
    </div>
</div>

<div class="stats">
    <div class="stat"><span>الطلبات</span><b class="num">{{ $stats['orders'] }}</b><small>{{ $stats['last'] ? 'آخرها '.$stats['last']->diffForHumans() : 'لم يطلب بعد' }}</small></div>
    <div class="stat"><span>مجموع مشترياته المسلّمة</span><b>{{ number_format($stats['spent']) }}</b><small>دينار عراقي</small></div>
    <div class="stat"><span>ينتظر انخفاض سعر</span><b class="num">{{ $customer->favorites->count() }}</b><small>منتج</small></div>
    <div class="stat"><span>تذكيرات الدواء</span><b class="num">{{ $customer->refillReminders->where('is_active', true)->count() }}</b><small>دواء يأخذه باستمرار</small></div>
</div>

<div class="cols">
    <div>
        @if (\App\Support\Ui::on('health_profile'))
        <div class="card">
            <div class="card__head"><h2>الملف الصحي</h2><span class="t-sub">{{ $customer->health_consent_at ? 'وافق على حفظ معلوماته الصحية '.$customer->health_consent_at->format('Y-m-d') : 'لم يوافق بعد على حفظ معلومات صحية' }}</span></div>
            @forelse ($customer->healthProfiles as $profile)
                @include('admin.partials.patient', ['profile' => $profile])
            @empty
                <p class="empty">لم يعبئ ملفه الصحي بعد.</p>
            @endforelse
        </div>
        @endif

        <div class="card">
            <h2>طلباته السابقة</h2>
            <div class="tablewrap">
                <table>
                    <thead><tr><th>الرقم</th><th>التاريخ</th><th>لمن</th><th>المواد</th><th>المجموع</th><th>الحالة</th></tr></thead>
                    <tbody>
                    @forelse ($orders as $o)
                        <tr>
                            <td><a class="num" style="font-weight:800;color:var(--link)" href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td>
                            <td><span class="num">{{ $o->created_at->format('Y-m-d') }}</span></td>
                            <td>{{ $o->healthProfile ? ($o->healthProfile->relation === 'self' ? 'له' : $o->healthProfile->name) : '—' }}</td>
                            <td>{{ $o->type === 'prescription' ? 'وصفة طبية' : $o->items->map(fn ($i) => $i->name.' ×'.$i->qty)->implode('، ') }}@if ($o->allergy_ack)<span class="t-sub" style="color:var(--coral)">⚠ تعارض حساسية: {{ $o->allergy_ack }}</span>@endif</td>
                            <td style="white-space:nowrap">@iqd($o->total)</td>
                            <td><span class="pill pill--{{ $o->status }}">{{ $o->status_label }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">لم يطلب من حسابه بعد.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div>
        <form class="card" method="post" action="{{ route('admin.customers.note', $customer) }}">
            @csrf @method('put')
            <h2>ملاحظة داخلية</h2>
            <p class="card__sub">لا يراها الزبون. مثلاً: يفضّل الاتصال مساءً، يحتاج تذكيراً بالجرعة.</p>
            <textarea name="pharmacist_note" rows="4" aria-label="ملاحظة داخلية">{{ old('pharmacist_note', $customer->pharmacist_note) }}</textarea>
            <button class="btn btn--sm" style="margin-top:10px">حفظ الملاحظة</button>
        </form>

        <div class="card">
            <h2>أدوية يكررها</h2>
            <ul class="statuslist">
                @forelse ($repeat as $r)
                    <li><a href="{{ route('admin.products.edit', $r->product_id) }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $r->name }}</span><b class="num">{{ $r->times }} مرة</b></a></li>
                @empty
                    <li class="empty">لا توجد مشتريات بعد.</li>
                @endforelse
            </ul>
        </div>

        @if ($customer->refillReminders->count())
        <div class="card">
            <h2>تذكيرات الدواء</h2>
            <ul class="statuslist">
                @foreach ($customer->refillReminders as $r)
                    <li><a><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $r->product->name }}</span><span class="pill">{{ $r->interval_label }} · {{ $r->next_at->format('j/n') }}</span></a></li>
                @endforeach
            </ul>
        </div>
        @endif

        @if ($customer->favorites->count())
        <div class="card">
            <h2>ينتظر انخفاض سعرها</h2>
            <ul class="statuslist">
                @foreach ($customer->favorites as $f)<li><a href="{{ route('admin.products.edit', $f) }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $f->name }}</span><span class="pill">@iqd($f->price)</span></a></li>@endforeach
            </ul>
        </div>
        @endif

        @if ($requests->count() || $messages->count())
        <div class="card">
            <h2>طلبات التوفير والرسائل</h2>
            <ul class="statuslist">
                @foreach ($requests as $r)<li><a href="{{ route('admin.requests.index') }}"><span>طلب توفير: {{ $r->product_name }}</span><span class="pill">{{ $r->status_label }}</span></a></li>@endforeach
                @foreach ($messages as $m)<li><a href="{{ route('admin.messages.index') }}"><span style="min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">رسالة: {{ $m->subject ?: \Illuminate\Support\Str::limit($m->body, 40) }}</span><span class="t-sub">{{ $m->created_at->diffForHumans() }}</span></a></li>@endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
