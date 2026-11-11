@extends('layouts.admin')
@section('title', 'كوبونات الخصم')

@section('content')
<div class="head"><div><h1>كوبونات الخصم</h1><p>كود يكتبه الزبون في السلة فيُخصم من مجموع مواده</p></div></div>

@unless (\App\Support\Ui::on('coupons'))
    <div class="hint"><strong>خاصية كود الخصم متوقفة.</strong> شغّلها من <a href="{{ route('admin.appearance') }}#features" style="text-decoration:underline">واجهة الموقع</a> ليظهر حقل الكود في السلة.</div>
@endunless

<form class="card" method="post" action="{{ route('admin.coupons.store') }}">
    @csrf
    <h2>كود جديد</h2>
    <div class="form form--3">
        <div class="field"><label for="code">الكود</label><input id="code" type="text" name="code" dir="ltr" value="{{ old('code') }}" placeholder="DALU10" required><small>حروف إنجليزية وأرقام</small></div>
        <div class="field"><label for="type">نوع الخصم</label><select id="type" name="type"><option value="percent" @selected(old('type') !== 'fixed')>نسبة مئوية %</option><option value="fixed" @selected(old('type') === 'fixed')>مبلغ ثابت بالدينار</option></select></div>
        <div class="field"><label for="value">قيمة الخصم</label><input id="value" type="number" name="value" min="1" value="{{ old('value') }}" placeholder="10" required><small>مثلاً 10 للنسبة، أو 2000 للمبلغ</small></div>
        <div class="field"><label for="min_total">الحد الأدنى للطلب (د.ع)</label><input id="min_total" type="number" name="min_total" min="0" step="1000" value="{{ old('min_total', 0) }}"><small>0 يعني بلا حد</small></div>
        <div class="field"><label for="max_uses">عدد مرات الاستعمال</label><input id="max_uses" type="number" name="max_uses" min="1" value="{{ old('max_uses') }}"><small>اتركه فارغاً لعدد غير محدود</small></div>
        <div class="field"><label for="expires_at">ينتهي في</label><input id="expires_at" type="date" name="expires_at" value="{{ old('expires_at') }}"><small>اتركه فارغاً ليبقى بلا تاريخ</small></div>
    </div>
    <button class="btn" style="margin-top:16px">أضف الكود</button>
</form>

<div class="card">
    <h2>الأكواد</h2>
    <div class="tablewrap">
        <table>
            <thead><tr><th>الكود</th><th>الخصم</th><th>الشروط</th><th>الاستعمال</th><th>مبيعات بالكود</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
            @forelse ($coupons as $c)
                @php $stat = $saved[$c->code] ?? null; $dead = $c->rejection(PHP_INT_MAX); @endphp
                <tr>
                    <td><b class="num">{{ $c->code }}</b></td>
                    <td>{{ $c->label }}</td>
                    <td><span class="t-sub">{{ $c->min_total ? 'للطلبات من '.number_format($c->min_total).' د.ع' : 'بلا حد أدنى' }}</span><span class="t-sub">{{ $c->expires_at ? 'ينتهي '.$c->expires_at->format('Y-m-d') : 'بلا تاريخ انتهاء' }}</span></td>
                    <td class="num">{{ $c->used }}{{ $c->max_uses ? ' / '.$c->max_uses : '' }}</td>
                    <td>@if ($stat) @iqd($stat->sales)<span class="t-sub">خصم ممنوح {{ number_format($stat->discount) }} د.ع</span> @else <span class="t-sub">لم يُستعمل بعد</span> @endif</td>
                    <td><span @class(['pill', 'pill--ok' => ! $dead, 'pill--bad' => (bool) $dead])>{{ $dead ?: 'يعمل' }}</span></td>
                    <td>
                        <div class="t-actions">
                            <form method="post" action="{{ route('admin.coupons.toggle', $c) }}">@csrf @method('put')<button class="btn btn--sm btn--ghost">{{ $c->is_active ? 'إيقاف' : 'تشغيل' }}</button></form>
                            <form method="post" action="{{ route('admin.coupons.destroy', $c) }}" onsubmit="return confirm('حذف الكود {{ $c->code }}؟')">@csrf @method('delete')<button class="btn btn--sm btn--danger">حذف</button></form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">لا توجد أكواد. أضف أول كود من الأعلى وانشره في صفحاتكم.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
