<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->string('invoice_number')->unique();

            $table->enum('status', ['pending', 'due', 'past_due', 'paid'])
                ->default('pending');

            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);

            $table->string('pdf_path')->nullable();

            $table->timestamp('mailed_to_customer_at')->nullable();
            $table->timestamp('mailed_to_accountant_at')->nullable();

            $table->string('tally_reference')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
