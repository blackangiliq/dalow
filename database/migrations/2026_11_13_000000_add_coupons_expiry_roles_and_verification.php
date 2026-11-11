<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('min_total')->default(0);
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('used')->default(0);
            $table->date('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 40)->nullable();
            $table->unsignedInteger('discount')->default(0);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->date('expires_at')->nullable()->index(); // تاريخ انتهاء الصلاحية: المنتهي يختفي من المتجر تلقائياً
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 10)->default('admin'); // admin: كل شيء | staff: الطلبات والمنتجات والزبائن فقط
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('phone_verified_at'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('expires_at'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['coupon_code', 'discount']));
        Schema::dropIfExists('coupons');
    }
};
