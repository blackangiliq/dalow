<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('pill');
            $table->string('color', 9)->default('#13838F');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('scientific_name')->nullable()->index();
            $table->string('company')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->string('dosage_form')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('old_price')->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('image')->nullable();
            $table->timestamps();
            $table->index('name');
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type')->default('cart'); // cart | prescription
            $table->string('customer_name');
            $table->string('phone')->index();
            $table->string('area');
            $table->string('address');
            $table->text('notes')->nullable();
            $table->string('prescription_image')->nullable();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('total')->default(0);
            $table->string('status')->default('new')->index();
            $table->string('handled_by')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('qty');
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('role')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->boolean('notify')->default(true);
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'staff', 'order_items', 'orders', 'products', 'categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
