<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('password');
            $table->string('area')->nullable();
            $table->string('address')->nullable();
            $table->boolean('notify_offers')->default(true);   // إشعار داخل الموقع بكل عرض جديد
            $table->boolean('notify_telegram')->default(true); // رسالة تيليجرام عند خصم منتج في المفضلة
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
        });

        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('body')->nullable();
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('product_name');
            $table->text('details')->nullable();
            $table->string('image')->nullable();
            $table->string('status')->default('new')->index(); // new | sourcing | available | unavailable
            $table->timestamps();
        });

        // زيارات الصفحات: معرف زائر مجهول (كوكي عشوائي) بلا أي بيانات شخصية
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor', 32)->index();
            $table->date('day')->index();
            $table->string('type', 20);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('path', 191);
            $table->string('referrer', 120)->nullable();
            $table->string('device', 10);
            $table->timestamp('created_at')->nullable();
            $table->index(['type', 'ref_id']);
        });

        // نقرات وزمن مشاهدة لمعرفة ما يلفت انتباه الزائر
        Schema::create('track_events', function (Blueprint $table) {
            $table->id();
            $table->string('visitor', 32);
            $table->date('day')->index();
            $table->string('kind', 10);  // click | dwell
            $table->string('name', 80);
            $table->unsignedInteger('value')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->index(['kind', 'name']);
        });

        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('visitor', 32)->nullable();
            $table->string('term', 120);
            $table->string('normalized', 120)->index();
            $table->unsignedInteger('results');
            $table->date('day')->index();
            $table->timestamp('created_at')->nullable();
        });

        // مرادفات البحث: كلمة يكتبها الزبون => كلمات تُضاف للبحث
        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('term');
            $table->string('expands_to', 500);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('keywords', 500)->nullable(); // أسماء بديلة وكلمات يبحث بها الزبائن
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('keywords'));
        foreach (['search_synonyms', 'search_logs', 'track_events', 'visits', 'product_requests', 'customer_notifications', 'favorites', 'customers'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
