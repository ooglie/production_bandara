<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_collections', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            // general, occasion, chef, seasonal, festive, campaign
            $table->string('kind')->default('general');

            $table->string('eyebrow')->nullable();
            $table->text('description')->nullable();

            $table->string('image_path')->nullable();

            $table->string('cta_text')->nullable();
            $table->string('cta_url')->nullable();

            // phase 1: manual only, future-proof for rule-based
            $table->string('selection_mode')->default('manual'); // manual, rule
            $table->json('rules')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(false);

            // occasions, chef_picks, seasonal, etc.
            $table->string('home_section')->nullable();
            $table->unsignedInteger('home_order')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_collections');
    }
};