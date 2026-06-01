<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('label')->nullable();          // "Festive Wish", "Special Offer"
            $table->text('message')->nullable();
            $table->string('type')->default('info');      // info, special, festive
            $table->string('icon')->nullable();           // emoji or short text
            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('secondary_text')->nullable();
            $table->string('secondary_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(true);
            $table->boolean('is_dismissible')->default(true);
            $table->unsignedInteger('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
