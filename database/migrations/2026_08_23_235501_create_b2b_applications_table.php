<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('application_number', 40)->unique();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('contact_first_name', 100);
            $table->string('contact_last_name', 100)->nullable();
            $table->string('email', 191);
            $table->string('phone', 32);
            $table->string('whatsapp', 32)->nullable();
            $table->string('preferred_contact_method', 20)->default('phone');

            $table->string('legal_business_name', 191);
            $table->string('trading_name', 191)->nullable();
            $table->string('business_type', 60);
            $table->boolean('gst_registered')->default(false);
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable();
            $table->string('fssai_number', 14)->nullable();
            $table->string('website', 500)->nullable();

            $table->string('address_line_1', 255);
            $table->string('address_line_2', 255)->nullable();
            $table->unsignedBigInteger('state_id')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->string('state_name', 120);
            $table->string('city_name', 160);
            $table->string('postal_code', 10);

            $table->json('interested_categories')->nullable();
            $table->string('estimated_monthly_purchase', 40)->nullable();
            $table->string('purchase_frequency', 40)->nullable();
            $table->text('requirements_message')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();

            $table->string('status', 40)->default('draft')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('customer_message')->nullable();

            $table->unsignedBigInteger('approved_price_group_id')->nullable();
            $table->boolean('pay_later_enabled')->default(false);
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(0);
            $table->decimal('minimum_order_value', 14, 2)->default(0);
            $table->text('delivery_arrangement')->nullable();
            $table->foreignId('approved_account_manager_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('information_requested_at')->nullable();
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamp('last_customer_edit_at')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->index(['status', 'submitted_at']);
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_applications');
    }
};
