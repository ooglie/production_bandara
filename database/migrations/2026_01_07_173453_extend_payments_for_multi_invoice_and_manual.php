<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Allow payments not tied to a single order (for pure AR payments)
            $table->foreignId('order_id')->nullable()->change();

            // Link to customer (optional but very useful)
            $table->foreignId('user_id')
                ->nullable()
                ->after('order_id')
                ->constrained('users')
                ->nullOnDelete();

            // Generic reference ID (UTR, cheque no, Razorpay payment ID, etc.)
            $table->string('reference')
                ->nullable()
                ->after('method');

            // When money is actually received / cleared
            $table->date('received_date')
                ->nullable()
                ->after('payment_data');

            // Free text notes
            $table->text('notes')
                ->nullable()
                ->after('received_date');

            // Who recorded / confirmed this payment (admin, accountant, etc.)
            $table->foreignId('recorded_by_id')
                ->nullable()
                ->after('notes')
                ->constrained('users')
                ->nullOnDelete();

            // Cheque‑specific fields
            $table->string('cheque_number')
                ->nullable()
                ->after('recorded_by_id');

            $table->date('cheque_date')
                ->nullable()
                ->after('cheque_number');

            $table->string('cheque_bank_name')
                ->nullable()
                ->after('cheque_date');

            $table->string('cheque_branch_name')
                ->nullable()
                ->after('cheque_bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['recorded_by_id']);

            $table->dropColumn([
                'user_id',
                'reference',
                'received_date',
                'notes',
                'recorded_by_id',
                'cheque_number',
                'cheque_date',
                'cheque_bank_name',
                'cheque_branch_name',
            ]);

            // Optionally revert order_id to NOT NULL if needed
            // $table->foreignId('order_id')->nullable(false)->change();
        });
    }
};
