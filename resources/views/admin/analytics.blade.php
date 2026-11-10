@extends('layouts.admin')
@section('title', 'الإحصاءات والزوار')

@section('content')
@php
    $maxV = max(1, $series->max('visitors'));
    $step = $days <= 7 ? 1 : ($days <= 30 ? 5 : 15);
    $deviceNames = ['mobile' => 'موبايل', 'tablet' => 'تابلت', 'desktop' => 'حاسوب'];
    $deviceTotal = max(1, $devices->sum());
@endphp
<div class="head">
    <div><h1>الإحصاءات والزوار</h1><p>من يزور الموقع، عمّ يبحث، وما الذي يلفت انتباهه</p></div>
    <div class="seg" role="group" aria-label="الفترة">
        @foreach ([7 => 'أسبوع', 30 => '30 يوماً', 90 => '90 يوماً'] as $d => $label)
            <a href="{{ route('admin.analytics', ['days' => $d]) }}" @class(['on' => $days === $d])>{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="stats stats--5">
    <div class="stat stat--hot"><span>زوار اليوم</span><b class="num">{{ number_format($today['visitors']) }}</b><small>{{ number_format($today['views']) }} صفحة شوهدت</small></div>
    <div class="stat"><span>زوار آخر {{ $days }} يوماً</span><b class="num">{{ number_format($total['visitors']) }}</b><small>أشخاص مختلفون</small></div>
    <div class="stat"><span>الزيارات (صفحات)</span><b class="num">{{ number_format($total['views']) }}</b><small>في الفترة نفسها</small></div>
    <div class="stat"><span>الطلبات</span><b class="num">{{ number_format($total['orders']) }}</b><small>في الفترة نفسها</small></div>
    <div class="stat"><span>نسبة الشراء</span><b class="num">{{ $total['rate'] }}%</b><small>طلب لكل 100 زائر</small></div>
</div>

<div class="cols">
    <div class="card">
        <div class="card__head"><h2>الزوار يومياً</h2><span class="t-sub">عدد الأشخاص المختلفين كل يوم</span></div>
        <div class="chart chart--30" style="--n: {{ $days }}" role="img" aria-label="عدد الزوار لكل يوم في آخر {{ $days }} يوماً">
            @foreach ($series as $d)
                @php $h = round($d['visitors'] / $maxV * 100); @endphp
                <div @class(['chart__col', 'chart__col--zero' => ! $d['visitors']]) style="--h: {{ $h }}%" tabindex="0">
                    @if ($d['visitors'] && $d['visitors'] === $series->max('visitors'))<span class="chart__val num">{{ $d['visitors'] }}</span>@endif
                    <span class="chart__bar" style="height: {{ $h }}%"></span>
                    <span class="chart__tip">{{ $d['date']->translatedFormat('l j/n') }}: <span class="num">{{ $d['visitors'] }}</span> زائر، <span class="num">{{ $d['views'] }}</span> صفحة</span>
                </div>
            @endforeach
        </div>
        <div class="chart__axis chart__axis--30" style="--n: {{ $days }}" aria-hidden="true">
            @foreach ($series as $i => $d)<span @class(['num', 'show' => ($days - 1 - $i) % $step === 0])>{{ $d['date']->format('j/n') }}</span>@endforeach
        </div>
    </div>

    <div class="card">
        <h2>الأشهر الستة الأخيرة</h2>
        <div class="tablewrap">
            <table>
                <thead><tr><th>الشهر</th><th>الزوار</th><th>الزيارات</th><th>الطلبات</th></tr></thead>
                <tbody>
                @foreach ($months->reverse() as $m)
                    <tr><td>{{ $m['label'] }}</td><td class="num">{{ number_format($m['visitors']) }}</td><td class="num">{{ number_format($m['views']) }}</td><td class="num">{{ $m['orders'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid2" style="margin-top:18px">
    <div class="card">
        <h2>عمّ يبحث الزبائن</h2>
        @if ($topSearches->count())
            @php $m = max(1, $topSearches->max('c')); @endphp
            <ol class="bars">
                @foreach ($topSearches as $s)
                    <li><span>{{ $s->term }} @if (! $s->results)<span class="pill pill--bad">بلا نتائج</span>@endif</span><b class="num">{{ $s->c }} <small>مرة</small></b><i style="--w: {{ round($s->c / $m * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">تظهر هنا كلمات البحث بعد أول عمليات بحث في المتجر.</p>
        @endif
    </div>

    <div class="card">
        <h2>بحثوا عنه ولم يجدوه</h2>
        <p class="card__sub">فرص بيع ضائعة: وفّر هذه المنتجات، أو أضف مرادفاً يوصل الكلمة لمنتج موجود.</p>
        @if ($failedSearches->count())
            @php $m = max(1, $failedSearches->max('c')); @endphp
            <ol class="bars bars--warn">
                @foreach ($failedSearches as $s)
                    <li><span>{{ $s->term }}</span><b class="num">{{ $s->c }} <small>مرة</small></b><i style="--w: {{ round($s->c / $m * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">كل ما بحث عنه الزبائن وجدوه.</p>
        @endif
        @if ($requests->count())
            <h2 style="margin-top:22px">طلبوا توفيره صراحة</h2>
            <ul class="statuslist">
                @foreach ($requests as $r)<li><a href="{{ route('admin.requests.index') }}"><span>{{ $r->product_name }}</span><b class="num">{{ $r->c }}</b></a></li>@endforeach
            </ul>
        @endif
    </div>
</div>

<div class="grid2" style="margin-top:18px">
    <div class="card">
        <h2>ما الذي يلفت انتباه الزائر</h2>
        <p class="card__sub">متوسط الوقت الذي يبقى فيه كل قسم ظاهراً أمام الزائر.</p>
        @if ($dwell->count())
            @php $m = max(1, $dwell->max('seconds')); @endphp
            <ol class="bars">
                @foreach ($dwell as $d)
                    <li><span>{{ $d['name'] }}</span><b class="num">{{ $d['seconds'] }} <small>ثانية</small></b><i style="--w: {{ round($d['seconds'] / $m * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">يحتاج هذا القياس إلى زيارات حقيقية للمتجر.</p>
        @endif
    </div>

    <div class="card">
        <h2>أكثر ما يُنقر عليه</h2>
        <p class="card__sub">الأزرار والعناصر مرتبة بعدد النقرات.</p>
        @if ($clicks->count())
            @php $m = max(1, $clicks->max('c')); @endphp
            <ol class="bars">
                @foreach ($clicks as $name => $c)
                    <li><span>{{ $name }}</span><b class="num">{{ $c['c'] }} <small>نقرة</small></b><i style="--w: {{ round($c['c'] / $m * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">تظهر النقرات بعد أول زيارات للمتجر.</p>
        @endif
    </div>
</div>

<div class="grid2" style="margin-top:18px">
    <div class="card">
        <h2>أكثر الأقسام زيارة</h2>
        @if ($topCategories->count())
            @php $m = max(1, $topCategories->max('views')); @endphp
            <ol class="bars">
                @foreach ($topCategories as $c)
                    <li><span>{{ $c['name'] }}</span><b class="num">{{ $c['views'] }} <small>زيارة من {{ $c['visitors'] }} زائر</small></b><i style="--w: {{ round($c['views'] / $m * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">لا توجد زيارات للأقسام في هذه الفترة.</p>
        @endif
    </div>

    <div class="card">
        <h2>أكثر المنتجات مشاهدة</h2>
        <div class="tablewrap">
            <table>
                <thead><tr><th>المنتج</th><th>المشاهدات</th><th>بيع منه</th><th>السعر</th></tr></thead>
                <tbody>
                @forelse ($topProducts as $t)
                    <tr>
                        <td><a href="{{ route('admin.products.edit', $t['product']) }}">{{ $t['product']->name }}</a>@if ($t['views'] >= 5 && ! $t['sold'])<span class="t-sub">يُشاهَد ولا يُشترى: راجع سعره أو صورته</span>@endif</td>
                        <td class="num">{{ $t['views'] }}</td>
                        <td class="num">{{ $t['sold'] }}</td>
                        <td style="white-space:nowrap">@iqd($t['product']->price)</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">لا توجد مشاهدات في هذه الفترة.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid3">
    <div class="card">
        <div class="card__head"><h2>الأكثر طلباً للخصم</h2><a href="{{ route('admin.price-alerts') }}">القائمة والأرقام</a></div>
        <p class="card__sub">منتجات ينتظر الزبائن انخفاض سعرها.</p>
        @if ($wanted->count())
            @php $m = max(1, $wanted->max('favored_by_count')); @endphp
            <ol class="bars">
                @foreach ($wanted as $p)
                    <li><span>{{ $p->name }} @if ($p->discount)<span class="pill pill--ok">عليه خصم</span>@endif</span><b class="num">{{ $p->favored_by_count }} <small>زبون</small></b><i style="--w: {{ round($p->favored_by_count / $m * 100) }}%"></i></li>
                @endforeach
            </ol>
        @else
            <p class="empty">لم يطلب أي زبون تنبيهاً بعد.</p>
        @endif
        <p class="kpi-note">الزبائن المسجلون: <b class="num">{{ $customers }}</b></p>
    </div>

    <div class="card">
        <h2>من أين يأتي الزوار</h2>
        @if ($referrers->count())
            <ul class="statuslist">
                @foreach ($referrers as $r)<li><a><span dir="ltr">{{ $r->referrer }}</span><b class="num">{{ $r->c }}</b></a></li>@endforeach
            </ul>
        @else
            <p class="empty">أغلب الزوار دخلوا مباشرة أو من رابط بلا مصدر.</p>
        @endif
        <h2 style="margin-top:22px">الأجهزة</h2>
        <ol class="bars">
            @foreach ($deviceNames as $key => $name)
                <li><span>{{ $name }}</span><b class="num">{{ round(($devices[$key] ?? 0) * 100 / $deviceTotal) }}%</b><i style="--w: {{ round(($devices[$key] ?? 0) * 100 / $deviceTotal) }}%"></i></li>
            @endforeach
        </ol>
    </div>

    <div class="card">
        <h2>مرادفات البحث</h2>
        <p class="card__sub">كلمة يكتبها الزبون تُفهم على أنها كلمات أخرى. مثال: صداع ← paracetamol, ibuprofen</p>
        <form method="post" action="{{ route('admin.synonyms.store') }}" class="inline" style="margin-bottom:14px">
            @csrf
            <input type="text" name="term" placeholder="كلمة الزبون" aria-label="كلمة الزبون" required style="flex:1;min-width:110px">
            <input type="text" name="expands_to" placeholder="تُفهم على أنها" aria-label="تُفهم على أنها" dir="auto" required style="flex:1.4;min-width:140px">
            <button class="btn btn--sm">أضف</button>
        </form>
        <ul class="statuslist" style="max-height:300px;overflow-y:auto">
            @forelse ($synonyms as $syn)
                <li><a style="gap:10px"><span style="min-width:0"><b>{{ $syn->term }}</b><span class="t-sub" dir="auto">{{ $syn->expands_to }}</span></span>
                    <form method="post" action="{{ route('admin.synonyms.destroy', $syn) }}">@csrf @method('delete')<button class="btn btn--sm btn--danger" aria-label="حذف مرادف {{ $syn->term }}">حذف</button></form></a></li>
            @empty
                <li class="empty">لا توجد مرادفات.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
