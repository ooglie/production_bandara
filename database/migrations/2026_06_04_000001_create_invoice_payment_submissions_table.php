<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_payment_submissions')) {
            return;
        }

        Schema::create('invoice_payment_submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('method', 32)->index(); // bank_transfer, upi, cheque, cash, other
            $table->string('status', 32)->default('pending')->index();

            $table->string('reference')->nullable(); // UTR, UPI ref, cash receipt no, etc.
            $table->date('paid_on')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();

            $table->string('cheque_number')->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('cheque_bank_name')->nullable();
            $table->string('cheque_branch_name')->nullable();

            $table->string('proof_path')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            $table->foreignId('approved_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->index(['invoice_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_submissions');
    }
};
