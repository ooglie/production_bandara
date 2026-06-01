<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hsn_codes', function (Blueprint $table) {
            $table->id();

            $table->string('code', 32)->unique();      // e.g. 0402
            $table->decimal('gst_rate', 5, 2)->default(0); // 0 / 5 / 12 / etc

            $table->string('name')->nullable();        // optional label
            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hsn_codes');
    }
};
