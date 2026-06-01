<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_tag_ticket', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                ->constrained('tickets')
                ->cascadeOnDelete();

            $table->foreignId('ticket_tag_id')
                ->constrained('ticket_tags')
                ->cascadeOnDelete();

            $table->primary(['ticket_id', 'ticket_tag_id'], 'ticket_tag_ticket_pk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_tag_ticket');
    }
};
