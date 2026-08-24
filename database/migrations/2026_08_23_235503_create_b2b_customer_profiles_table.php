<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_customer_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('b2b_application_id')->unique()->constrained('b2b_applications')->cascadeOnDelete();
            $table->string('legal_business_name', 191);
            $table->string('trading_name', 191)->nullable();
            $table->string('business_type', 60);
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable();
            $table->string('fssai_number', 14)->nullable();
            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('state_name', 120);
            $table->string('city_name', 160);
            $table->string('postal_code', 10);
            $table->unsignedBigInteger('price_group_id')->nullable();
            $table->boolean('pay_later_enabled')->default(false);
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->decimal('minimum_order_value', 14, 2)->default(0);
            $table->text('delivery_arrangement')->nullable();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_customer_profiles');
    }
};
