<?php

namespace App\Models;

use App\Support\Util;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'requires_prescription' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const FORMS = [
        'أقراص' => 'pill', 'كبسول' => 'capsule', 'شراب' => 'syrup', 'كريم' => 'tube', 'مرهم' => 'tube',
        'جل' => 'tube', 'قطرة' => 'drop', 'بخاخ' => 'spray', 'حقن' => 'syringe', 'فوار' => 'pill',
        'جهاز' => 'device', 'مستلزمات' => 'firstaid', 'غسول' => 'bottle', 'شامبو' => 'bottle',
        'حليب' => 'baby', 'أخرى' => 'box',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim(Util::digits($term));
        if ($term === '') {
            return $q;
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $q->where(fn (Builder $w) => $w
            ->where('name', 'like', $like)
            ->orWhere('scientific_name', 'like', $like)
            ->orWhere('company', 'like', $like)
            ->orWhere('barcode', $term));
    }

    public function getDiscountAttribute(): int
    {
        return ($this->old_price && $this->old_price > $this->price)
            ? (int) round(100 - $this->price * 100 / $this->old_price)
            : 0;
    }

    public function getIconAttribute(): string
    {
        return self::FORMS[$this->dosage_form] ?? ($this->category->icon ?? 'box');
    }

    public function getTintAttribute(): string
    {
        return $this->category->color ?? '#13838F';
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('uploads/products/'.$this->image) : null;
    }
}
