<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_reservations')) {
            return;
        }

        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->index();
            $table->foreignId('order_item_id')->nullable()->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->foreignId('product_id')->nullable()->index();
            $table->foreignId('product_variant_id')->nullable()->index();
            $table->foreignId('inventory_piece_id')->nullable()->index();
            $table->foreignId('inventory_pack_id')->nullable()->index();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('weight_kg', 12, 3)->nullable();
            $table->string('status', 20)->default('reserved')->index();
            $table->timestamp('reserved_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at'], 'stock_res_status_expires_idx');
            $table->index(['product_id', 'product_variant_id', 'status', 'expires_at'], 'stock_res_product_status_idx');
            $table->index(['inventory_piece_id', 'status', 'expires_at'], 'stock_res_piece_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
