<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];

    public const ICONS = [
        'pill' => 'حبوب', 'syrup' => 'شراب', 'vitamin' => 'فيتامينات', 'skin' => 'عناية بالبشرة',
        'baby' => 'أم وطفل', 'device' => 'أجهزة', 'heart' => 'قلب وضغط', 'care' => 'عناية شخصية',
        'firstaid' => 'إسعافات', 'drop' => 'قطرات',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => \App\Search\ProductSearch::forget());
        static::deleted(fn () => \App\Search\ProductSearch::forget());
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
