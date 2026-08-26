<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('home_sections')) {
            return;
        }

        $this->clearSeededSectionCopy();
        $this->removeSeededPlaceholderItems();
    }

    public function down(): void
    {
        // Intentionally no-op. This migration removes only exact seeded placeholder
        // copy/items so the admin UI starts clean; frontend fallbacks remain in code.
    }

    private function clearSeededSectionCopy(): void
    {
        $defaults = [
            'hero' => [
                'eyebrow' => 'Frozen • Bandara by Maytira',
                'title' => 'Frozen favourites for everyday cooking and special gatherings.',
                'subtitle' => 'Shop frozen and chilled products, discover chef-led serving ideas, and order confidently for home or business.',
                'cta_text' => 'Shop all products',
                'cta_url' => '/shop',
                'secondary_cta_text' => 'Browse collections',
                'secondary_cta_url' => '#occasions',
                'image_path' => 'images/home/hero-main.png',
            ],
            'categories' => [
                'title' => 'Browse by category',
                'cta_text' => 'View all',
                'cta_url' => '/shop',
            ],
            'shop_highlights' => [
                'eyebrow' => 'Shop highlights',
                'title' => 'Popular frozen picks',
                'subtitle' => 'Featured, new, and special products selected from the active catalogue.',
                'cta_text' => 'Shop all products',
                'cta_url' => '/shop',
            ],
            'occasions' => [
                'eyebrow' => 'Shop by occasion',
                'title' => 'Curated collections for every plan',
                'subtitle' => 'Use collections to group products around occasions, meal plans, buying moments, or business requirements.',
            ],
            'chef_picks' => [
                'eyebrow' => 'Chef picks',
                'title' => 'Serving ideas and recipe-led discovery',
                'subtitle' => 'Bring products to life with chef notes, recipe highlights, and practical cooking guidance.',
            ],
            'trust' => [
                'eyebrow' => 'Shop with confidence',
                'title' => 'Clear information before the order',
                'subtitle' => 'Give customers confidence with clear pricing, practical product details, and support when they need it.',
            ],
            'support_cta' => [
                'title' => 'Need help before ordering?',
                'subtitle' => 'Get help with product selection, storage guidance, business orders, or support queries.',
                'cta_text' => 'Contact support',
                'cta_url' => '/account/tickets/create',
                'secondary_cta_text' => 'Browse collections',
                'secondary_cta_url' => '#occasions',
            ],
        ];

        foreach ($defaults as $key => $fields) {
            $section = DB::table('home_sections')->where('key', $key)->first();

            if (! $section) {
                continue;
            }

            $updates = [];
            foreach ($fields as $field => $defaultValue) {
                if (($section->{$field} ?? null) === $defaultValue) {
                    $updates[$field] = null;
                }
            }

            if ($key === 'hero') {
                $defaultSettings = json_encode([
                    'overlay_eyebrow' => 'From freezer to table',
                    'overlay_title' => 'Practical frozen products for everyday cooking, entertaining, and repeat ordering.',
                    'fallback_images' => ['images/home/frozen-hero.png', 'images/hero/frozen-hero.png', 'images/home/product-pack.png'],
                ]);

                if (($section->settings ?? null) === $defaultSettings) {
                    $updates['settings'] = null;
                }
            }

            if ($key === 'occasions') {
                $settings = json_decode((string) ($section->settings ?? ''), true);
                if (is_array($settings) && ($settings['home_section'] ?? null) === 'occasions' && (int) ($settings['limit'] ?? 0) === 3) {
                    $settings['limit'] = 6;
                    $updates['settings'] = json_encode($settings);
                }
            }

            if ($updates !== []) {
                $updates['updated_at'] = now();
                DB::table('home_sections')->where('id', $section->id)->update($updates);
            }
        }
    }

    private function removeSeededPlaceholderItems(): void
    {
        if (! Schema::hasTable('home_section_items')) {
            return;
        }

        $defaults = [
            ['hero', 'stat', 'Better discovery', 'Visual product browsing', null],
            ['hero', 'stat', 'Business ready', 'GST-ready invoices', null],
            ['hero', 'stat', 'Cook with confidence', 'Chef-led inspiration', null],
            ['trust', 'trust', 'GST-ready ordering', 'Invoice-friendly checkout for repeat buying and business-friendly orders.', '🧾'],
            ['trust', 'trust', 'Frozen-first experience', 'A storefront built around frozen storage, practical cooking, and easy browsing.', '❄️'],
            ['trust', 'trust', 'Bulk-order friendly', 'Useful for households, events, and repeat larger-volume buying patterns.', '📦'],
            ['trust', 'trust', 'Recipe-led discovery', 'Connect products with dishes, serving ideas, and real usage moments.', '👨‍🍳'],
        ];

        foreach ($defaults as [$sectionKey, $itemType, $title, $description, $icon]) {
            $sectionId = DB::table('home_sections')->where('key', $sectionKey)->value('id');

            if (! $sectionId) {
                continue;
            }

            $query = DB::table('home_section_items')
                ->where('home_section_id', $sectionId)
                ->where('item_type', $itemType)
                ->where('title', $title)
                ->where('description', $description)
                ->whereNull('linked_type')
                ->whereNull('linked_id');

            $icon === null ? $query->whereNull('icon') : $query->where('icon', $icon);

            $query->delete();
        }
    }
};
