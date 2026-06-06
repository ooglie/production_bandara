<script>
(function () {
    function setVariant(card, variantId) {
        card.querySelectorAll('input.js-variant-input').forEach(inp => inp.value = variantId || '');
        const btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = !variantId || btn.hasAttribute('data-force-disabled');
            btn.classList.toggle('opacity-40', btn.disabled);
        }
    }

    async function hydrateCard(card) {
        const sel = card.querySelector('select.js-variant-select');
        if (!sel) return;

        const url = sel.dataset.url;
        const hint = card.querySelector('.js-variant-hint');

        sel.innerHTML = '<option value="">Loading…</option>';
        sel.disabled = true;

        const btn = card.querySelector('form.js-cart-form button.js-cart-btn');
        if (btn) {
            btn.disabled = true;
            btn.classList.add('opacity-40');
        }

        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('bad');
            const data = await res.json();
            const list = Array.isArray(data.variants) ? data.variants : [];

            sel.innerHTML = '<option value="">Choose option</option>';

            if (!list.length) {
                sel.innerHTML = '<option value="">No slabs available</option>';
                if (hint) hint.textContent = 'No available slabs in stock.';
                sel.disabled = true;
                setVariant(card, '');
                return;
            }

            list.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.id;
                opt.textContent = v.label || ('Slab #' + v.id);
                sel.appendChild(opt);
            });

            sel.disabled = false;
            if (hint) hint.textContent = 'Select & add to cart.';
        } catch (e) {
            sel.innerHTML = '<option value="">Unable to load slabs</option>';
            sel.disabled = true;
            if (hint) hint.textContent = 'Could not load slab list.';
            setVariant(card, '');
            return;
        }

        sel.addEventListener('change', () => setVariant(card, sel.value));
    }

    document.querySelectorAll('.js-product-card').forEach(hydrateCard);
})();
</script>
