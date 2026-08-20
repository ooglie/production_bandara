<div class="product-label-canvas">
    <div class="label-category-badge">
        <span data-label-field="category" style="font-size: {{ $label['category_font_size'] }}pt; top: {{ $label['category_text_top'] }}pt;">
            {{ $label['category'] }}
        </span>
    </div>

    <div class="label-rule label-rule-top"></div>
    <div class="label-rule label-rule-middle"></div>
    <div class="label-rule label-rule-vertical"></div>
    <div class="label-rule label-rule-bottom"></div>

    <div class="label-country-heading">Country of Origin</div>
    <div class="label-country-value"
         data-label-field="country"
         style="font-size: {{ $label['country_font_size'] }}pt;">
        {{ $label['country'] }}
    </div>

    <div class="label-product-name"
         data-label-field="product_name"
         style="font-size: {{ $label['product_font_size'] }}pt;">
        {{ $label['product_name'] }}
    </div>

    <div class="label-price-line" style="font-size: {{ $label['price_font_size'] }}pt;">
        ₹ <span data-label-field="price">{{ $label['price'] }}</span> | <span data-label-field="unit_label">{{ $label['unit_label'] }}</span>
    </div>

    <img class="label-logo" src="{{ $logoUrl }}" alt="">

    <div class="label-company-name"
         data-label-field="company_name"
         style="font-size: {{ $label['company_font_size'] }}pt;">
        {{ $label['company_name'] }}
    </div>
    <div class="label-fssai">Fssai # <span data-label-field="fssai">{{ $label['fssai'] }}</span></div>

    <div class="label-best-before">
        Best before :: <span data-label-field="best_before_label">{{ $label['best_before_label'] }}</span>
    </div>
    <div class="label-website"
         data-label-field="website"
         style="font-size: {{ $label['website_font_size'] }}pt;">
        {{ $label['website'] }}
    </div>
</div>
