<?php

namespace App\Models;

use App\Search\ProductSearch;
use Illuminate\Database\Eloquent\Model;

class SearchSynonym extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn () => ProductSearch::forget());
        static::deleted(fn () => ProductSearch::forget());
    }
}
