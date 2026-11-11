<?php

namespace App\Models;

use App\Support\Util;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];
    protected $casts = ['expires_at' => 'date', 'is_active' => 'boolean'];

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim(Util::digits((string) $code)));

        return $code === '' ? null : self::where('code', $code)->first();
    }

    /** سبب رفض الكود لهذا المبلغ، أو null إذا كان صالحاً */
    public function rejection(int $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'هذا الكود متوقف حالياً',
            $this->expires_at && $this->expires_at->endOfDay()->isPast() => 'انتهت مدة هذا الكود',
            $this->max_uses !== null && $this->used >= $this->max_uses => 'استُنفد عدد مرات استعمال هذا الكود',
            $subtotal < $this->min_total => 'هذا الكود للطلبات من '.Util::money($this->min_total).' فأكثر',
            default => null,
        };
    }

    public function discountFor(int $subtotal): int
    {
        $amount = $this->type === 'percent' ? (int) round($subtotal * min(100, $this->value) / 100) : $this->value;

        return min($amount, $subtotal);
    }

    public function getLabelAttribute(): string
    {
        return $this->type === 'percent' ? "خصم {$this->value}%" : 'خصم '.Util::money($this->value);
    }
}
