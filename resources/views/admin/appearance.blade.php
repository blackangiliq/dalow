@extends('layouts.admin')
@section('title', 'واجهة الموقع')

@section('content')
<div class="head">
    <div><h1>واجهة الموقع</h1><p>كل ما يراه الزبون: الأقسام وترتيبها، النصوص، الخصائص، والألوان</p></div>
    <a class="btn btn--ghost" href="{{ route('home') }}" target="_blank" rel="noopener">@icon('cart') افتح المتجر</a>
</div>

<nav class="jump" aria-label="أقسام الصفحة">
    <a href="#sections">أقسام الرئيسية</a><a href="#features">الخصائص</a><a href="#texts">النصوص</a><a href="#colors">الألوان</a>
    <a href="{{ route('admin.settings') }}#delivery">التوصيل</a><a href="{{ route('admin.categories.index') }}">الأقسام</a><a href="{{ route('admin.faqs.index') }}">الأسئلة</a>
</nav>

<form method="post" action="{{ route('admin.appearance.update') }}">
    @csrf @method('put')

    <div class="card" id="sections">
        <h2>أقسام الصفحة الرئيسية</h2>
        <p class="card__sub">اختر ما يظهر، ورتّبه بالأرقام (الأصغر أولاً). الواجهة والبحث ثابتان في الأعلى.</p>
        <div class="toggles toggles--order">
            @foreach ($sections as $i => $s)
                <div class="toggle">
                    <label class="switch"><input type="checkbox" name="section_on[{{ $s['key'] }}]" value="1" @checked($s['on'] ?? true)><span></span></label>
                    <b>{{ $sectionNames[$s['key']] }}</b>
                    <label class="sr-only" for="so_{{ $s['key'] }}">ترتيب {{ $sectionNames[$s['key']] }}</label>
                    <input id="so_{{ $s['key'] }}" type="number" name="section_order[{{ $s['key'] }}]" value="{{ $i + 1 }}" min="0" max="99">
                </div>
            @endforeach
        </div>
    </div>

    <div class="card" id="features">
        <h2>تشغيل وإيقاف الخصائص</h2>
        <p class="card__sub">ما توقفه يختفي من المتجر كله، وتُغلق صفحته.</p>
        @foreach ($features as $group => $items)
            <h3 class="subhead">{{ $group }}</h3>
            <div class="toggles">
                @foreach ($items as $key => [$label, $hint])
                    <label class="toggle">
                        <span class="switch"><input type="checkbox" name="on[{{ $key }}]" value="1" @checked(\App\Support\Ui::on($key))><span></span></span>
                        <span><b>{{ $label }}</b>@if ($hint)<small>{{ $hint }}</small>@endif</span>
                    </label>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="card" id="texts">
        <h2>نصوص الواجهة</h2>
        <p class="card__sub">امسح الحقل ليرجع النص الافتراضي. اسم الصيدلية وعنوانها وهاتفها من «الإعدادات».</p>
        @foreach ($groups as $group => $fields)
            <details class="fold" @if ($loop->first) open @endif style="margin-top:10px">
                <summary>{{ $group }}</summary>
                <div>
                    <div class="form" style="margin-top:14px">
                        @foreach ($fields as $key => $def)
                            <div @class(['field', 'field--full' => $def[2] ?? false])>
                                <label for="t_{{ $key }}">{{ $def[0] }}</label>
                                @if ($def[2] ?? false)
                                    <textarea id="t_{{ $key }}" name="text[{{ $key }}]" rows="2" placeholder="{{ $def[1] }}">{{ old("text.$key", $texts[$key]) }}</textarea>
                                @else
                                    <input id="t_{{ $key }}" type="text" name="text[{{ $key }}]" value="{{ old("text.$key", $texts[$key]) }}" placeholder="{{ $def[1] }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </details>
        @endforeach
    </div>

    <div class="card" id="colors">
        <h2>الألوان</h2>
        <p class="card__sub">تتغير في المتجر كله. الوضع الداكن يحتفظ بألوانه ليبقى مقروءاً.</p>
        <div class="form form--4">
            @foreach ($colors as $key => $c)
                <div class="field">
                    <label for="c_{{ $key }}">{{ $c['label'] }}</label>
                    <div class="inline"><input id="c_{{ $key }}" type="color" name="color[{{ $key }}]" value="{{ old("color.$key", $c['value']) }}"><code>{{ $c['value'] }}</code>@if ($c['value'] !== $c['default'])<span class="pill pill--new">معدّل</span>@endif</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="savebar">
        <button class="btn">حفظ واجهة الموقع</button>
        <span>التغييرات تظهر في المتجر فور الحفظ.</span>
    </div>
</form>

<div class="card">
    <h2>الرجوع للوضع الافتراضي</h2>
    <form method="post" action="{{ route('admin.appearance.reset') }}" class="inline" onsubmit="return confirm('إرجاع القيم الافتراضية؟')">
        @csrf
        <button class="btn btn--ghost btn--sm" name="what" value="texts">النصوص الافتراضية</button>
        <button class="btn btn--ghost btn--sm" name="what" value="colors">الألوان الافتراضية</button>
        <button class="btn btn--ghost btn--sm" name="what" value="sections">ترتيب الأقسام الافتراضي</button>
    </form>
</div>
@endsection
