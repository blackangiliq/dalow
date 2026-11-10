<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    public const UPDATED_AT = null;
    protected $guarded = [];
    protected $casts = ['day' => 'date'];
}
