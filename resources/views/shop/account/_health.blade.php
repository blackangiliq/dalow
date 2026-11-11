{{-- الملف الصحي: حساسية الزبون وعائلته، أمراضهم المزمنة، أدويتهم، وتحاليلهم السابقة --}}
@php $needConsent = ! $customer->health_consent_at; @endphp

@if (request('welcome'))
    <div class="note note--ok">@icon('check') <p><strong>أكمل ملفك الصحي الآن، يأخذ دقيقة واحدة.</strong> يراه الصيدلاني مع كل طلب فيختار لك الدواء الآمن، وننبهك إن اخترت دواءً يتعارض مع حساسيتك.</p></div>
@endif
<p class="page__sub" style="margin-bottom:16px">معلوماتك الصحية سرية: يراها صيدلاني الصيدلية فقط، ولا تظهر لأي جهة أخرى. تستطيع تعديلها أو حذفها متى شئت.</p>

@if ($on['family_profiles'])
<div class="people-switch" role="tablist" aria-label="لمن الملف">
    @foreach ($profiles as $p)
        <a href="{{ route('account', ['tab' => 'health', 'profile' => $p->id]) }}" @class(['on' => $p->id === $current->id]) role="tab" aria-selected="{{ $p->id === $current->id ? 'true' : 'false' }}">
            {{ $p->relation === 'self' ? 'أنا' : $p->name }}
            @if ($p->allergies->count())<span class="dot dot--warn" title="لديه حساسية"></span>@endif
        </a>
    @endforeach
    <details class="addperson">
        <summary>+ أضف فرداً من العائلة</summary>
        <form method="post" action="{{ route('health.store') }}" class="inline-form">
            @csrf
            <input name="name" placeholder="الاسم" aria-label="الاسم" required>
            <select name="relation" aria-label="صلة القرابة">@foreach (array_diff_key(\App\Models\HealthProfile::RELATIONS, ['self' => 1]) as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
            <button class="btn btn--sm">أضف</button>
        </form>
        <small>اطلب لأطفالك أو والديك وتبقى حساسية كل واحد منفصلة.</small>
    </details>
</div>
@endif

<div class="healthgrid">
    {{-- الحساسية: أهم ما في الملف --}}
    <section class="panel hcard hcard--allergy" id="allergies">
        <h2>@icon('alert') الحساسية {{ $current->relation === 'self' ? '' : '— '.$current->name }}</h2>
        @forelse ($current->allergies as $a)
            <div class="allergy">
                <span @class(['sev', 'sev--'.$a->severity])>{{ $a->severity_label }}</span>
                <div><b>{{ $a->substance }}</b>@if ($a->reaction)<small>{{ $a->reaction }}</small>@endif</div>
                <form method="post" action="{{ route('health.allergy.destroy', [$current, $a->id]) }}" onsubmit="return confirm('حذف حساسية {{ $a->substance }}؟')">@csrf @method('delete')<button class="link" aria-label="حذف حساسية {{ $a->substance }}">حذف</button></form>
            </div>
        @empty
            <p class="hint" style="margin:0 0 12px">{{ $current->no_known_allergies ? 'مسجل: لا توجد حساسية معروفة.' : 'لم تُسجَّل أي حساسية بعد.' }}</p>
        @endforelse

        <form method="post" action="{{ route('health.allergy', $current) }}" class="form" style="margin-top:14px">
            @csrf
            <div @class(['field', 'field--full', 'bad' => $errors->has('substance')])>
                <label for="substance">أضف حساسية من دواء أو مادة</label>
                <input id="substance" name="substance" value="{{ old('substance') }}" placeholder="مثلاً: البنسلين" required>
                <div class="quickchips">@foreach (\App\Support\AllergyCheck::COMMON as $c)<button type="button" data-chip="{{ $c }}" data-chip-target="#substance">{{ $c }}</button>@endforeach</div>
                @error('substance')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="reaction">ماذا حدث؟ (اختياري)</label>
                <input id="reaction" name="reaction" value="{{ old('reaction') }}" placeholder="طفح جلدي، ضيق نفس، تورم">
            </div>
            <div class="field">
                <label for="severity">شدتها</label>
                <select id="severity" name="severity">@foreach (\App\Models\Allergy::SEVERITIES as $k => $l)<option value="{{ $k }}" @selected(old('severity', 'moderate') === $k)>{{ $l }}</option>@endforeach</select>
            </div>
            @if ($needConsent)
                <label class="checkline field--full"><input type="checkbox" name="consent" value="1"> <span>أوافق على حفظ معلوماتي الصحية ليطلع عليها صيدلاني الصيدلية فقط</span></label>
                @error('consent')<span class="err field--full">{{ $message }}</span>@enderror
            @endif
            <button class="btn btn--sm" style="justify-self:start">@icon('alert') أضف الحساسية</button>
        </form>
    </section>

    {{-- المعلومات التي تغيّر اختيار الدواء --}}
    <form class="panel hcard" method="post" action="{{ route('health.update', $current) }}" data-health-form>
        @csrf @method('put')
        <h2>@icon('user') معلومات تهم الصيدلاني</h2>
        <div class="form">
            <div @class(['field', 'bad' => $errors->has('name')])>
                <label for="pname">الاسم</label>
                <input id="pname" name="name" value="{{ old('name', $current->name) }}" required>
            </div>
            <div class="field">
                <label for="birth_date">تاريخ الميلاد</label>
                <input id="birth_date" type="date" name="birth_date" value="{{ old('birth_date', $current->birth_date?->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}">
                <span class="hint">جرعات الأطفال وكبار السن تختلف</span>
                @error('birth_date')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
                <label for="gender">الجنس</label>
                <select id="gender" name="gender" data-gender>
                    <option value="">غير محدد</option>
                    <option value="male" @selected(old('gender', $current->gender) === 'male')>ذكر</option>
                    <option value="female" @selected(old('gender', $current->gender) === 'female')>أنثى</option>
                </select>
            </div>
            <div class="field" data-pregnancy @if (old('gender', $current->gender) !== 'female') hidden @endif>
                <label for="pregnancy">الحمل والرضاعة</label>
                <select id="pregnancy" name="pregnancy">
                    <option value="">لا</option>
                    @foreach (\App\Models\HealthProfile::PREGNANCY as $k => $l)<option value="{{ $k }}" @selected(old('pregnancy', $current->pregnancy) === $k)>{{ $l }}</option>@endforeach
                </select>
                <span class="hint">أدوية كثيرة تُمنع أثناء الحمل والرضاعة</span>
            </div>
            <fieldset class="field field--full conds">
                <legend>أمراض مزمنة</legend>
                @foreach (\App\Models\HealthProfile::CONDITIONS as $k => $l)
                    <label class="checkline"><input type="checkbox" name="conditions[]" value="{{ $k }}" @checked(in_array($k, old('conditions', $current->conditions ?? []), true))> <span>{{ $l }}</span></label>
                @endforeach
            </fieldset>
            <div class="field field--full">
                <label for="conditions_other">أمراض أخرى</label>
                <input id="conditions_other" name="conditions_other" value="{{ old('conditions_other', $current->conditions_other) }}" placeholder="اكتب ما لم يُذكر أعلاه">
            </div>
            <div class="field field--full">
                <label for="current_medications">الأدوية التي يأخذها حالياً</label>
                <textarea id="current_medications" name="current_medications" rows="3" placeholder="الاسم والجرعة، مثلاً: Concor 5mg مرة يومياً">{{ old('current_medications', $current->current_medications) }}</textarea>
                <span class="hint">ليتأكد الصيدلاني من عدم تعارض الدواء الجديد مع أدويتك</span>
            </div>
            <div class="field field--full">
                <label for="notes">ملاحظات أخرى (اختياري)</label>
                <textarea id="notes" name="notes" rows="2" placeholder="مثلاً: لا يبلع الحبوب، يفضّل الشراب">{{ old('notes', $current->notes) }}</textarea>
            </div>
            @if ($current->allergies->isEmpty())
                <label class="checkline field--full"><input type="checkbox" name="no_known_allergies" value="1" @checked(old('no_known_allergies', $current->no_known_allergies))> <span>أؤكد أنه لا توجد حساسية معروفة من أي دواء</span></label>
            @endif
            @if ($needConsent)
                <label class="checkline field--full"><input type="checkbox" name="consent" value="1"> <span>أوافق على حفظ معلوماتي الصحية ليطلع عليها صيدلاني الصيدلية فقط</span></label>
                @error('consent')<span class="err field--full">{{ $message }}</span>@enderror
            @endif
        </div>
        <button class="btn" style="margin-top:16px">حفظ الملف الصحي</button>
    </form>

    @if ($on['health_documents'])
    <section class="panel hcard" id="documents">
        <h2>@icon('sheet') التحاليل والفحوصات</h2>
        <p class="hint" style="margin:-6px 0 14px">ارفع فحص الحساسية أو تحاليلك السابقة. يقرأها الصيدلاني ويرد عليك هنا، ويصلك إشعار.</p>
        @forelse ($current->documents as $d)
            <div class="doc">
                <span class="doc__ico">@icon($d->is_image ? 'scan' : 'sheet')</span>
                <div class="doc__body">
                    <b>{{ $d->title }}</b>
                    <small>{{ $d->kind_label }} · {{ $d->created_at->translatedFormat('j F Y') }}</small>
                    @if ($d->customer_note)<small>سؤالك: {{ $d->customer_note }}</small>@endif
                    @if ($d->reviewed_at)
                        <div class="reply"><b>رد الصيدلاني ({{ $d->reviewed_by }}):</b> {{ $d->pharmacist_note }}</div>
                    @else
                        <span class="badge">بانتظار قراءة الصيدلاني</span>
                    @endif
                </div>
                <div class="doc__acts">
                    <a class="btn btn--sm btn--ghost" href="{{ route('health.document.file', $d) }}" target="_blank" rel="noopener">عرض</a>
                    <form method="post" action="{{ route('health.document.destroy', $d) }}" onsubmit="return confirm('حذف الملف نهائياً؟')">@csrf @method('delete')<button class="link">حذف</button></form>
                </div>
            </div>
        @empty
            <p class="hint">لم تُرفع أي تحاليل بعد.</p>
        @endforelse

        <form method="post" action="{{ route('health.document', $current) }}" enctype="multipart/form-data" class="form" style="margin-top:16px">
            @csrf
            <div class="field">
                <label for="kind">نوع الملف</label>
                <select id="kind" name="kind">@foreach (\App\Models\HealthDocument::KINDS as $k => $l)<option value="{{ $k }}" @selected(old('kind') === $k)>{{ $l }}</option>@endforeach</select>
            </div>
            <div class="field">
                <label for="title">عنوان (اختياري)</label>
                <input id="title" name="title" value="{{ old('title') }}" placeholder="مثلاً: فحص حساسية مختبر ابن سينا">
            </div>
            <div @class(['field', 'field--full', 'bad' => $errors->has('file')])>
                <label class="drop">
                    @icon('upload')
                    <b>اختر الصورة أو ملف PDF</b>
                    <small>JPG أو PNG أو PDF حتى 10 ميغابايت</small>
                    <input type="file" name="file" accept="image/*,application/pdf" required>
                </label>
                @error('file')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field field--full">
                <label for="customer_note">سؤالك للصيدلاني (اختياري)</label>
                <input id="customer_note" name="customer_note" value="{{ old('customer_note') }}" placeholder="مثلاً: هل أستطيع أخذ المضاد الحيوي مع هذه النتيجة؟">
            </div>
            @if ($needConsent)
                <label class="checkline field--full"><input type="checkbox" name="consent" value="1"> <span>أوافق على حفظ معلوماتي الصحية ليطلع عليها صيدلاني الصيدلية فقط</span></label>
            @endif
            <button class="btn btn--sm" style="justify-self:start">@icon('send') ارسله للصيدلاني</button>
        </form>
    </section>
    @endif
</div>

@if ($current->relation !== 'self')
    <form method="post" action="{{ route('health.destroy', $current) }}" style="margin-top:16px" onsubmit="return confirm('حذف ملف {{ $current->name }} وكل ما فيه نهائياً؟')">
        @csrf @method('delete')
        <button class="link">حذف ملف {{ $current->name }}</button>
    </form>
@endif
