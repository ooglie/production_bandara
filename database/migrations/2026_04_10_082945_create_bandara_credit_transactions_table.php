<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bandara_credit_transactions')) {
            return;
        }

        Schema::create('bandara_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('campaign_id')->nullable()->index();
            $table->integer('amount');
            $table->integer('tier_points')->default(0);
            $table->string('type', 40);
            $table->string('status', 20)->default('posted');
            $table->string('idempotency_key', 120)->nullable()->unique();
            $table->json('meta')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->unsignedBigInteger('created_by_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['order_id', 'type']);
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bandara_credit_transactions');
    }
};
