<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRequest extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'new' => 'جديد',
        'sourcing' => 'جارٍ التوفير',
        'available' => 'تم توفيره',
        'unavailable' => 'غير متوفر',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
