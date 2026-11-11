{{-- ملف صحي واحد كما يراه الصيدلاني: الحساسية أولاً، ثم ما يغيّر اختيار الدواء، ثم التحاليل --}}
@php $p = $profile; @endphp
<div class="patient">
    <div class="patient__head">
        <b>{{ $p->name }}</b>
        <span class="pill">{{ \App\Models\HealthProfile::RELATIONS[$p->relation] ?? '' }}</span>
        @if ($p->age_label)<span class="pill @if ($p->age !== null && ($p->age < 12 || $p->age >= 65)) pill--new @endif">العمر {{ $p->age_label }}</span>@endif
        @if ($p->gender)<span class="pill">{{ $p->gender === 'female' ? 'أنثى' : 'ذكر' }}</span>@endif
        @if ($p->pregnancy)<span class="pill pill--bad">{{ \App\Models\HealthProfile::PREGNANCY[$p->pregnancy] }}</span>@endif
    </div>

    <div class="patient__allergy @if ($p->allergies->isEmpty()) is-empty @endif">
        <span class="patient__label">@icon('alert') الحساسية</span>
        @forelse ($p->allergies as $a)
            <span @class(['sev', 'sev--'.$a->severity])>{{ $a->substance }} · {{ $a->severity_label }}@if ($a->reaction) · {{ $a->reaction }}@endif</span>
        @empty
            <span class="t-sub">{{ $p->no_known_allergies ? 'لا توجد حساسية معروفة (أكّدها الزبون)' : 'لم يسجل الزبون حساسيته. اسأله قبل صرف المضادات الحيوية والمسكنات.' }}</span>
        @endforelse
    </div>

    <dl class="kv patient__kv">
        @if ($p->condition_labels)<dt>أمراض مزمنة</dt><dd>{{ implode('، ', $p->condition_labels) }}</dd>@endif
        @if ($p->current_medications)<dt>أدويته الحالية</dt><dd style="white-space:pre-line">{{ $p->current_medications }}</dd>@endif
        @if ($p->notes)<dt>ملاحظات</dt><dd style="white-space:pre-line">{{ $p->notes }}</dd>@endif
        @if (! $p->condition_labels && ! $p->current_medications && ! $p->notes)<dt>المعلومات</dt><dd class="t-sub">لم يسجل أمراضاً مزمنة ولا أدوية حالية.</dd>@endif
        <dt>آخر تحديث</dt><dd class="t-sub">{{ $p->updated_at->diffForHumans() }}</dd>
    </dl>

    @if ($p->documents->count())
        <div class="patient__docs">
            <span class="patient__label">@icon('sheet') التحاليل والفحوصات ({{ $p->documents->count() }})</span>
            @foreach ($p->documents as $d)
                <div @class(['hdoc', 'hdoc--new' => ! $d->reviewed_at])>
                    <a class="hdoc__thumb" href="{{ route('admin.care.document', $d) }}" target="_blank" rel="noopener" title="فتح بالحجم الكامل">
                        @if ($d->is_image)<img src="{{ route('admin.care.document', $d) }}" alt="{{ $d->title }}" loading="lazy">@else @icon('sheet') <small>PDF</small>@endif
                    </a>
                    <div class="hdoc__body">
                        <b>{{ $d->title }}</b> <span class="pill">{{ $d->kind_label }}</span>
                        <span class="t-sub">رُفع {{ $d->created_at->diffForHumans() }}@if ($d->customer_note) · سؤاله: «{{ $d->customer_note }}»@endif</span>
                        @if ($d->reviewed_at)
                            <div class="hdoc__reply"><b>قراءة {{ $d->reviewed_by }}:</b> {{ $d->pharmacist_note }}</div>
                        @else
                            <form method="post" action="{{ route('admin.care.review', $d) }}" class="hdoc__form">
                                @csrf @method('put')
                                <label class="sr-only" for="rv{{ $d->id }}">قراءتك وردك على الزبون</label>
                                <textarea id="rv{{ $d->id }}" name="pharmacist_note" rows="2" placeholder="قراءتك وردك على الزبون. يصله في حسابه وعلى رقمه." required></textarea>
                                <button class="btn btn--sm">أرسل القراءة</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
