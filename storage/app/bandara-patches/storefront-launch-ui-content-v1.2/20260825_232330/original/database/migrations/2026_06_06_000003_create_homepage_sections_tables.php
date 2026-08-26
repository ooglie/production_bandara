<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            Schema::create('home_sections', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('type')->default('content');
                $table->string('eyebrow')->nullable();
                $table->string('title')->nullable();
                $table->text('subtitle')->nullable();
                $table->longText('body')->nullable();
                $table->string('cta_text')->nullable();
                $table->string('cta_url')->nullable();
                $table->string('secondary_cta_text')->nullable();
                $table->string('secondary_cta_url')->nullable();
                $table->string('image_path')->nullable();
                $table->string('mobile_image_path')->nullable();
                $table->string('layout')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('home_section_items')) {
            Schema::create('home_section_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('home_section_id')->constrained('home_sections')->cascadeOnDelete();
                $table->string('item_type')->default('card');
                $table->string('eyebrow')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('image_path')->nullable();
                $table->string('cta_text')->nullable();
                $table->string('cta_url')->nullable();
                $table->string('linked_type')->nullable();
                $table->unsignedBigInteger('linked_id')->nullable();
                $table->json('settings')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['linked_type', 'linked_id']);
            });
        }

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('home_section_items');
        Schema::dropIfExists('home_sections');
    }

    private function seedDefaults(): void
    {
        if (DB::table('home_sections')->exists()) {
            return;
        }

        $now = now();
        $sections = [
            [
                'key' => 'hero',
                'type' => 'hero',
                'eyebrow' => 'Frozen • Bandara by Maytira',
                'title' => 'Frozen favourites for everyday cooking and special gatherings.',
                'subtitle' => 'Shop frozen and chilled products, discover chef-led serving ideas, and order confidently for home or business.',
                'cta_text' => 'Shop all products',
                'cta_url' => '/shop',
                'secondary_cta_text' => 'Browse collections',
                'secondary_cta_url' => '#occasions',
                'image_path' => 'images/home/hero-main.png',
                'settings' => [
                    'overlay_eyebrow' => 'From freezer to table',
                    'overlay_title' => 'Practical frozen products for everyday cooking, entertaining, and repeat ordering.',
                    'fallback_images' => ['images/home/frozen-hero.png', 'images/hero/frozen-hero.png', 'images/home/product-pack.png'],
                ],
                'sort_order' => 10,
            ],
            [
                'key' => 'categories',
                'type' => 'categories',
                'title' => 'Browse by category',
                'cta_text' => 'View all',
                'cta_url' => '/shop',
                'settings' => ['limit' => 8, 'show_counts' => true],
                'sort_order' => 20,
            ],
            [
                'key' => 'shop_highlights',
                'type' => 'product_showcase',
                'eyebrow' => 'Shop highlights',
                'title' => 'Popular frozen picks',
                'subtitle' => 'Featured, new, and special products selected from the active catalogue.',
                'cta_text' => 'Shop all products',
                'cta_url' => '/shop',
                'settings' => ['limit' => 8, 'mode' => 'featured_new_special'],
                'sort_order' => 30,
            ],
            [
                'key' => 'occasions',
                'type' => 'collections',
                'eyebrow' => 'Shop by occasion',
                'title' => 'Curated collections for every plan',
                'subtitle' => 'Use collections to group products around occasions, meal plans, buying moments, or business requirements.',
                'settings' => ['home_section' => 'occasions', 'limit' => 3],
                'sort_order' => 40,
            ],
            [
                'key' => 'chef_picks',
                'type' => 'chef_picks',
                'eyebrow' => 'Chef picks',
                'title' => 'Serving ideas and recipe-led discovery',
                'subtitle' => 'Bring products to life with chef notes, recipe highlights, and practical cooking guidance.',
                'settings' => ['collection_home_section' => 'chef_picks', 'recipe_limit' => 3],
                'sort_order' => 50,
            ],
            [
                'key' => 'trust',
                'type' => 'trust_cards',
                'eyebrow' => 'Shop with confidence',
                'title' => 'Clear information before the order',
                'subtitle' => 'Give customers confidence with clear pricing, practical product details, and support when they need it.',
                'sort_order' => 60,
            ],
            [
                'key' => 'support_cta',
                'type' => 'support_cta',
                'title' => 'Need help before ordering?',
                'subtitle' => 'Get help with product selection, storage guidance, business orders, or support queries.',
                'cta_text' => 'Contact support',
                'cta_url' => '/account/tickets/create',
                'secondary_cta_text' => 'Browse collections',
                'secondary_cta_url' => '#occasions',
                'sort_order' => 70,
            ],
        ];

        $sectionIds = [];
        foreach ($sections as $section) {
            $settings = $section['settings'] ?? null;
            unset($section['settings']);
            $section['settings'] = $settings ? json_encode($settings) : null;
            $section['is_active'] = true;
            $section['created_at'] = $now;
            $section['updated_at'] = $now;
            $sectionIds[$section['key']] = DB::table('home_sections')->insertGetId($section);
        }

        $items = [
            ['hero', 'stat', 'Better discovery', 'Visual product browsing', null, null, ['label' => 'Better discovery'], 10],
            ['hero', 'stat', 'Business ready', 'GST-ready invoices', null, null, ['label' => 'Business ready'], 20],
            ['hero', 'stat', 'Cook with confidence', 'Chef-led inspiration', null, null, ['label' => 'Cook with confidence'], 30],
            ['trust', 'trust', 'GST-ready ordering', 'Invoice-friendly checkout for repeat buying and business-friendly orders.', '🧾', null, ['accent' => 'bg-sky-50 border-sky-100 dark:bg-sky-950/20 dark:border-sky-900/40'], 10],
            ['trust', 'trust', 'Frozen-first experience', 'A storefront built around frozen storage, practical cooking, and easy browsing.', '❄️', null, ['accent' => 'bg-cyan-50 border-cyan-100 dark:bg-cyan-950/20 dark:border-cyan-900/40'], 20],
            ['trust', 'trust', 'Bulk-order friendly', 'Useful for households, events, and repeat larger-volume buying patterns.', '📦', null, ['accent' => 'bg-amber-50 border-amber-100 dark:bg-amber-950/20 dark:border-amber-900/40'], 30],
            ['trust', 'trust', 'Recipe-led discovery', 'Connect products with dishes, serving ideas, and real usage moments.', '👨‍🍳', null, ['accent' => 'bg-rose-50 border-rose-100 dark:bg-rose-950/20 dark:border-rose-900/40'], 40],
        ];

        foreach ($items as [$sectionKey, $itemType, $title, $description, $icon, $image, $settings, $sortOrder]) {
            DB::table('home_section_items')->insert([
                'home_section_id' => $sectionIds[$sectionKey],
                'item_type' => $itemType,
                'title' => $title,
                'description' => $description,
                'icon' => $icon,
                'image_path' => $image,
                'settings' => $settings ? json_encode($settings) : null,
                'is_active' => true,
                'sort_order' => $sortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
