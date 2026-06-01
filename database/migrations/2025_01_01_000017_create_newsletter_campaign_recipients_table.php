<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_campaign_recipients', function (Blueprint $table) {
            $table->id();

            $table->foreignId('campaign_id')
                ->constrained('newsletter_campaigns')
                ->cascadeOnDelete();

            $table->foreignId('subscriber_id')
                ->constrained('newsletter_subscribers')
                ->cascadeOnDelete();

            $table->timestamp('sent_at')->nullable();
            $table->integer('open_count')->default(0);
            $table->timestamp('last_opened_at')->nullable();

            $table->string('bounce_status')->nullable();
            $table->string('unsubscribe_token')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_campaign_recipients');
    }
};
