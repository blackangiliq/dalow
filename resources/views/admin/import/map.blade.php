@extends('layouts.admin')
@section('title', 'اختيار الحقول')

@section('content')
<div class="head">
    <div><h1>اختيار الحقول</h1><p>الملف: {{ $fileName }} (<span class="num">{{ $total }}</span> سطر)</p></div>
    <a class="btn btn--ghost" href="{{ route('admin.import') }}">رفع ملف آخر</a>
</div>

<ol class="wizard"><li class="did">رفع الملف</li><li class="on">اختيار الحقول</li><li>النتيجة</li></ol>

<form method="post" action="{{ route('admin.import.run') }}">
    @csrf
    <div class="card">
        <h2>أي عمود يقابل أي حقل؟</h2>
        <p class="card__sub">خمّنا الأعمدة من عناوينها. راجعها وعدّل ما يلزم، واترك الحقل على «لا يوجد» إذا لم يكن في ملفك.</p>
        @foreach ($fields as $key => $meta)
            <div class="maprow">
                <b><label for="map_{{ $key }}">{{ $meta['label'] }}</label> @if (! empty($meta['required']))<span class="req">مطلوب</span>@endif</b>
                <select id="map_{{ $key }}" name="map[{{ $key }}]" data-map @if (! empty($meta['required'])) required @endif>
                    <option value="">لا يوجد في الملف</option>
                    @foreach ($headers as $i => $h)
                        <option value="{{ $i }}" data-peek="{{ $sample[1][$i] ?? '' }}" @selected(old('map.'.$key, $guess[$key] ?? '') !== '' && (int) old('map.'.$key, $guess[$key] ?? -1) === $i)>
                            العمود {{ $i + 1 }}{{ $h !== '' ? ': '.$h : '' }}
                        </option>
                    @endforeach
                </select>
                <span class="peek" data-peek-out></span>
            </div>
        @endforeach
    </div>

    <div class="card">
        <h2>خيارات الاستيراد</h2>
        <div class="form">
            <div class="field field--full checks">
                <label class="check"><input type="checkbox" name="has_header" value="1" checked> السطر الأول عناوين أعمدة (لا يُستورد)</label>
                <label class="check"><input type="checkbox" name="create_categories" value="1" checked> إنشاء الأقسام غير الموجودة تلقائياً</label>
            </div>
            <div class="field">
                <label for="match_by">التعرف على المنتج الموجود مسبقاً</label>
                <select id="match_by" name="match_by">
                    <option value="barcode">بالباركود، ثم بالاسم</option>
                    <option value="name">بالاسم فقط</option>
                </select>
            </div>
            <div class="field">
                <label for="on_duplicate">إذا كان المنتج موجوداً</label>
                <select id="on_duplicate" name="on_duplicate">
                    <option value="update">حدّث سعره وبياناته</option>
                    <option value="skip">تجاوزه واتركه كما هو</option>
                </select>
            </div>
            <div class="field">
                <label for="default_category">قسم المنتجات الجديدة التي بلا قسم</label>
                <select id="default_category" name="default_category">
                    <option value="">بدون قسم</option>
                    @foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </select>
            </div>
        </div>
        <button class="btn" style="margin-top:18px">استورد <span class="num">{{ $total }}</span> سطر دفعة واحدة</button>
    </div>
</form>

<div class="card">
    <h2>معاينة أول الأسطر</h2>
    <div class="tablewrap preview">
        <table>
            <thead><tr>@foreach ($headers as $i => $h)<th>العمود {{ $i + 1 }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach ($sample as $row)
                <tr>@foreach ($headers as $i => $h)<td>{{ $row[$i] ?? '' }}</td>@endforeach</tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
// عرض مثال من الملف بجانب كل حقل
document.querySelectorAll('[data-map]').forEach((sel) => {
    const out = sel.closest('.maprow').querySelector('[data-peek-out]');
    const show = () => {
        const peek = sel.selectedOptions[0]?.dataset.peek;
        out.textContent = sel.value === '' ? '' : (peek ? 'مثال: ' + peek : 'العمود فارغ في أول سطر');
    };
    sel.addEventListener('change', show);
    show();
});
</script>
@endsection
