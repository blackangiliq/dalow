<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'new' => 'جديد',
        'confirmed' => 'مؤكد',
        'preparing' => 'قيد التجهيز',
        'shipping' => 'خرج للتوصيل',
        'delivered' => 'تم التسليم',
        'cancelled' => 'ملغي',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** يغيّر الحالة ويعيد الكميات للمخزون عند الإلغاء (أو يخصمها إذا أعيد تفعيل طلب ملغي) */
    public function changeStatus(string $status, ?string $by = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($status, $by) {
            $wasCancelled = $this->status === 'cancelled';
            $nowCancelled = $status === 'cancelled';
            if ($wasCancelled !== $nowCancelled) {
                foreach ($this->items as $item) {
                    if ($item->product_id) {
                        Product::whereKey($item->product_id)->{$nowCancelled ? 'increment' : 'decrement'}('stock', $item->qty);
                    }
                }
            }
            $this->update(['status' => $status, 'handled_by' => $by ?? $this->handled_by]);
        });
    }

    public static function newNumber(): string
    {
        do {
            $number = 'D'.random_int(100000, 999999);
        } while (self::where('number', $number)->exists());

        return $number;
    }
}
