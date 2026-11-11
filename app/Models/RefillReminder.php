<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefillReminder extends Model
{
    protected $guarded = [];

    protected $casts = ['next_at' => 'date', 'last_reminded_at' => 'datetime', 'is_active' => 'boolean'];

    public const INTERVALS = [7 => 'كل أسبوع', 14 => 'كل أسبوعين', 30 => 'كل شهر', 60 => 'كل شهرين', 90 => 'كل 3 أشهر'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getIntervalLabelAttribute(): string
    {
        return self::INTERVALS[$this->every_days] ?? 'كل '.$this->every_days.' يوماً';
    }
}
