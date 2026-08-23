<?php if (! $__env->hasRenderedOnce('357d4f18-7970-4cf7-95f6-d4fe64cc03bd')): $__env->markAsRenderedOnce('357d4f18-7970-4cf7-95f6-d4fe64cc03bd'); ?>
    <style id="bandara-storefront-ui-refinement-v3-styles">
        /*
         * Bandara storefront UI refinement v3
         * - Compact, genuinely right-aligned desktop header
         * - Pronounced neutral elevation for product cards on home and shop
         * - No new brand colours
         */

        :is(
            [data-bandara-storefront-product-card],
            [data-bandara-phase1-product-card],
            .product-card
        ) {
            position: relative !important;
            isolation: isolate;
            translate: 0 0;
            transition:
                translate 230ms cubic-bezier(.2, .75, .25, 1),
                box-shadow 230ms ease !important;
            will-change: translate, box-shadow;
        }

        :is(
            [data-bandara-storefront-product-image],
            [data-bandara-phase1-product-image]
        ) {
            transform: scale(1);
            transform-origin: center;
            transition: transform 360ms cubic-bezier(.2, .75, .25, 1) !important;
            will-change: transform;
        }

        :is(
            [data-bandara-storefront-product-card],
            [data-bandara-phase1-product-card],
            .product-card
        ):focus-within {
            z-index: 8 !important;
            translate: 0 -3px !important;
            box-shadow:
                0 22px 46px -22px rgb(15 23 42 / .34),
                0 9px 22px -15px rgb(15 23 42 / .24) !important;
        }

        .dark :is(
            [data-bandara-storefront-product-card],
            [data-bandara-phase1-product-card],
            .product-card
        ):focus-within {
            box-shadow:
                0 26px 52px -22px rgb(0 0 0 / .72),
                0 10px 24px -15px rgb(0 0 0 / .58) !important;
        }

        @media (hover: hover) and (pointer: fine) {
            :is(
                [data-bandara-storefront-product-card],
                [data-bandara-phase1-product-card],
                .product-card
            ):hover {
                z-index: 10 !important;
                translate: 0 -7px !important;
                box-shadow:
                    0 30px 62px -24px rgb(15 23 42 / .46),
                    0 14px 30px -17px rgb(15 23 42 / .34) !important;
            }

            .dark :is(
                [data-bandara-storefront-product-card],
                [data-bandara-phase1-product-card],
                .product-card
            ):hover {
                box-shadow:
                    0 34px 68px -24px rgb(0 0 0 / .90),
                    0 16px 34px -17px rgb(0 0 0 / .76) !important;
            }

            :is(
                [data-bandara-storefront-product-card],
                [data-bandara-phase1-product-card],
                .product-card
            ):hover :is(
                [data-bandara-storefront-product-image],
                [data-bandara-phase1-product-image]
            ) {
                transform: scale(1.03) !important;
            }
        }

        @supports not (translate: 0 -1px) {
            :is(
                [data-bandara-storefront-product-card],
                [data-bandara-phase1-product-card],
                .product-card
            ):focus-within {
                transform: translateY(-3px) !important;
            }

            @media (hover: hover) and (pointer: fine) {
                :is(
                    [data-bandara-storefront-product-card],
                    [data-bandara-phase1-product-card],
                    .product-card
                ):hover {
                    transform: translateY(-7px) !important;
                }
            }
        }

        @media (min-width: 1024px) {
            [data-bandara-compact-header-row] {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: .375rem !important;
                width: 100% !important;
            }

            [data-bandara-header-brand-branch] {
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 0 !important;
                margin-inline-end: auto !important;
            }

            [data-bandara-header-utility-branch] {
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: none !important;
                margin: 0 !important;
            }

            [data-bandara-header-right-group] {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: none !important;
                gap: .375rem !important;
                margin: 0 !important;
            }

            [data-bandara-header-nav] {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: none !important;
                gap: 0 !important;
                margin: 0 !important;
            }

            [data-bandara-header-nav] > :not([hidden]) ~ :not([hidden]) {
                margin-inline-start: 0 !important;
            }

            [data-bandara-header-nav] a,
            [data-bandara-header-nav] button {
                padding-inline: .5rem !important;
            }

            [data-bandara-header-search-branch],
            [data-bandara-header-search-form] {
                flex: 0 0 13.5rem !important;
                width: 13.5rem !important;
                min-width: 13.5rem !important;
                max-width: 13.5rem !important;
                margin: 0 0 0 .25rem !important;
            }

            [data-bandara-header-search-form] input[type="search"],
            [data-bandara-header-search-form] input[name="q"] {
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
            }

            [data-bandara-header-actions] {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
                flex: 0 0 auto !important;
                width: auto !important;
                min-width: 0 !important;
                max-width: none !important;
                gap: .375rem !important;
                margin: 0 0 0 .25rem !important;
            }

            [data-bandara-header-actions] > :not([hidden]) ~ :not([hidden]) {
                margin-inline-start: 0 !important;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            :is(
                [data-bandara-storefront-product-card],
                [data-bandara-phase1-product-card],
                .product-card
            ),
            :is(
                [data-bandara-storefront-product-image],
                [data-bandara-phase1-product-image]
            ) {
                transition-duration: .01ms !important;
                translate: 0 0 !important;
                transform: none !important;
            }
        }
    </style>

    <script id="bandara-storefront-ui-refinement-v3-script">
        (() => {
            'use strict';

            const CARD_ATTRIBUTE = 'data-bandara-storefront-product-card';
            const IMAGE_ATTRIBUTE = 'data-bandara-storefront-product-image';

            const isVisible = (element) => {
                if (!(element instanceof HTMLElement)) {
                    return false;
                }

                const style = window.getComputedStyle(element);
                const rect = element.getBoundingClientRect();

                return style.display !== 'none'
                    && style.visibility !== 'hidden'
                    && rect.width > 0
                    && rect.height > 0;
            };

            const lowestCommonAncestor = (elements, boundary) => {
                const usable = elements.filter((element) => element instanceof Element);

                if (!usable.length) {
                    return null;
                }

                let candidate = usable[0];

                while (candidate && candidate !== boundary?.parentElement) {
                    if (usable.every((element) => candidate.contains(element))) {
                        return candidate;
                    }

                    candidate = candidate.parentElement;
                }

                return boundary || null;
            };

            const branchWithin = (ancestor, descendant) => {
                let node = descendant;

                while (node?.parentElement && node.parentElement !== ancestor) {
                    node = node.parentElement;
                }

                return node?.parentElement === ancestor ? node : null;
            };

            const clearHeaderMarkers = (header) => {
                header.querySelectorAll([
                    '[data-bandara-compact-header-row]',
                    '[data-bandara-header-brand-branch]',
                    '[data-bandara-header-utility-branch]',
                    '[data-bandara-header-right-group]',
                    '[data-bandara-header-nav]',
                    '[data-bandara-header-search-branch]',
                    '[data-bandara-header-search-form]',
                    '[data-bandara-header-actions]',
                ].join(',')).forEach((element) => {
                    [
                        'data-bandara-compact-header-row',
                        'data-bandara-header-brand-branch',
                        'data-bandara-header-utility-branch',
                        'data-bandara-header-right-group',
                        'data-bandara-header-nav',
                        'data-bandara-header-search-branch',
                        'data-bandara-header-search-form',
                        'data-bandara-header-actions',
                    ].forEach((attribute) => element.removeAttribute(attribute));
                });
            };

            const scoreNavigation = (nav) => {
                if (!isVisible(nav)) {
                    return -100;
                }

                const text = (nav.textContent || '').toLowerCase();
                const visibleControls = Array.from(nav.querySelectorAll('a, button'))
                    .filter(isVisible).length;
                let score = visibleControls;

                ['shop', 'orders', 'wishlist', 'support'].forEach((word) => {
                    if (text.includes(word)) {
                        score += 5;
                    }
                });

                if (nav.querySelector('input[type="search"], input[name="q"]')) {
                    score -= 8;
                }

                return score;
            };

            const scoreSearchForm = (form) => {
                if (!isVisible(form)) {
                    return -100;
                }

                const rect = form.getBoundingClientRect();
                const classText = `${form.className || ''} ${form.parentElement?.className || ''}`;
                let score = 0;

                if (/\b(?:lg|xl|2xl):(?:flex|block|inline-flex|grid)\b/.test(classText)) score += 8;
                if (/\bhidden\b/.test(classText) && /\b(?:lg|xl|2xl):/.test(classText)) score += 3;
                if (/\b(?:lg|xl|2xl):hidden\b/.test(classText)) score -= 12;
                if (rect.width >= 140) score += 5;

                return score;
            };

            const findBrand = (header) => {
                const candidates = Array.from(header.querySelectorAll('a'))
                    .filter(isVisible)
                    .filter((anchor) => {
                        const text = (anchor.textContent || '').toLowerCase();
                        return anchor.querySelector('img, svg')
                            || text.includes('bandara')
                            || text.includes('bhāṇḍāra');
                    });

                return candidates.sort((left, right) => {
                    const leftRect = left.getBoundingClientRect();
                    const rightRect = right.getBoundingClientRect();
                    return leftRect.left - rightRect.left;
                })[0] || null;
            };

            const findActionControls = (header, nav, searchForm, brand) => {
                const searchRight = searchForm?.getBoundingClientRect().right || 0;
                const labelled = /wishlist|cart|basket|language|translate|theme|dark|light|account|profile|user/i;

                return Array.from(header.querySelectorAll('a, button, select'))
                    .filter(isVisible)
                    .filter((control) => !nav?.contains(control))
                    .filter((control) => !searchForm?.contains(control))
                    .filter((control) => !brand?.contains(control))
                    .filter((control) => {
                        const label = `${control.getAttribute('aria-label') || ''} ${control.getAttribute('title') || ''}`;
                        const rect = control.getBoundingClientRect();
                        return labelled.test(label) || rect.left >= searchRight - 4;
                    });
            };

            const compactHeader = () => {
                const header = Array.from(document.querySelectorAll('header')).find(isVisible);

                if (!header) {
                    return;
                }

                clearHeaderMarkers(header);

                const searchForms = Array.from(header.querySelectorAll('form'))
                    .filter((form) => form.querySelector('input[type="search"], input[name="q"]'))
                    .sort((left, right) => scoreSearchForm(right) - scoreSearchForm(left));
                const searchForm = searchForms[0] || null;

                const nav = Array.from(header.querySelectorAll('nav'))
                    .sort((left, right) => scoreNavigation(right) - scoreNavigation(left))[0] || null;
                const brand = findBrand(header);

                if (!searchForm || !nav || !brand) {
                    return;
                }

                const actions = findActionControls(header, nav, searchForm, brand);
                const nodes = [brand, nav, searchForm, ...actions.slice(0, 8)];
                const row = lowestCommonAncestor(nodes, header);

                if (!row || row === document.body || row === document.documentElement) {
                    return;
                }

                row.setAttribute('data-bandara-compact-header-row', '');
                nav.setAttribute('data-bandara-header-nav', '');
                searchForm.setAttribute('data-bandara-header-search-form', '');

                const brandBranch = branchWithin(row, brand);
                const navBranch = branchWithin(row, nav);
                const searchBranch = branchWithin(row, searchForm);

                brandBranch?.setAttribute('data-bandara-header-brand-branch', '');
                navBranch?.setAttribute('data-bandara-header-utility-branch', '');
                searchBranch?.setAttribute('data-bandara-header-utility-branch', '');
                searchBranch?.setAttribute('data-bandara-header-search-branch', '');

                const rightNodes = [nav, searchForm, ...actions];
                const rightGroup = lowestCommonAncestor(rightNodes, row);

                if (rightGroup && rightGroup !== row) {
                    rightGroup.setAttribute('data-bandara-header-right-group', '');
                    branchWithin(row, rightGroup)?.setAttribute('data-bandara-header-utility-branch', '');
                }

                const actionGroup = lowestCommonAncestor(actions, row);

                if (actionGroup && actionGroup !== row) {
                    actionGroup.setAttribute('data-bandara-header-actions', '');
                    branchWithin(row, actionGroup)?.setAttribute('data-bandara-header-utility-branch', '');
                } else {
                    actions.forEach((action) => {
                        const branch = branchWithin(row, action);
                        branch?.setAttribute('data-bandara-header-utility-branch', '');
                    });
                }

                /* Ensure every right-side direct branch is auto-sized, even when the layout used flex:1. */
                [nav, searchForm, ...actions].forEach((node) => {
                    branchWithin(row, node)?.setAttribute('data-bandara-header-utility-branch', '');
                });
            };

            const isLikelyProductCard = (element) => {
                if (!(element instanceof HTMLElement)) {
                    return false;
                }

                if (element.matches('a, button, form, input, select, textarea')) {
                    return false;
                }

                if (element.closest('header, footer, nav, .bandara-phase1-hero, [data-bandara-phase1-hero]')) {
                    return false;
                }

                const images = element.querySelectorAll('img');
                if (!images.length || images.length > 5) {
                    return false;
                }

                const productLinks = element.querySelectorAll(
                    'a[href*="/shop/"], a[href*="/product/"], a[href*="/products/"]'
                );
                const purchaseControls = element.querySelectorAll(
                    'form[action*="cart"], button[name="product_id"], [data-product-id], [data-variant-id]'
                );
                const headings = element.querySelectorAll('h2, h3, h4');

                if (!productLinks.length && !purchaseControls.length) {
                    return false;
                }

                if (!headings.length && !element.querySelector('[class*="product"], [class*="price"]')) {
                    return false;
                }

                const rect = element.getBoundingClientRect();
                if (rect.width > 760 || rect.height > 1100) {
                    return false;
                }

                return true;
            };

            const findCardForSeed = (seed) => {
                let node = seed.parentElement;
                const main = seed.closest('main') || document.querySelector('main') || document.body;
                let depth = 0;

                while (node && node !== main.parentElement && depth < 10) {
                    if (isLikelyProductCard(node)) {
                        return node;
                    }

                    node = node.parentElement;
                    depth += 1;
                }

                return null;
            };

            const markProductImage = (card) => {
                const images = Array.from(card.querySelectorAll('img'));

                if (!images.length) {
                    return;
                }

                const image = images.sort((left, right) => {
                    const leftRect = left.getBoundingClientRect();
                    const rightRect = right.getBoundingClientRect();
                    return (rightRect.width * rightRect.height) - (leftRect.width * leftRect.height);
                })[0];

                image.setAttribute(IMAGE_ATTRIBUTE, '');
            };

            const markProductCards = (root = document) => {
                root.querySelectorAll([
                    '[data-bandara-phase1-product-card]',
                    '[data-product-card]',
                    '.product-card',
                ].join(',')).forEach((card) => {
                    card.setAttribute(CARD_ATTRIBUTE, '');
                    markProductImage(card);
                });

                const seeds = root.querySelectorAll([
                    'main form[action*="cart"]',
                    'main button[name="product_id"]',
                    'main [data-product-id]',
                    'main [data-variant-id]',
                    'main a[href*="/shop/"]',
                    'main a[href*="/product/"]',
                    'main a[href*="/products/"]',
                ].join(','));

                seeds.forEach((seed) => {
                    const card = findCardForSeed(seed);
                    if (!card) {
                        return;
                    }

                    card.setAttribute(CARD_ATTRIBUTE, '');
                    markProductImage(card);
                });
            };

            const initialise = () => {
                document.body.classList.add('bandara-storefront-ui-refinement-v3');
                compactHeader();
                markProductCards();

                let cardRefreshQueued = false;
                const observer = new MutationObserver((mutations) => {
                    if (cardRefreshQueued) {
                        return;
                    }

                    if (!mutations.some((mutation) => mutation.addedNodes.length)) {
                        return;
                    }

                    cardRefreshQueued = true;
                    window.requestAnimationFrame(() => {
                        markProductCards();
                        cardRefreshQueued = false;
                    });
                });

                observer.observe(document.body, { childList: true, subtree: true });

                let headerTimer = null;
                window.addEventListener('resize', () => {
                    window.clearTimeout(headerTimer);
                    headerTimer = window.setTimeout(compactHeader, 120);
                }, { passive: true });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialise, { once: true });
            } else {
                initialise();
            }
        })();
    </script>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/storefront-ui-refinement-v3.blade.php ENDPATH**/ ?>