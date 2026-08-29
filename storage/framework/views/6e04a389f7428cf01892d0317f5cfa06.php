<?php $__env->startSection('title', config('app.name') . ' - Home'); ?>

<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('home.partials.phase-one-enhancements', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php
    use Illuminate\Support\Facades\Route;

    $has = fn(string $r) => Route::has($r);
    $shopUrl = $has('shop.index') ? route('shop.index') : '#';
    $supportUrl = $has('tickets.create') ? route('tickets.create') : null;

    $cartAddUrl = $has('cart.add') ? route('cart.add') : ($has('cart.store') ? route('cart.store') : null);
    $wishlistToggleUrl = $has('wishlist.store') ? route('wishlist.store') : null;
    $wishlistUrl = $has('wishlist.index') ? route('wishlist.index') : null;
    $loginUrl = $has('login') ? route('login') : null;

    $flagEmoji = function (?string $code) {
        if (!function_exists('mb_convert_encoding')) return null;
        $code = strtoupper(trim((string) $code));
        if (!preg_match('/^[A-Z]{2}$/', $code)) return null;
        $a = 127397 + ord($code[0]);
        $b = 127397 + ord($code[1]);
        return mb_convert_encoding("&#{$a};&#{$b};", 'UTF-8', 'HTML-ENTITIES');
    };

    $categoryUrl = function ($category) use ($has) {
        return $has('shop.index') ? route('shop.index', ['category' => $category->id]) : '#?category=' . $category->id;
    };

    $productUrl = function ($product) use ($has) {
        if ($has('product.show')) return route('product.show', $product->slug ?? $product);
        return '#';
    };

    $collectionUrl = function ($collection) use ($has, $shopUrl) {
        if ($has('collections.show') && filled($collection->slug ?? null)) {
            return route('collections.show', ['collection' => $collection->slug]);
        }
        return filled($collection->cta_url ?? null) ? $collection->cta_url : $shopUrl;
    };

    $resolveMediaUrl = $mediaUrlResolver ?? fn($pathOrPaths) => null;

    $recipeText = function ($recipe, $field) {
        if (method_exists($recipe, 'tr')) return $recipe->tr($field);
        $value = $recipe->{$field} ?? null;
        if (is_array($value)) return $value[app()->getLocale()] ?? $value['en'] ?? (count($value) ? reset($value) : null);
        return $value;
    };

    $recipeList = function ($recipe, $field) {
        if (method_exists($recipe, 'trList')) return $recipe->trList($field);
        $value = $recipe->{$field} ?? [];
        if (!is_array($value)) return [];
        if (isset($value[app()->getLocale()]) && is_array($value[app()->getLocale()])) return $value[app()->getLocale()];
        if (isset($value['en']) && is_array($value['en'])) return $value['en'];
        return array_values($value);
    };
?>

<div class="bg-gray-50 dark:bg-gray-950 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-6 space-y-6">
        <?php if($homeAnnouncement): ?>
            <?php echo $__env->make('partials.home_cards.announcement_banner', ['announcement' => $homeAnnouncement], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?>

        <?php $__currentLoopData = $homeSections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div id="home-section-<?php echo e($section->key); ?>" class="scroll-mt-24">
                <?php switch($section->type):
                    case ('hero'): ?>
                        <?php echo $__env->make('home.sections.hero', ['section' => $section], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                    <?php case ('categories'): ?>
                        <?php echo $__env->make('home.sections.categories', ['section' => $section], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                    <?php case ('product_showcase'): ?>
                        <?php echo $__env->make('home.sections.product-showcase', ['section' => $section], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                    <?php case ('collections'): ?>
                        <?php echo $__env->make('home.sections.collections', ['section' => $section], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                    <?php case ('chef_picks'): ?>
                        <?php echo $__env->make('home.sections.chef-picks', ['section' => $section], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>

                    <?php case ('support_cta'): ?>
                        <?php echo $__env->make('home.sections.support-cta', ['section' => $section], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php break; ?>
                <?php endswitch; ?>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<?php if(Route::has('product.variants.options')): ?>
    <?php echo $__env->make('home.sections.product-card-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.customer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/home.blade.php ENDPATH**/ ?>