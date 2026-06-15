<script>
(function () {
    if (window.__bandaraProductCardScriptsBound) {
        return;
    }

    window.__bandaraProductCardScriptsBound = true;

    function formatINR(amount) {
        var value = Number(amount || 0);
        return '₹' + value.toLocaleString('en-IN', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function setVariant(card, variantId) {
        card.querySelectorAll('input.js-variant-input').forEach(function (inp) {
            inp.value = variantId || '';
        });

        var btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = !variantId || btn.hasAttribute('data-force-disabled');
            btn.classList.toggle('opacity-40', btn.disabled);
        }
    }

    function hiddenInput(name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : String(value);
        return input;
    }

    function closeOpenMenus(exceptMenu) {
        document.querySelectorAll('details.js-card-option-menu[open]').forEach(function (menu) {
            if (menu !== exceptMenu) {
                menu.removeAttribute('open');
            }
        });
    }

    function optionCountText(count) {
        return count === 1 ? '1 pack option' : count + ' pack options';
    }

    function updateVariantSummary(card, variants) {
        var priceSummary = card.querySelector('.js-variant-price-summary');
        var countSummary = card.querySelector('.js-variant-count-summary');

        if (!variants.length) {
            if (priceSummary) priceSummary.textContent = 'Out of stock';
            if (countSummary) countSummary.textContent = 'No pack options available';
            return;
        }

        var prices = variants
            .map(function (variant) { return Number(variant.price || 0); })
            .filter(function (price) { return price > 0; });

        if (priceSummary) {
            priceSummary.textContent = prices.length ? 'From ' + formatINR(Math.min.apply(Math, prices)) : 'Choose pack';
        }

        if (countSummary) {
            countSummary.textContent = optionCountText(variants.length);
        }
    }

    function buildVariantRow(menu, variant) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = menu.dataset.cartUrl || '#';
        form.className = 'block';

        form.appendChild(hiddenInput('_token', menu.dataset.csrf || ''));
        form.appendChild(hiddenInput('product_id', menu.dataset.productId || ''));
        form.appendChild(hiddenInput('product_variant_id', variant.id || ''));
        form.appendChild(hiddenInput('quantity', '1'));

        var button = document.createElement('button');
        button.type = 'submit';
        button.className = 'flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-800';

        var labelWrap = document.createElement('span');
        labelWrap.className = 'min-w-0';

        var name = document.createElement('span');
        name.className = 'block truncate text-[12px] font-medium text-gray-900 dark:text-gray-50';
        name.textContent = variant.name || variant.label || ('Option #' + (variant.id || ''));

        var sub = document.createElement('span');
        sub.className = 'mt-0.5 block text-[10px] text-gray-500 dark:text-gray-400';
        sub.textContent = variant.stock_label || 'Add 1 pack';

        var price = document.createElement('span');
        price.className = 'shrink-0 text-[12px] font-semibold text-gray-900 dark:text-gray-50';
        price.textContent = variant.price_label || formatINR(variant.price || 0);

        labelWrap.appendChild(name);
        labelWrap.appendChild(sub);
        button.appendChild(labelWrap);
        button.appendChild(price);
        form.appendChild(button);

        return form;
    }

    async function hydrateVariantMenu(menu) {
        if (!menu || menu.dataset.loaded === 'true') {
            return;
        }

        var body = menu.querySelector('.js-variant-options-menu');
        var card = menu.closest('.js-product-card');
        var url = menu.dataset.url;

        if (!body || !url) {
            return;
        }

        body.innerHTML = '<div class="rounded-lg px-3 py-2 text-[11px] text-gray-500 dark:text-gray-400">Loading options…</div>';

        try {
            var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load variants');

            var data = await res.json();
            var variants = Array.isArray(data.variants) ? data.variants : [];

            menu.dataset.loaded = 'true';
            body.innerHTML = '';
            updateVariantSummary(card || document, variants);

            if (!variants.length) {
                body.innerHTML = '<div class="rounded-lg px-3 py-2 text-[11px] text-gray-500 dark:text-gray-400">No pack options available.</div>';
                return;
            }

            variants.forEach(function (variant) {
                body.appendChild(buildVariantRow(menu, variant));
            });
        } catch (e) {
            body.innerHTML = '<div class="rounded-lg px-3 py-2 text-[11px] text-red-600 dark:text-red-300">Could not load pack options.</div>';
            updateVariantSummary(card || document, []);
        }
    }

    async function hydrateLegacyVariantSelect(card) {
        var sel = card.querySelector('select.js-variant-select');
        if (!sel) return;

        var url = sel.dataset.url;
        var hint = card.querySelector('.js-variant-hint');

        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled = true;

        var btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-40');
        }

        try {
            var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Unable to load variants');
            var data = await res.json();
            var list = Array.isArray(data.variants) ? data.variants : [];

            sel.innerHTML = '<option value="">Choose option</option>';

            if (!list.length) {
                sel.innerHTML = '<option value="">No options available</option>';
                if (hint) hint.textContent = 'No available options in stock.';
                sel.disabled = true;
                setVariant(card, '');
                return;
            }

            list.forEach(function (v) {
                var opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label || v.name || ('Option #' + v.id);
                sel.appendChild(opt);
            });

            sel.disabled = false;
            if (hint) hint.textContent = 'Select & add to cart.';
        } catch (e) {
            sel.innerHTML = '<option value="">Unable to load options</option>';
            sel.disabled = true;
            if (hint) hint.textContent = 'Could not load option list.';
            setVariant(card, '');
            return;
        }

        sel.addEventListener('change', function () {
            setVariant(card, sel.value);
        });
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!target) return;

        document.querySelectorAll('details.js-card-option-menu[open]').forEach(function (menu) {
            if (!menu.contains(target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.addEventListener('toggle', function (event) {
        var menu = event.target;
        if (!menu || menu.tagName !== 'DETAILS' || !menu.classList || !menu.classList.contains('js-card-option-menu') || !menu.open) {
            return;
        }

        closeOpenMenus(menu);

        if (menu.classList.contains('js-variant-option-menu')) {
            hydrateVariantMenu(menu);
        }
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeOpenMenus(null);
        }
    });

    document.querySelectorAll('.js-product-card').forEach(function (card) {
        hydrateLegacyVariantSelect(card);

        var variantMenu = card.querySelector('details.js-variant-option-menu');
        if (variantMenu) {
            hydrateVariantMenu(variantMenu);
        }
    });
})();
</script>
