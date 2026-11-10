<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    protected $guarded = [];

    private static ?array $cache = null;

    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::$cache === null) {
            try {
                self::$cache = self::query()->pluck('value', 'key')->all();
            } catch (\Throwable) {
                self::$cache = [];
            }
        }
        $value = self::$cache[$key] ?? null;

        return ($value === null || $value === '') ? (config('pharmacy.'.$key) ?? $default) : $value;
    }

    public static function put(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }
}
