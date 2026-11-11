<?php

namespace App\Http\Controllers;

use App\Models\HealthDocument;
use App\Models\HealthProfile;
use App\Models\Product;
use App\Models\RefillReminder;
use App\Services\StaffNotifier;
use App\Support\Ui;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** الملف الصحي للزبون وعائلته: الحساسية، الأمراض المزمنة، الأدوية، التحاليل، وتذكير إعادة الشراء */
class HealthController extends Controller
{
    /** إضافة فرد من العائلة */
    public function store(Request $request)
    {
        abort_unless(Ui::on('family_profiles'), 404);
        $customer = $request->user('customer');
        if ($customer->healthProfiles()->count() >= 10) {
            return back()->with('error', 'وصلت إلى الحد الأقصى: 10 ملفات.');
        }
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'relation' => ['required', Rule::in(array_keys(array_diff_key(HealthProfile::RELATIONS, ['self' => 1])))],
        ], [], ['name' => 'الاسم', 'relation' => 'صلة القرابة']);
        $profile = $customer->healthProfiles()->create($data);

        return redirect()->route('account', ['tab' => 'health', 'profile' => $profile->id])->with('ok', 'أضيف ملف '.$profile->name.'. أكمل حساسيته وأمراضه الآن.');
    }

    public function update(Request $request, HealthProfile $profile)
    {
        $this->own($request, $profile);
        $customer = $request->user('customer');
        $data = $request->validate([
            'name' => 'required|string|min:2|max:80',
            'gender' => 'nullable|in:male,female',
            'birth_date' => 'nullable|date|before_or_equal:today|after:1900-01-01',
            'pregnancy' => ['nullable', Rule::in(array_keys(HealthProfile::PREGNANCY))],
            'conditions' => 'nullable|array',
            'conditions.*' => Rule::in(array_keys(HealthProfile::CONDITIONS)),
            'conditions_other' => 'nullable|string|max:160',
            'current_medications' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'consent' => [$customer->health_consent_at ? 'nullable' : 'accepted'],
        ], ['consent.accepted' => 'وافق على حفظ معلوماتك الصحية لنستطيع حفظها'], [
            'birth_date' => 'تاريخ الميلاد', 'current_medications' => 'الأدوية الحالية', 'conditions_other' => 'أمراض أخرى',
        ]);
        unset($data['consent']);
        if (($data['gender'] ?? null) !== 'female') {
            $data['pregnancy'] = null;
        }
        $profile->update($data + ['conditions' => $data['conditions'] ?? [], 'no_known_allergies' => $request->boolean('no_known_allergies') && $profile->allergies()->doesntExist()]);
        $customer->health_consent_at ??= now();
        $customer->save();

        return back()->with('ok', 'حُفظ الملف الصحي لـ'.$profile->name.'. يراه الصيدلاني مع كل طلب.');
    }

    public function destroy(Request $request, HealthProfile $profile)
    {
        $this->own($request, $profile);
        abort_if($profile->relation === 'self', 403);
        $profile->documents->each->delete();
        $profile->delete();

        return redirect()->route('account', ['tab' => 'health'])->with('ok', 'حُذف ملف '.$profile->name.' وكل ما فيه.');
    }

    public function allergyStore(Request $request, HealthProfile $profile)
    {
        $this->own($request, $profile);
        $customer = $request->user('customer');
        $data = $request->validate([
            'substance' => 'required|string|min:2|max:120',
            'reaction' => 'nullable|string|max:160',
            'severity' => 'required|in:mild,moderate,severe',
            'consent' => [$customer->health_consent_at ? 'nullable' : 'accepted'],
        ], ['consent.accepted' => 'وافق على حفظ معلوماتك الصحية لنستطيع حفظها'], ['substance' => 'اسم الدواء أو المادة', 'reaction' => 'ما حدث']);

        foreach (array_filter(array_map('trim', preg_split('/[،,]+/u', $data['substance']))) as $substance) {
            $profile->allergies()->firstOrCreate(['substance' => mb_substr($substance, 0, 120)], ['reaction' => $data['reaction'], 'severity' => $data['severity']]);
        }
        $profile->update(['no_known_allergies' => false]);
        $customer->health_consent_at ??= now();
        $customer->save();

        return back()->with('ok', 'أضيفت الحساسية. سننبهك والصيدلاني إن طلبت دواءً يتعارض معها.');
    }

    public function allergyDestroy(Request $request, HealthProfile $profile, int $allergy)
    {
        $this->own($request, $profile);
        $profile->allergies()->whereKey($allergy)->delete();

        return back()->with('ok', 'حُذفت الحساسية');
    }

    public function documentStore(Request $request, HealthProfile $profile, StaffNotifier $notifier)
    {
        abort_unless(Ui::on('health_documents'), 404);
        $this->own($request, $profile);
        $customer = $request->user('customer');
        if (HealthDocument::whereIn('health_profile_id', $customer->healthProfiles()->pluck('id'))->count() >= 30) {
            return back()->with('error', 'وصلت إلى الحد الأقصى: 30 ملفاً. احذف القديم أولاً.');
        }
        $data = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'kind' => ['required', Rule::in(array_keys(HealthDocument::KINDS))],
            'title' => 'nullable|string|max:160',
            'customer_note' => 'nullable|string|max:500',
            'consent' => [$customer->health_consent_at ? 'nullable' : 'accepted'],
        ], [
            'file.required' => 'اختر صورة الفحص أو ملف PDF',
            'file.mimes' => 'الملف يجب أن يكون صورة (JPG أو PNG) أو PDF',
            'file.max' => 'حجم الملف أكبر من 10 ميغابايت',
            'consent.accepted' => 'وافق على حفظ معلوماتك الصحية لنستطيع حفظها',
        ], ['customer_note' => 'سؤالك للصيدلاني']);

        $file = $request->file('file');
        $document = $profile->documents()->create([
            'kind' => $data['kind'],
            'title' => $data['title'] ?: HealthDocument::KINDS[$data['kind']].' '.now()->format('Y-m-d'),
            'customer_note' => $data['customer_note'] ?? null,
            // مجلد خاص غير منشور، واسم عشوائي لا يكشف شيئاً عن صاحبه
            'path' => $file->storeAs('health/'.$customer->id, Str::uuid().'.'.$file->extension(), 'local'),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        $customer->health_consent_at ??= now();
        $customer->save();
        defer(fn () => $notifier->documentUploaded($document));

        return back()->with('ok', 'وصل الملف إلى الصيدلاني. سيصلك إشعار عند قراءته والرد عليه.');
    }

    public function documentDestroy(Request $request, HealthDocument $document)
    {
        $this->own($request, $document->profile);
        $document->delete();

        return back()->with('ok', 'حُذف الملف نهائياً');
    }

    /** عرض الملف لصاحبه فقط */
    public function documentFile(Request $request, HealthDocument $document)
    {
        $this->own($request, $document->profile);

        return self::stream($document);
    }

    public static function stream(HealthDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->response($document->path, Str::slug($document->title) ?: 'document', [
            'Content-Type' => $document->mime,
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    public function reminderStore(Request $request, Product $product)
    {
        abort_unless(Ui::on('refill_reminders'), 404);
        $data = $request->validate(['every_days' => ['required', Rule::in(array_keys(RefillReminder::INTERVALS))]]);
        $reminder = $request->user('customer')->refillReminders()->updateOrCreate(
            ['product_id' => $product->id],
            ['every_days' => (int) $data['every_days'], 'next_at' => today()->addDays((int) $data['every_days']), 'is_active' => true],
        );

        return back()->with('ok', 'سنذكّرك بـ«'.$product->name.'» '.$reminder->interval_label.'. أول تذكير يوم '.$reminder->next_at->translatedFormat('j F').'.');
    }

    public function reminderDestroy(Request $request, RefillReminder $reminder)
    {
        abort_unless($reminder->customer_id === $request->user('customer')->id, 404);
        $reminder->delete();

        return back()->with('ok', 'أُلغي التذكير');
    }

    private function own(Request $request, HealthProfile $profile): void
    {
        abort_unless(Ui::on('health_profile') && $profile->customer_id === $request->user('customer')->id, 404);
    }
}
