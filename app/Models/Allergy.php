<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allergy extends Model
{
    protected $guarded = [];

    public const SEVERITIES = ['mild' => 'خفيفة', 'moderate' => 'متوسطة', 'severe' => 'شديدة'];

    public function profile()
    {
        return $this->belongsTo(HealthProfile::class, 'health_profile_id');
    }

    public function getSeverityLabelAttribute(): string
    {
        return self::SEVERITIES[$this->severity] ?? $this->severity;
    }
}
