<?php

namespace App\Models;

use App\Search\ProductSearch;
use App\Services\DiscountNotifier;
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
        'expires_at' => 'date',
    ];

    public const FORMS = [
        'أقراص' => 'pill', 'كبسول' => 'capsule', 'شراب' => 'syrup', 'كريم' => 'tube', 'مرهم' => 'tube',
        'جل' => 'tube', 'قطرة' => 'drop', 'بخاخ' => 'spray', 'حقن' => 'syringe', 'فوار' => 'pill',
        'جهاز' => 'device', 'مستلزمات' => 'firstaid', 'غسول' => 'bottle', 'شامبو' => 'bottle',
        'حليب' => 'baby', 'أخرى' => 'box',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => ProductSearch::forget());
        static::deleted(fn () => ProductSearch::forget());

        // خصم جديد أو نزول في السعر: نبلغ من وضع المنتج في مفضلته
        static::updated(function (Product $product) {
            $before = (int) $product->getOriginal('price');
            $hadOffer = $product->getOriginal('old_price') > $before;
            if ($product->is_sellable && ($product->price < $before || (! $hadOffer && $product->discount > 0))) {
                app(DiscountNotifier::class)->priceDropped($product, $before);
            }
            // رجع المنتج للمخزون: نبلغ من ينتظره
            if ($product->is_sellable && (int) $product->getOriginal('stock') < 1 && $product->stock > 0) {
                app(\App\Services\CustomerNotifier::class)->backInStock($product);
            }
        });
    }

    /** رابط المنتج يحمل اسمه لمحركات البحث: /product/6-augmentin-1g */
    public function getRouteKey(): string
    {
        $slug = trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($this->name)), '-');

        return $this->id.($slug !== '' ? '-'.$slug : '');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->whereKey((int) $value)->first();
    }

    public function favoredBy()
    {
        return $this->belongsToMany(Customer::class, 'favorites')->withTimestamps();
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** ما يُعرض في المتجر: ظاهر وغير منتهي الصلاحية */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn (Builder $w) => $w->whereNull('expires_at')->orWhere('expires_at', '>=', today()));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(today());
    }

    /** هل يُباع في المتجر الآن؟ */
    public function getIsSellableAttribute(): bool
    {
        return $this->is_active && ! $this->is_expired;
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
