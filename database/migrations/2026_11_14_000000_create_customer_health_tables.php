<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ملف صحي لكل شخص: الزبون نفسه (self) وأفراد عائلته الذين يطلب لهم
        Schema::create('health_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relation', 20)->default('self'); // self | child | parent | spouse | other
            $table->string('gender', 6)->nullable();          // male | female
            $table->date('birth_date')->nullable();
            $table->string('pregnancy', 15)->nullable();     // pregnant | breastfeeding
            $table->boolean('no_known_allergies')->default(false);
            $table->json('conditions')->nullable();
            $table->string('conditions_other')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_profile_id')->constrained()->cascadeOnDelete();
            $table->string('substance', 120);
            $table->string('reaction', 160)->nullable();
            $table->string('severity', 10)->default('moderate'); // mild | moderate | severe
            $table->timestamps();
        });

        // تحاليل وفحوصات سابقة يرفعها الزبون ليقرأها الصيدلاني
        Schema::create('health_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_profile_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('allergy_test'); // allergy_test | lab | prescription | report | other
            $table->string('title', 160);
            $table->string('path');
            $table->string('mime', 60);
            $table->unsignedInteger('size');
            $table->text('customer_note')->nullable();
            $table->text('pharmacist_note')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable()->index();
            $table->timestamps();
        });

        // تذكير بإعادة شراء دواء يُستعمل باستمرار
        Schema::create('refill_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('every_days')->default(30);
            $table->date('next_at')->index();
            $table->timestamp('last_reminded_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['customer_id', 'product_id']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('health_consent_at')->nullable();  // موافقة الزبون على حفظ معلوماته الصحية
            $table->text('pharmacist_note')->nullable();          // ملاحظة داخلية لا يراها الزبون
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('health_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('allergy_ack')->nullable();               // ما أقرّ الزبون بعلمه من تعارض الحساسية
            $table->timestamp('followed_up_at')->nullable();         // متابعة الصيدلاني بعد التسليم
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('health_profile_id');
            $table->dropColumn(['allergy_ack', 'followed_up_at']);
        });
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn(['health_consent_at', 'pharmacist_note']));
        foreach (['refill_reminders', 'health_documents', 'allergies', 'health_profiles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
