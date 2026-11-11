<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthProfile extends Model
{
    protected $guarded = [];

    protected $casts = ['birth_date' => 'date', 'conditions' => 'array', 'no_known_allergies' => 'boolean'];

    public const RELATIONS = ['self' => 'أنا', 'child' => 'ابن / ابنة', 'parent' => 'أب / أم', 'spouse' => 'زوج / زوجة', 'other' => 'قريب آخر'];

    public const CONDITIONS = [
        'diabetes' => 'السكري', 'hypertension' => 'ضغط الدم', 'heart' => 'أمراض القلب', 'asthma' => 'الربو والحساسية الصدرية',
        'kidney' => 'أمراض الكلى', 'liver' => 'أمراض الكبد', 'thyroid' => 'الغدة الدرقية', 'epilepsy' => 'الصرع',
        'stomach' => 'قرحة أو نزف معدة', 'anticoagulant' => 'يأخذ مميّع دم', 'g6pd' => 'نقص G6PD (الفول)',
    ];

    public const PREGNANCY = ['pregnant' => 'حامل', 'breastfeeding' => 'مرضعة'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function allergies()
    {
        return $this->hasMany(Allergy::class)->orderByRaw("case severity when 'severe' then 0 when 'moderate' then 1 else 2 end")->orderBy('substance');
    }

    public function documents()
    {
        return $this->hasMany(HealthDocument::class)->latest();
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birth_date?->age;
    }

    /** وصف قصير للعمر يناسب الصيدلاني: «3 سنوات»، «8 أشهر» */
    public function getAgeLabelAttribute(): ?string
    {
        if (! $this->birth_date) {
            return null;
        }
        $months = (int) $this->birth_date->diffInMonths(now());

        return $months < 24 ? $months.' شهراً' : intdiv($months, 12).' سنة';
    }

    /** @return string[] أسماء الأمراض المزمنة بالعربية */
    public function getConditionLabelsAttribute(): array
    {
        $labels = array_map(fn ($k) => self::CONDITIONS[$k] ?? $k, $this->conditions ?? []);
        if ($this->conditions_other) {
            $labels[] = $this->conditions_other;
        }

        return $labels;
    }

    /** هل في الملف ما يستحق انتباه الصيدلاني قبل صرف الدواء؟ */
    public function getNeedsAttentionAttribute(): bool
    {
        return $this->allergies->isNotEmpty() || $this->pregnancy !== null || $this->condition_labels !== []
            || ($this->age !== null && ($this->age < 12 || $this->age >= 65));
    }

    public function getIsFilledAttribute(): bool
    {
        return $this->no_known_allergies || $this->allergies->isNotEmpty() || $this->condition_labels !== [] || $this->current_medications;
    }
}
