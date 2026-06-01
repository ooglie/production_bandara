<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();

            // India-only for now, but keep the column so you can extend later if needed.
            $table->char('country_code', 2)->default('IN');

            // 2–3 chars is enough for India codes (MH, DL, etc.)
            $table->string('code', 8);
            $table->string('name');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['country_code', 'code']);
            $table->index(['country_code', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
