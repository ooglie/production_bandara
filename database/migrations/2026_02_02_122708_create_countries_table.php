<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary();     // ISO 3166-1 alpha-2
            $table->string('name', 191);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
