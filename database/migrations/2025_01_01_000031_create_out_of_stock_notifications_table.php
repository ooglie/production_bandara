<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('out_of_stock_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
                ->constrained('out_of_stock_subscriptions')
                ->cascadeOnDelete();

            $table->timestamp('sent_at')->useCurrent();

            $table->foreignId('sent_by_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('channel')->default('email');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('out_of_stock_notifications');
    }
};
