<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('recurring_expense_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('description');
            $table->string('payee')->nullable();
            $table->string('frequency', 32)->default('monthly');
            $table->decimal('expected_taxable_amount', 14, 2)->default(0);
            $table->decimal('expected_gst_amount', 14, 2)->default(0);
            $table->decimal('expected_total_amount', 14, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_due_date');
            $table->string('default_payment_method', 40)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'next_due_date']);
        });

        Schema::create('business_expenses', function (Blueprint $table): void {
            $table->id();
            $table->string('expense_number', 40)->unique();
            $table->date('expense_date');
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->string('description');
            $table->string('payee')->nullable();
            $table->decimal('taxable_amount', 14, 2)->default(0);
            $table->decimal('gst_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('record_status', 20)->default('draft');
            $table->string('payment_status', 20)->default('unpaid');
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_reference')->nullable();
            $table->date('due_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_original_name')->nullable();
            $table->string('receipt_mime_type', 100)->nullable();
            $table->unsignedBigInteger('receipt_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recurring_expense_template_id')->nullable()->constrained('recurring_expense_templates')->nullOnDelete();
            $table->date('generated_for_date')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['recurring_expense_template_id', 'generated_for_date'],
                'business_expenses_recurring_date_unique'
            );
            $table->index(['expense_date', 'record_status']);
            $table->index(['payment_status', 'paid_date']);
        });

        Schema::create('salary_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('monthly_salary', 14, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->unsignedTinyInteger('payment_day')->default(7);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'effective_from', 'effective_to']);
            $table->index(['is_active', 'effective_from']);
        });

        Schema::create('salary_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('salary_profile_id')->nullable()->constrained('salary_profiles')->nullOnDelete();
            $table->string('staff_name');
            $table->date('salary_month');
            $table->decimal('basic_salary', 14, 2);
            $table->decimal('additions', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('net_payable', 14, 2);
            $table->string('payment_status', 20)->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'salary_month'], 'salary_entries_user_month_unique');
            $table->index(['salary_month', 'payment_status']);
            $table->index(['payment_status', 'payment_date']);
        });

        $now = now();
        $categories = [
            ['Staff salaries', 'Staff payroll and salary-related operating costs.'],
            ['Premises rent', 'Rent and occupancy charges for business premises.'],
            ['Electricity', 'General electricity expenses.'],
            ['Cold-storage electricity', 'Electricity attributable to freezers and cold storage.'],
            ['Internet and telephone', 'Internet, mobile, and telephone costs.'],
            ['Packaging', 'Packaging material and packing supplies.'],
            ['Local transport', 'Local transport and delivery support costs.'],
            ['Courier and freight', 'Courier, freight, and logistics charges.'],
            ['Vehicle expenses', 'Fuel, tolls, parking, and business vehicle running costs.'],
            ['Repairs and maintenance', 'Repairs and maintenance of premises and equipment.'],
            ['Professional fees', 'Legal, accounting, consulting, and other professional fees.'],
            ['Bank and payment-gateway charges', 'Bank fees and payment-gateway processing charges.'],
            ['Marketing', 'Advertising, promotion, and marketing expenditure.'],
            ['Software and subscriptions', 'Software licences, hosting, and recurring subscriptions.'],
            ['Insurance', 'Business and asset insurance costs.'],
            ['Licences and statutory fees', 'Licences, registrations, and statutory filing charges.'],
            ['Cleaning and sanitation', 'Cleaning materials, pest control, and sanitation services.'],
            ['Other business expenses', 'Other ordinary operating expenses.'],
        ];

        foreach ($categories as $position => [$name, $description]) {
            DB::table('expense_categories')->insert([
                'name' => $name,
                'slug' => Str::slug($name),
                'description' => $description,
                'is_system' => true,
                'is_active' => true,
                'sort_order' => ($position + 1) * 10,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_entries');
        Schema::dropIfExists('salary_profiles');
        Schema::dropIfExists('business_expenses');
        Schema::dropIfExists('recurring_expense_templates');
        Schema::dropIfExists('expense_categories');
    }
};
