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

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
