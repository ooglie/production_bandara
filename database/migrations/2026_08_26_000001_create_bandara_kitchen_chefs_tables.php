<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // These names are owned by this module. Never adopt an unrelated or
        // partially created table: fail before changing either schema object.
        if (Schema::hasTable('chefs') || Schema::hasTable('chef_recipe')) {
            throw new RuntimeException(
                "Bandara Kitchen cannot create its schema because 'chefs' or 'chef_recipe' already exists without this migration being recorded. No table was changed."
            );
        }

        $createdChefs = false;
        $createdPivot = false;

        try {
            Schema::create('chefs', function (Blueprint $table): void {
                $table->id();

                // Public identity and professional profile.
                $table->string('display_name', 150);
                $table->string('slug', 180)->unique();
                $table->string('professional_title', 180);
                $table->string('organisation_name', 200)->nullable();
                $table->string('city', 120);
                $table->string('country', 120)->nullable();
                $table->unsignedSmallInteger('years_experience')->nullable();
                $table->text('short_intro')->nullable();
                $table->longText('biography')->nullable();
                $table->text('cooking_philosophy')->nullable();
                $table->text('quote')->nullable();
                $table->json('specialties')->nullable();
                $table->text('signature_dishes')->nullable();
                $table->text('culinary_training')->nullable();
                $table->text('career_highlights')->nullable();
                $table->text('awards')->nullable();
                $table->json('qa')->nullable();

                // Public media and links.
                $table->string('portrait_image_path')->nullable();
                $table->string('hero_image_path')->nullable();
                $table->json('gallery_image_paths')->nullable();
                $table->string('photographer_credit', 200)->nullable();
                $table->string('website_url')->nullable();
                $table->string('instagram_url')->nullable();
                $table->string('linkedin_url')->nullable();

                // Private contact and editorial administration.
                $table->string('legal_name', 180)->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone', 40)->nullable();
                $table->string('contact_person_name', 180)->nullable();
                $table->string('preferred_contact_method', 40)->nullable();
                $table->string('best_contact_time', 120)->nullable();
                $table->text('internal_notes')->nullable();

                // Rights and approval record.
                $table->boolean('image_rights_confirmed')->default(false);
                $table->boolean('profile_use_approved')->default(false);
                $table->boolean('restaurant_mention_approved')->default(false);
                $table->boolean('recipe_use_approved')->default(false);
                $table->boolean('social_promotion_approved')->default(false);
                $table->timestamp('content_approved_at')->nullable();
                $table->text('approval_notes')->nullable();

                // Editorial workflow and manual homepage selection.
                $table->string('status', 30)->default('draft')->index();
                $table->boolean('is_featured')->default(false)->index();
                // NULL for every normal row; 1 for the single manually featured
                // row. The unique nullable slot is a database-level one-Chef
                // invariant even when two staff requests arrive together.
                $table->unsignedTinyInteger('homepage_feature_slot')->nullable()->unique();
                $table->foreignId('featured_recipe_id')
                    ->nullable()
                    ->constrained('recipes')
                    ->nullOnDelete();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
            $createdChefs = true;

            Schema::create('chef_recipe', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('chef_id')->constrained('chefs')->cascadeOnDelete();
                $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['chef_id', 'recipe_id']);
                $table->index(['chef_id', 'sort_order']);
            });
            $createdPivot = true;
        } catch (Throwable $exception) {
            if ($createdPivot) {
                Schema::dropIfExists('chef_recipe');
            }
            if ($createdChefs) {
                Schema::dropIfExists('chefs');
            }

            throw $exception;
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chef_recipe');
        Schema::dropIfExists('chefs');
    }
};
