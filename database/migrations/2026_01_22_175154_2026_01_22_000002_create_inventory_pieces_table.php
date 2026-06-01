<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_pieces', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_lot_id')
                ->constrained('inventory_lots')
                ->cascadeOnDelete();

            // Piece sequence number inside this lot (1..N)
            $table->unsignedInteger('piece_no')->default(1);

            // Weight per piece (kg)
            $table->decimal('weight_kg', 10, 3)->nullable();

            // for future: available/reserved/consumed
            $table->string('status', 20)->default('available');

            $table->timestamps();

            $table->index(['inventory_lot_id', 'piece_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_pieces');
    }
};