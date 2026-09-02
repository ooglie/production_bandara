<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_application_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('b2b_application_id')->constrained('b2b_applications')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_label', 191)->nullable();
            $table->string('event', 60)->index();
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->string('visibility', 20)->default('internal')->index();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['b2b_application_id', 'created_at'], 'b2b_app_history_app_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_application_histories');
    }
};
