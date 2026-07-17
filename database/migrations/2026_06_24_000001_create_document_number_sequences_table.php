<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_key', 80);
            $table->string('period', 6);
            $table->unsignedInteger('last_number')->default(100);
            $table->timestamps();

            $table->unique(['sequence_key', 'period'], 'doc_num_seq_key_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_number_sequences');
    }
};
