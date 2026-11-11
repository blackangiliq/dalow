<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable
{
    protected $guarded = [];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['password' => 'hashed', 'notify_offers' => 'boolean', 'notify_telegram' => 'boolean', 'phone_verified_at' => 'datetime', 'health_consent_at' => 'datetime'];

    /**
     * هل نراسله على رقمه؟ يشترط موافقته، وتأكيد الرقم إن كانت خاصية التأكيد مشغلة
     * (حتى لا تصل رسائل لرقم سجّله شخص آخر).
     */
    public function getCanBeMessagedAttribute(): bool
    {
        return $this->notify_telegram && ($this->phone_verified_at !== null || ! \App\Support\Ui::on('phone_verify'));
    }

    public function healthProfiles()
    {
        return $this->hasMany(HealthProfile::class)->orderByRaw("case relation when 'self' then 0 else 1 end")->orderBy('id');
    }

    /** ملف الزبون نفسه، يُنشأ عند أول حاجة إليه */
    public function selfProfile(): HealthProfile
    {
        return $this->healthProfiles()->firstOrCreate(['relation' => 'self'], ['name' => $this->name]);
    }

    public function refillReminders()
    {
        return $this->hasMany(RefillReminder::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function notifications()
    {
        return $this->hasMany(CustomerNotification::class);
    }
}
