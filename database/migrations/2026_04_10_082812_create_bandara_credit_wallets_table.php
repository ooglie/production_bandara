<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bandara_credit_wallets')) {
            return;
        }

        Schema::create('bandara_credit_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('balance')->default(0);
            $table->string('tier', 20)->default('silver');
            $table->timestamps();

            $table->index(['tier', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bandara_credit_wallets');
    }
};
