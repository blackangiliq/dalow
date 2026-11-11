<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class HealthDocument extends Model
{
    protected $guarded = [];

    protected $casts = ['reviewed_at' => 'datetime'];

    public const KINDS = [
        'allergy_test' => 'فحص حساسية', 'lab' => 'تحليل مختبر', 'prescription' => 'وصفة سابقة',
        'report' => 'تقرير طبي', 'other' => 'أخرى',
    ];

    protected static function booted(): void
    {
        // حذف السجل يحذف الملف أيضاً: لا نُبقي بيانات صحية بلا حاجة
        static::deleting(fn (HealthDocument $doc) => Storage::disk('local')->delete($doc->path));
    }

    public function profile()
    {
        return $this->belongsTo(HealthProfile::class, 'health_profile_id');
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }
}
