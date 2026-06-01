<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('b2b_customer_terms')) {
            return;
        }

        Schema::create('b2b_customer_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->boolean('pay_later_enabled')->default(false);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(7);
            $table->enum('credit_status', ['active', 'on_hold', 'blocked'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customer_terms');
    }
};
