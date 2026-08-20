<div class="product-label-canvas">
    <div class="label-category-badge">
        <span data-label-field="category" style="font-size: <?php echo e($label['category_font_size']); ?>pt; top: <?php echo e($label['category_text_top']); ?>pt;">
            <?php echo e($label['category']); ?>

        </span>
    </div>

    <div class="label-rule label-rule-top"></div>
    <div class="label-rule label-rule-middle"></div>
    <div class="label-rule label-rule-vertical"></div>
    <div class="label-rule label-rule-bottom"></div>

    <div class="label-country-heading">Country of Origin</div>
    <div class="label-country-value"
         data-label-field="country"
         style="font-size: <?php echo e($label['country_font_size']); ?>pt;">
        <?php echo e($label['country']); ?>

    </div>

    <div class="label-product-name"
         data-label-field="product_name"
         style="font-size: <?php echo e($label['product_font_size']); ?>pt;">
        <?php echo e($label['product_name']); ?>

    </div>

    <div class="label-price-line" style="font-size: <?php echo e($label['price_font_size']); ?>pt;">
        ₹ <span data-label-field="price"><?php echo e($label['price']); ?></span> | <span data-label-field="unit_label"><?php echo e($label['unit_label']); ?></span>
    </div>

    <img class="label-logo" src="<?php echo e($logoUrl); ?>" alt="">

    <div class="label-company-name"
         data-label-field="company_name"
         style="font-size: <?php echo e($label['company_font_size']); ?>pt;">
        <?php echo e($label['company_name']); ?>

    </div>
    <div class="label-fssai">Fssai # <span data-label-field="fssai"><?php echo e($label['fssai']); ?></span></div>

    <div class="label-best-before">
        Best before :: <span data-label-field="best_before_label"><?php echo e($label['best_before_label']); ?></span>
    </div>
    <div class="label-website"
         data-label-field="website"
         style="font-size: <?php echo e($label['website_font_size']); ?>pt;">
        <?php echo e($label['website']); ?>

    </div>
</div>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/labels/_canvas.blade.php ENDPATH**/ ?>