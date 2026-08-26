/*
 * Bandara storefront launch refinements — layout-safe UI correction v1.4.1.
 *
 * Important design rule:
 * - Existing page elements are never moved out of their original containers.
 * - The Help & FAQ floating tools are non-destructive clones shown only after
 *   the original controls have scrolled out of view.
 */
const BandaraLaunchUi = (() => {
    const VERSION = '1.4.1-alignment';
    const BUILD_MARKER = 'BANDARA_UI_ALIGNMENT_FIX_1_4_1';
    let productObserver = null;
    let revealObserver = null;

    const safePath = () => window.location.pathname.replace(/\/+$/, '') || '/';
    const normalizeText = (value) => (value || '').replace(/\s+/g, ' ').trim().toLowerCase();
    const uniqueElements = (elements) => Array.from(new Set(elements.filter(Boolean)));

    const isTransactionalPage = () => /\/(admin|staff|checkout|cart|login|register|account|orders)(\/|$)/i.test(safePath());

    const contentRoot = () => document.querySelector('main, [role="main"], .page-body, [data-page-body]') || document.body;

    /* ---------------------------------------------------------------------
     * Scroll reveal
     * ------------------------------------------------------------------ */
    const isRevealCandidate = (element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        if (element.matches('header, nav, footer, form, dialog, script, style, template, [hidden]')) {
            return false;
        }

        if (element.closest('header, nav, footer, form, dialog, [data-no-reveal], .bandara-faq-floating')) {
            return false;
        }

        if (element.matches('[data-no-reveal], .bandara-launch-reward, .bandara-faq-floating')) {
            return false;
        }

        const style = window.getComputedStyle(element);
        if (style.display === 'none' || style.visibility === 'hidden' || ['fixed', 'sticky'].includes(style.position)) {
            return false;
        }

        const rect = element.getBoundingClientRect();
        return rect.height >= 48 && rect.width >= 80;
    };

    const collectRevealTargets = () => {
        const explicit = Array.from(document.querySelectorAll([
            '[data-bandara-reveal]',
            '[data-home-section]',
            '.home-section',
            '.page-body > section',
            '.page-body > article',
            '.page-body section',
            '.page-body article',
            '.page-body > .section-gap',
            '.page-body > .section-gap-sm',
            'main > section',
            'main > article',
            'main section',
            'main article',
            '[role="main"] > section',
            '[role="main"] > article',
            '[role="main"] section',
            '[role="main"] article',
            '[data-section-key]',
            '[data-home-key]',
            '[class*="home-section" i]',
        ].join(',')));

        const roots = uniqueElements([
            document.querySelector('.page-body'),
            document.querySelector('main'),
            document.querySelector('[role="main"]'),
        ]);

        const inferred = [];
        roots.forEach((root) => {
            Array.from(root.children).forEach((child) => {
                if (!(child instanceof HTMLElement)) {
                    return;
                }

                const classSignal = child.className && typeof child.className === 'string'
                    ? /(section|hero|collection|recipe|kitchen|feature|trust|support|category|product|content|faq)/i.test(child.className)
                    : false;
                const contentSignal = Boolean(child.querySelector('h2, h3, [data-product-card], .product-card, [class*="product-card" i]'));

                if (['SECTION', 'ARTICLE'].includes(child.tagName) || classSignal || child.hasAttribute('data-section') || contentSignal) {
                    inferred.push(child);
                }

                // Some templates have one structural wrapper inside .page-body.
                if (child.matches('main, [role="main"], .content, .page-content')) {
                    Array.from(child.children).forEach((grandchild) => {
                        if (grandchild instanceof HTMLElement && (
                            ['SECTION', 'ARTICLE'].includes(grandchild.tagName)
                            || /(section|hero|collection|recipe|feature|trust|support|category|product|faq)/i.test(grandchild.className || '')
                        )) {
                            inferred.push(grandchild);
                        }
                    });
                }
            });
        });

        let targets = uniqueElements([...explicit, ...inferred]).filter(isRevealCandidate);

        // Final fallback: visible sections anywhere inside the content root. This
        // covers Blade templates which wrap sections in an extra container.
        if (!targets.length) {
            roots.forEach((root) => {
                targets.push(...Array.from(root.querySelectorAll('section, article')).filter(isRevealCandidate));
            });
            targets = uniqueElements(targets);
        }

        return targets;
    };

    const initScrollReveal = () => {
        if (isTransactionalPage() || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const targets = collectRevealTargets();
        if (!targets.length) {
            return;
        }

        document.documentElement.classList.add('bandara-reveal-enabled');

        targets.forEach((element, index) => {
            element.classList.add('bandara-scroll-reveal');
            element.dataset.bandaraRevealBound = VERSION;
            element.style.setProperty('--bandara-reveal-delay', `${Math.min(index % 4, 3) * 40}ms`);

            const rect = element.getBoundingClientRect();
            if (rect.bottom > window.innerHeight * 0.06 && rect.top < window.innerHeight * 0.92) {
                // Above-the-fold content must never flash blank on page load.
                element.classList.add('is-bandara-visible');
            }
        });

        if (!('IntersectionObserver' in window)) {
            targets.forEach((element) => element.classList.add('is-bandara-visible'));
            return;
        }

        revealObserver?.disconnect();
        revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                entry.target.classList.toggle('is-bandara-visible', entry.isIntersecting);
            });
        }, {
            threshold: 0,
            rootMargin: '-4% 0px -7% 0px',
        });

        targets.forEach((element) => revealObserver.observe(element));
    };

    /* ---------------------------------------------------------------------
     * Theme icon — show the action available to the customer
     * ------------------------------------------------------------------ */
    const isDarkTheme = () => {
        const html = document.documentElement;
        const body = document.body;
        const declared = (html.getAttribute('data-theme') || body?.getAttribute('data-theme') || '').toLowerCase();

        return declared === 'dark'
            || html.classList.contains('dark')
            || Boolean(body?.classList.contains('dark'));
    };

    const actionIconMarkup = () => `
        <span class="bandara-theme-action-icons" aria-hidden="true">
            <svg class="bandara-theme-action-icon bandara-theme-action-icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <path d="M20.6 15.3A8.5 8.5 0 0 1 8.7 3.4 8.5 8.5 0 1 0 20.6 15.3Z"></path>
            </svg>
            <svg class="bandara-theme-action-icon bandara-theme-action-icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                <circle cx="12" cy="12" r="3.75"></circle>
                <path d="M12 2.3v2.1M12 19.6v2.1M4.4 12H2.3M21.7 12h-2.1M5.2 5.2l1.5 1.5M17.3 17.3l1.5 1.5M18.8 5.2l-1.5 1.5M6.7 17.3l-1.5 1.5"></path>
            </svg>
        </span>`;

    const themeButtons = () => uniqueElements(Array.from(document.querySelectorAll([
        '#themeBtn',
        '[data-theme-toggle]',
        '#theme-toggle',
        '#dark-mode-toggle',
        'button[aria-label*="theme" i]',
        'button[aria-label*="dark mode" i]',
        'button[aria-label*="light mode" i]',
        'button[title*="theme" i]',
        'button[title*="dark mode" i]',
        'button[title*="light mode" i]',
    ].join(','))));

    const ensureActionIcon = (button) => {
        let wrapper = button.querySelector(':scope > .bandara-theme-action-icons');
        if (!wrapper) {
            button.insertAdjacentHTML('beforeend', actionIconMarkup());
            wrapper = button.querySelector(':scope > .bandara-theme-action-icons');
        }

        // Keep the application’s existing icon nodes in place for its own theme
        // script, but hide their visual output. This also handles controls that
        // render only one SVG rather than separate #sunIcon / #moonIcon nodes.
        button.querySelectorAll('svg, #sunIcon, #moonIcon, [data-theme-icon], [data-lucide], .icon-sun, .icon-moon').forEach((icon) => {
            if (!icon.closest('.bandara-theme-action-icons')) {
                icon.classList.add('bandara-theme-source-icon');
            }
        });

        Array.from(button.children).forEach((child) => {
            if (child === wrapper || !(child instanceof HTMLElement)) {
                return;
            }
            const hasOnlyIcons = child.textContent.trim() === ''
                && child.querySelector('svg, [data-theme-icon], [data-lucide], #sunIcon, #moonIcon')
                && !child.querySelector('input, img, button, a');
            if (hasOnlyIcons) {
                child.classList.add('bandara-theme-source-icon');
            }
        });

        return wrapper;
    };

    const initThemeToggle = () => {
        let syncQueued = false;
        let buttons = themeButtons();
        if (!buttons.length) {
            return;
        }

        const sync = () => {
            syncQueued = false;
            buttons = themeButtons();
            const dark = isDarkTheme();

            buttons.forEach((button) => {
                const wrapper = ensureActionIcon(button);
                if (!wrapper) {
                    return;
                }

                wrapper.classList.toggle('is-dark', dark);
                const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
            });
        };

        const queueSync = () => {
            if (syncQueued) {
                return;
            }
            syncQueued = true;
            window.requestAnimationFrame(sync);
        };

        sync();
        window.setTimeout(sync, 60);
        window.setTimeout(sync, 260);

        const observer = new MutationObserver(queueSync);
        observer.observe(document.documentElement, {
            attributes: true,
            childList: true,
            subtree: true,
            attributeFilter: ['class', 'data-theme'],
        });

        buttons.forEach((button) => button.addEventListener('click', () => {
            queueSync();
            window.setTimeout(sync, 40);
            window.setTimeout(sync, 180);
        }));
    };

    /* ---------------------------------------------------------------------
     * Product-detail hover
     * ------------------------------------------------------------------ */
    const productPathPattern = /\/(?:product|products|shop)\/[^/]+(?:\/[^/]+)?$/i;

    const isProductLikeLink = (link) => {
        if (!(link instanceof HTMLAnchorElement)) {
            return false;
        }

        try {
            const target = new URL(link.href, window.location.origin);
            const current = new URL(window.location.href);
            const targetPath = target.pathname.replace(/\/+$/, '');
            const currentPath = current.pathname.replace(/\/+$/, '');

            return target.origin === current.origin
                && targetPath !== currentPath
                && /\/(?:product|products|shop)\//i.test(targetPath);
        } catch (_) {
            return false;
        }
    };

    const findAddToCartControl = (main) => {
        const explicit = main.querySelector('[data-add-to-cart], form[action*="cart" i], form[action*="basket" i]');
        if (explicit) {
            return explicit;
        }

        return Array.from(main.querySelectorAll('button, input[type="submit"], a[role="button"]')).find((control) => {
            const label = normalizeText([
                control.textContent,
                control.getAttribute('value'),
                control.getAttribute('aria-label'),
                control.getAttribute('title'),
            ].filter(Boolean).join(' '));

            return /(add|buy|select).*(cart|basket|piece|slab)|cart.*(add|buy)/i.test(label);
        }) || null;
    };

    const looksLikeProductDetailPage = () => {
        const main = contentRoot();
        if (!main || main === document.body && !productPathPattern.test(safePath())) {
            return false;
        }

        const explicit = main.querySelector('[data-product-detail], [data-product-gallery], [data-product-id], .product-detail, [class*="product-detail" i]');
        const h1 = main.querySelector('h1');
        const image = main.querySelector('img');
        const addControl = findAddToCartControl(main);
        const pathMatch = productPathPattern.test(safePath());

        return Boolean(explicit)
            || Boolean(pathMatch && h1 && image)
            || Boolean(h1 && image && addControl);
    };

    const renderedArea = (element) => {
        const rect = element.getBoundingClientRect();
        return Math.max(0, rect.width) * Math.max(0, rect.height);
    };

    const findPrimaryProductImage = (main) => {
        const heading = normalizeText(main.querySelector('h1')?.textContent);
        const addControl = findAddToCartControl(main);
        const addRect = addControl?.getBoundingClientRect();
        const cardSelector = '.product-card, [data-product-card], [class*="product-card" i], [class*="product-tile" i]';

        const images = Array.from(main.querySelectorAll('img')).filter((image) => {
            if (image.closest('header, nav, footer, .bandara-faq-floating')) {
                return false;
            }
            if (image.closest(cardSelector)) {
                return false;
            }

            const rect = image.getBoundingClientRect();
            return rect.width >= 120 && rect.height >= 120;
        });

        let best = null;
        let bestScore = -1;

        images.forEach((image) => {
            const rect = image.getBoundingClientRect();
            let score = renderedArea(image);
            const alt = normalizeText(image.getAttribute('alt'));

            if (image.closest('[data-product-gallery], [data-product-images], .product-gallery, .product-images, [class*="product-gallery" i], [class*="product-image" i]')) {
                score += 5_000_000;
            }

            if (heading && alt && (heading.includes(alt) || alt.includes(heading) || heading.split(' ').some((word) => word.length > 4 && alt.includes(word)))) {
                score += 1_500_000;
            }

            if (addRect && rect.top <= addRect.bottom + 160) {
                score += 600_000;
            }

            if (rect.top < window.innerHeight * 1.2) {
                score += 300_000;
            }

            if (score > bestScore) {
                bestScore = score;
                best = image;
            }
        });

        return best;
    };

    const primaryVisualWrapper = (image, main) => {
        if (!image) {
            return null;
        }

        // Prefer the existing block which visually owns the principal image.
        // Do not stop at <picture> or an inline link: transforms on inline
        // wrappers are frequently invisible, which was the cause of the
        // product-detail hover appearing to do nothing in the real storefront.
        let wrapper = image.closest([
            '[data-product-image]',
            '[data-product-gallery-main]',
            '[data-product-gallery]',
            '[data-product-images]',
            '.product-image',
            '.product-main-image',
            '.product-gallery__main',
            '.product-gallery',
            '.product-images',
            '[class*="main-image" i]',
            '[class*="product-gallery" i]',
            '[class*="gallery" i]',
            '[class*="product-media" i]',
            '[class*="media-shell" i]',
            '[class*="image-shell" i]',
            '[class*="visual" i]',
            'figure',
        ].join(','));

        if (!wrapper || wrapper === main) {
            const imageRect = image.getBoundingClientRect();
            let node = image.parentElement;
            let depth = 0;

            while (node instanceof HTMLElement && node !== main && depth < 6) {
                const rect = node.getBoundingClientRect();
                const style = window.getComputedStyle(node);
                const displayIsBox = !['inline', 'contents', 'none'].includes(style.display);
                const nearImageSize = rect.width >= imageRect.width * 0.82
                    && rect.width <= imageRect.width * 1.45
                    && rect.height >= imageRect.height * 0.70
                    && rect.height <= imageRect.height * 1.55;
                const safeContent = node.querySelectorAll('img').length <= 2
                    && !node.querySelector('h1, form, [data-add-to-cart]');

                if (displayIsBox && nearImageSize && safeContent) {
                    wrapper = node;
                    break;
                }

                node = node.parentElement;
                depth += 1;
            }
        }

        if (!wrapper || wrapper === main) {
            wrapper = image.parentElement;
        }

        // Never apply the effect to a container which also owns thumbnails or
        // the whole product information column.
        while (wrapper && wrapper !== main && wrapper.querySelectorAll('img').length > 2) {
            const child = Array.from(wrapper.children).find((candidate) => candidate === image || candidate.contains(image));
            if (!child || child === image) {
                break;
            }
            wrapper = child;
        }

        return wrapper instanceof HTMLElement ? wrapper : null;
    };

    const nearestProductCard = (link, main) => {
        let candidate = link.closest('.product-card, [data-product-card], [class*="product-card" i], [class*="product-tile" i], article, li, .group');
        if (candidate && candidate !== main) {
            return candidate;
        }

        let node = link.parentElement;
        let depth = 0;
        while (node && node !== main && depth < 6) {
            const classSignal = /(product|card|tile|item)/i.test(node.className || '');
            const imageCount = node.querySelectorAll('img').length;
            const linkCount = node.querySelectorAll('a[href]').length;
            const siblingProductCount = node.parentElement
                ? Array.from(node.parentElement.children).filter((sibling) => sibling.querySelector?.('img') && Array.from(sibling.querySelectorAll?.('a[href]') || []).some(isProductLikeLink)).length
                : 0;

            if (imageCount >= 1 && linkCount <= 5 && (classSignal || siblingProductCount >= 2)) {
                candidate = node;
                break;
            }

            node = node.parentElement;
            depth += 1;
        }

        return candidate;
    };

    const markProductDetail = () => {
        if (!looksLikeProductDetailPage()) {
            return;
        }

        const main = contentRoot();
        if (!main) {
            return;
        }

        document.documentElement.classList.add('bandara-product-detail-page');

        const primaryImage = findPrimaryProductImage(main);
        const primaryVisual = primaryVisualWrapper(primaryImage, main);
        if (primaryVisual) {
            primaryVisual.classList.add('bandara-product-detail-visual');
        }
        if (primaryImage) {
            primaryImage.classList.add('bandara-product-primary-image');
        }

        const candidates = new Set(Array.from(main.querySelectorAll('.product-card, [data-product-card], [class*="product-card" i], [class*="product-tile" i]')));
        Array.from(main.querySelectorAll('a[href]')).filter(isProductLikeLink).forEach((link) => {
            const card = nearestProductCard(link, main);
            if (card) {
                candidates.add(card);
            }
        });

        Array.from(main.querySelectorAll('h2, h3, [role="heading"]')).forEach((heading) => {
            if (!/(related|recommended|similar|you may also|more to explore|customers also|complete your)/i.test(normalizeText(heading.textContent))) {
                return;
            }

            const section = heading.closest('section, article, [data-related-products], [data-recommendations], div');
            if (!section || section === main) {
                return;
            }

            Array.from(section.querySelectorAll('article, li, [data-product-card], .product-card, [class*="product-card" i], [class*="product-tile" i]')).forEach((card) => {
                if (card.querySelector('img, picture')) {
                    candidates.add(card);
                }
            });
        });

        const h1 = main.querySelector('h1');
        const addControl = findAddToCartControl(main);

        candidates.forEach((card) => {
            if (!(card instanceof HTMLElement) || card === main || !main.contains(card)) {
                return;
            }
            if (primaryVisual && (card === primaryVisual || card.contains(primaryVisual) || primaryVisual.contains(card))) {
                return;
            }
            if (h1 && card.contains(h1)) {
                return;
            }
            if (addControl && card.contains(addControl)) {
                return;
            }
            if (!card.querySelector('img, picture')) {
                return;
            }

            card.classList.add('bandara-product-card', 'bandara-product-related-card');
        });
    };

    const initProductDetailCards = () => {
        if (!looksLikeProductDetailPage()) {
            return;
        }

        markProductDetail();
        window.addEventListener('load', markProductDetail, { once: true });
        window.setTimeout(markProductDetail, 250);

        if ('MutationObserver' in window && !productObserver) {
            let queued = false;
            productObserver = new MutationObserver(() => {
                if (queued) {
                    return;
                }
                queued = true;
                window.requestAnimationFrame(() => {
                    queued = false;
                    markProductDetail();
                });
            });
            productObserver.observe(contentRoot(), {
                childList: true,
                subtree: true,
            });
        }
    };

    /* ---------------------------------------------------------------------
     * Newsletter state
     * ------------------------------------------------------------------ */
    const isNewsletterSubscribeAction = (action) => {
        let pathname = action;
        try {
            pathname = new URL(action, window.location.origin).pathname;
        } catch (_) {
            pathname = action.split(/[?#]/, 1)[0];
        }

        const segments = pathname.toLowerCase().split('/').filter(Boolean);
        return segments.includes('newsletter') && segments.includes('subscribe') && !segments.includes('unsubscribe');
    };

    const initNewsletterState = () => {
        const meta = document.querySelector('meta[name="bandara-newsletter-subscribed"]');
        if (!meta || meta.content !== '1') {
            return;
        }

        const forms = Array.from(document.querySelectorAll('form[action]')).filter((form) => {
            return isNewsletterSubscribeAction(form.getAttribute('action') || '');
        });

        forms.forEach((form) => {
            const confirmation = document.createElement('div');
            confirmation.className = 'bandara-newsletter-confirmation';
            confirmation.setAttribute('role', 'status');
            confirmation.setAttribute('aria-live', 'polite');
            confirmation.innerHTML = '<span aria-hidden="true">✓</span><strong>Subscribed</strong>';
            form.replaceWith(confirmation);
        });
    };

    /* ---------------------------------------------------------------------
     * Help & FAQ floating tools — preserve the original design completely
     * ------------------------------------------------------------------ */
    const isHelpPage = () => {
        const heading = normalizeText(document.querySelector('main h1, [role="main"] h1, main [role="heading"][aria-level="1"]')?.textContent);
        return /(^|\/)(help|help-faq|help-faqs|faq|faqs)(\/|$)/i.test(safePath())
            || heading.includes('help')
            || heading.includes('faq');
    };

    const findHelpSearch = (main) => {
        const candidates = Array.from(main.querySelectorAll('input, [contenteditable="true"]'));
        return candidates.find((input) => {
            const searchable = [
                input.getAttribute('type'),
                input.getAttribute('id'),
                input.getAttribute('name'),
                input.getAttribute('placeholder'),
                input.getAttribute('aria-label'),
            ].join(' ').toLowerCase();

            return input.matches('input[type="search"], [role="searchbox"]')
                || /(search|help|faq|question|answer|delivery|refund)/i.test(searchable);
        }) || null;
    };

    const isSamePageHashLink = (control) => {
        if (!(control instanceof HTMLAnchorElement)) {
            return false;
        }

        const href = control.getAttribute('href') || '';
        if (href.startsWith('#')) {
            return href.length > 1;
        }

        try {
            const target = new URL(href, window.location.href);
            const current = new URL(window.location.href);
            return target.origin === current.origin
                && target.pathname.replace(/\/+$/, '') === current.pathname.replace(/\/+$/, '')
                && target.hash.length > 1;
        } catch (_) {
            return false;
        }
    };

    const faqControlCount = (candidate) => {
        return Array.from(candidate.querySelectorAll('a, button, [role="tab"]')).filter((control) => {
            if (control instanceof HTMLAnchorElement) {
                return isSamePageHashLink(control);
            }
            return control.matches('[role="tab"], button[aria-controls], button[data-target], button[data-faq-target]');
        }).length;
    };

    const findFaqNavigation = (main, search) => {
        const semanticCandidates = Array.from(main.querySelectorAll([
            'nav',
            '[role="tablist"]',
            '[data-faq-nav]',
            '[class*="faq-nav" i]',
            '[class*="faq-link" i]',
            '[class*="faq-tabs" i]',
            '[class*="category-nav" i]',
            '[class*="section-link" i]',
        ].join(',')));

        const semantic = semanticCandidates.find((candidate) => {
            return !candidate.contains(search)
                && faqControlCount(candidate) >= 2
                && candidate.querySelectorAll('details, article, [data-faq-item]').length === 0;
        });
        if (semantic) {
            return semantic;
        }

        const grouped = new Map();
        Array.from(main.querySelectorAll('a[href*="#"], button[aria-controls], [role="tab"]')).forEach((control) => {
            if (control instanceof HTMLAnchorElement && !isSamePageHashLink(control)) {
                return;
            }

            const parent = control.closest('nav, [role="tablist"], ul, ol, [data-faq-nav], div');
            if (!parent || parent === main || parent.contains(search)) {
                return;
            }

            grouped.set(parent, (grouped.get(parent) || 0) + 1);
        });

        return Array.from(grouped.entries())
            .filter(([candidate, count]) => count >= 2 && candidate.querySelectorAll('details, article, [data-faq-item]').length === 0)
            .sort((left, right) => right[1] - left[1])
            .map(([candidate]) => candidate)[0] || null;
    };

    const parseZIndex = (value) => {
        const parsed = Number.parseInt(value, 10);
        return Number.isFinite(parsed) ? parsed : 0;
    };

    const isVisibleBox = (element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        const rect = element.getBoundingClientRect();
        const style = window.getComputedStyle(element);
        return rect.width > 0
            && rect.height > 0
            && rect.bottom > 0
            && style.display !== 'none'
            && style.visibility !== 'hidden'
            && Number.parseFloat(style.opacity || '1') > 0.01;
    };

    const isMeasurableBox = (element) => {
        if (!(element instanceof HTMLElement)) {
            return false;
        }

        const rect = element.getBoundingClientRect();
        const style = window.getComputedStyle(element);
        return rect.width > 0
            && rect.height > 0
            && style.display !== 'none'
            && style.visibility !== 'hidden';
    };

    const calculateFixedHeaderStack = () => {
        const candidates = new Set();
        const addWithAncestors = (element) => {
            let node = element;
            let depth = 0;
            while (node instanceof HTMLElement && node !== document.body && depth < 7) {
                if (!node.closest?.('.bandara-faq-floating')) {
                    candidates.add(node);
                }
                node = node.parentElement;
                depth += 1;
            }
        };

        document.querySelectorAll([
            'header',
            '[role="banner"]',
            '[data-site-header]',
            '[data-main-header]',
            '[data-header]',
            '.site-header',
            '.main-header',
            '.app-header',
            '.top-header',
            '.topbar',
            '.top-bar',
            'body > nav',
            '[data-main-navigation]',
        ].join(',')).forEach(addWithAncestors);

        const sampleXs = [
            8,
            Math.max(8, window.innerWidth * 0.18),
            Math.max(8, window.innerWidth * 0.5),
            Math.max(8, window.innerWidth * 0.82),
            Math.max(8, window.innerWidth - 8),
        ];

        let probeBottom = 0;
        let maxZIndex = 0;
        for (let pass = 0; pass < 4; pass += 1) {
            const probeY = Math.min(window.innerHeight - 1, Math.max(1, probeBottom + 1));
            sampleXs.forEach((x) => {
                document.elementsFromPoint(Math.min(window.innerWidth - 1, x), probeY).forEach(addWithAncestors);
            });

            let nextBottom = probeBottom;
            candidates.forEach((element) => {
                if (!isVisibleBox(element)) {
                    return;
                }

                const rect = element.getBoundingClientRect();
                const style = window.getComputedStyle(element);
                const positionAttached = ['fixed', 'sticky'].includes(style.position);
                const headerSignal = element.matches?.('header, [role="banner"], [data-site-header], [data-main-header], [data-header], .site-header, .main-header, .app-header, .top-header, .topbar, .top-bar, body > nav, [data-main-navigation]');
                const touchesStack = rect.top <= probeBottom + 5 && rect.bottom > probeBottom + 1;
                const sensibleHeight = rect.bottom <= Math.max(360, window.innerHeight * 0.45);

                if (touchesStack && sensibleHeight && (positionAttached || headerSignal)) {
                    nextBottom = Math.max(nextBottom, Math.ceil(rect.bottom));
                    maxZIndex = Math.max(maxZIndex, parseZIndex(style.zIndex));
                }
            });

            if (nextBottom <= probeBottom + 1) {
                break;
            }
            probeBottom = nextBottom;
        }

        if (probeBottom === 0) {
            const fallbackHeader = document.querySelector('header, [role="banner"], [data-site-header], .site-header');
            if (fallbackHeader instanceof HTMLElement) {
                const rect = fallbackHeader.getBoundingClientRect();
                const style = window.getComputedStyle(fallbackHeader);
                if (rect.bottom > 0 && rect.top <= 4 && isVisibleBox(fallbackHeader)) {
                    probeBottom = Math.ceil(rect.bottom);
                    maxZIndex = Math.max(maxZIndex, parseZIndex(style.zIndex));
                }
            }
        }

        return {
            bottom: Math.max(0, probeBottom),
            zIndex: maxZIndex,
        };
    };

    const calculateFixedHeaderBottom = () => calculateFixedHeaderStack().bottom;

    const faqLayoutRail = (sourceInput, sourceNavigation, main) => {
        const viewportWidth = Math.max(320, window.innerWidth);
        const minimumGutter = viewportWidth <= 640 ? 10 : 16;
        const candidates = new Set();

        const addAncestors = (element) => {
            let node = element;
            let depth = 0;
            while (node instanceof HTMLElement && node !== document.body && depth < 10) {
                candidates.add(node);
                if (node === main) {
                    break;
                }
                node = node.parentElement;
                depth += 1;
            }
        };

        addAncestors(sourceNavigation);
        addAncestors(sourceInput.closest('form, [role="search"]') || sourceInput);
        addAncestors(main);

        const minimumUsefulWidth = Math.min(720, viewportWidth - (minimumGutter * 2));
        const idealWidth = Math.min(1440, viewportWidth - (minimumGutter * 2));
        let best = null;
        let bestScore = -Infinity;

        candidates.forEach((candidate) => {
            if (!isMeasurableBox(candidate)) {
                return;
            }

            const rect = candidate.getBoundingClientRect();
            const leftGutter = rect.left;
            const rightGutter = viewportWidth - rect.right;
            if (rect.width < Math.max(280, minimumUsefulWidth * 0.68)
                || rect.width > viewportWidth - 4
                || leftGutter < minimumGutter - 4
                || rightGutter < minimumGutter - 4) {
                return;
            }

            const classSignal = `${candidate.id || ''} ${candidate.className || ''}`;
            let score = 0;
            if (sourceNavigation && candidate.contains(sourceNavigation)) score += 120;
            if (candidate.contains(sourceInput)) score += 80;
            if (candidate === sourceNavigation?.parentElement) score += 90;
            if (candidate === sourceNavigation) score += 45;
            if (/(container|max-w|wrapper|content|page|faq|support|legal)/i.test(classSignal)) score += 40;
            if (Math.abs(leftGutter - rightGutter) <= 24) score += 55;
            if (leftGutter >= 12 && rightGutter >= 12) score += 30;
            score -= Math.abs(rect.width - idealWidth) / 18;

            // Prefer the broad page rail, not a narrow search field or chip group.
            if (rect.width >= viewportWidth * 0.72) score += 40;
            if (rect.width >= viewportWidth * 0.84) score += 25;

            if (score > bestScore) {
                bestScore = score;
                best = rect;
            }
        });

        let left;
        let width;
        if (best) {
            left = Math.max(minimumGutter, Math.round(best.left));
            width = Math.min(Math.round(best.width), viewportWidth - left - minimumGutter);
        } else {
            width = Math.min(1440, viewportWidth - (minimumGutter * 2));
            left = Math.max(minimumGutter, Math.round((viewportWidth - width) / 2));
        }

        return {
            left,
            width: Math.max(280, width),
        };
    };

    const nativeInputValueSetter = (input, value) => {
        const descriptor = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
        if (descriptor?.set) {
            descriptor.set.call(input, value);
        } else {
            input.value = value;
        }
    };

    const dispatchSearchInput = (input) => {
        input.dispatchEvent(new Event('input', { bubbles: true, composed: true }));
        input.dispatchEvent(new Event('change', { bubbles: true, composed: true }));
        input.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true, composed: true, key: 'Unidentified' }));
    };

    const faqTargetForControl = (control) => {
        if (!(control instanceof HTMLAnchorElement) || !isSamePageHashLink(control)) {
            return null;
        }

        try {
            const targetUrl = new URL(control.getAttribute('href') || '', window.location.href);
            const targetId = decodeURIComponent(targetUrl.hash.slice(1));
            return targetId ? document.getElementById(targetId) : null;
        } catch (_) {
            return null;
        }
    };

    const scrollToFaqTarget = (target, floating) => {
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const headerBottom = calculateFixedHeaderBottom();
        const floatingHeight = floating?.getBoundingClientRect().height || 0;
        const gap = 14;
        const top = Math.max(0, window.scrollY + target.getBoundingClientRect().top - headerBottom - floatingHeight - gap);

        window.scrollTo({
            top,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
        });

        if (target.id) {
            try {
                const url = new URL(window.location.href);
                url.hash = target.id;
                window.history.replaceState(window.history.state, '', url);
            } catch (_) {
                // Hash updates are optional; scrolling still succeeds.
            }
        }
    };

    const createFloatingFaqLink = (control, floating) => {
        if (control instanceof HTMLAnchorElement && isSamePageHashLink(control)) {
            const link = document.createElement('a');
            link.className = 'bandara-faq-floating__link';
            link.href = control.getAttribute('href') || '#';
            link.textContent = (control.textContent || '').replace(/\s+/g, ' ').trim();
            link.addEventListener('click', (event) => {
                const target = faqTargetForControl(control);
                if (!target) {
                    return;
                }

                event.preventDefault();
                sourceActivate(control);
                scrollToFaqTarget(target, floating);
            });
            return link;
        }

        if (control.matches?.('[role="tab"], button[aria-controls], button[data-target], button[data-faq-target]')) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'bandara-faq-floating__link';
            button.textContent = (control.textContent || '').replace(/\s+/g, ' ').trim();
            button.addEventListener('click', () => control.click());
            return button;
        }

        return null;
    };

    const sourceActivate = (control) => {
        const sourceNav = control.closest('nav, [role="tablist"], [data-faq-nav], ul, ol, div');
        if (!sourceNav) {
            return;
        }

        Array.from(sourceNav.querySelectorAll('a, button, [role="tab"]')).forEach((item) => {
            item.classList.toggle('is-active', item === control);
            if (item.hasAttribute('aria-selected')) {
                item.setAttribute('aria-selected', item === control ? 'true' : 'false');
            }
        });
    };

    const initHelpFloatingTools = () => {
        if (!isHelpPage() || document.querySelector('[data-bandara-faq-floating]')) {
            return;
        }

        document.querySelectorAll('.bandara-faq-sticky, .bandara-faq-overflow-host').forEach((element) => {
            element.classList.remove('bandara-faq-sticky', 'bandara-faq-overflow-host');
        });

        const main = contentRoot();
        if (!main) {
            return;
        }

        const sourceInput = findHelpSearch(main);
        if (!sourceInput) {
            return;
        }

        const sourceNavigation = findFaqNavigation(main, sourceInput);
        const sourceBoundary = sourceNavigation || sourceInput.closest('form, [role="search"]') || sourceInput;
        const originalSearchParent = sourceInput.parentElement;
        const originalNavigationParent = sourceNavigation?.parentElement || null;

        const floating = document.createElement('aside');
        floating.className = 'bandara-faq-floating';
        floating.setAttribute('data-bandara-faq-floating', '1');
        floating.setAttribute('aria-hidden', 'true');
        floating.setAttribute('aria-label', 'Help search and categories');

        const inner = document.createElement('div');
        inner.className = 'bandara-faq-floating__inner';

        const searchWrap = document.createElement('label');
        searchWrap.className = 'bandara-faq-floating__search';
        searchWrap.innerHTML = '<span class="bandara-faq-floating__search-icon" aria-hidden="true"></span>';

        const floatingInput = document.createElement('input');
        floatingInput.type = 'search';
        floatingInput.className = 'bandara-faq-floating__input';
        floatingInput.placeholder = sourceInput.getAttribute('placeholder') || 'Search Help & FAQs';
        floatingInput.setAttribute('aria-label', sourceInput.getAttribute('aria-label') || 'Search Help & FAQs');
        floatingInput.autocomplete = 'off';
        floatingInput.value = sourceInput.value || '';
        searchWrap.append(floatingInput);
        inner.append(searchWrap);

        const linksWrap = document.createElement('nav');
        linksWrap.className = 'bandara-faq-floating__links';
        linksWrap.setAttribute('aria-label', 'Help categories');

        if (sourceNavigation) {
            Array.from(sourceNavigation.querySelectorAll('a, button, [role="tab"]')).forEach((control) => {
                const clone = createFloatingFaqLink(control, floating);
                if (clone && clone.textContent) {
                    linksWrap.append(clone);
                }
            });
        }

        if (linksWrap.children.length) {
            inner.append(linksWrap);
        }

        floating.append(inner);
        document.body.append(floating);

        // The original search and category nodes remain exactly where Blade put
        // them. Only their values/actions are mirrored into the floating clone.
        let syncingSearch = false;
        floatingInput.addEventListener('input', () => {
            if (syncingSearch) {
                return;
            }
            syncingSearch = true;
            nativeInputValueSetter(sourceInput, floatingInput.value);
            dispatchSearchInput(sourceInput);
            syncingSearch = false;
        });

        sourceInput.addEventListener('input', () => {
            if (syncingSearch) {
                return;
            }
            syncingSearch = true;
            floatingInput.value = sourceInput.value || '';
            syncingSearch = false;
        });

        main.querySelectorAll('[id]').forEach((element) => element.classList.add('bandara-faq-anchor'));

        let rafPending = false;
        const update = () => {
            rafPending = false;
            const headerStack = calculateFixedHeaderStack();
            const rail = faqLayoutRail(sourceInput, sourceNavigation, main);
            const headerGap = window.innerWidth <= 640 ? 8 : 10;
            const floatingTop = Math.max(headerGap, headerStack.bottom + headerGap);
            const floatingZ = headerStack.zIndex > 1 ? Math.max(1, headerStack.zIndex - 1) : 45;

            floating.style.setProperty('--bandara-faq-floating-top', `${floatingTop}px`);
            floating.style.setProperty('--bandara-faq-floating-left', `${rail.left}px`);
            floating.style.setProperty('--bandara-faq-floating-width', `${rail.width}px`);
            floating.style.setProperty('--bandara-faq-floating-z', `${floatingZ}`);
            document.documentElement.style.setProperty('--bandara-faq-floating-top', `${floatingTop}px`);

            const originalLayoutIntact = sourceInput.parentElement === originalSearchParent
                && (!sourceNavigation || sourceNavigation.parentElement === originalNavigationParent);
            document.documentElement.dataset.bandaraFaqOriginalLayout = originalLayoutIntact ? 'intact' : 'changed';

            if (!originalLayoutIntact) {
                floating.classList.remove('is-visible');
                floating.setAttribute('aria-hidden', 'true');
                return;
            }

            const boundaryRect = sourceBoundary.getBoundingClientRect();
            const shouldShow = window.scrollY > 24 && boundaryRect.bottom <= headerStack.bottom + 8;
            floating.classList.toggle('is-visible', shouldShow);
            floating.setAttribute('aria-hidden', shouldShow ? 'false' : 'true');
        };

        const scheduleUpdate = () => {
            if (rafPending) {
                return;
            }
            rafPending = true;
            window.requestAnimationFrame(update);
        };

        window.addEventListener('scroll', scheduleUpdate, { passive: true });
        window.addEventListener('resize', scheduleUpdate, { passive: true });
        window.addEventListener('load', scheduleUpdate, { once: true });

        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver(scheduleUpdate);
            resizeObserver.observe(floating);
            document.querySelectorAll('header, [role="banner"], [data-site-header], [data-main-header], .site-header, .main-header, .topbar, body > nav').forEach((header) => resizeObserver.observe(header));
        }

        if ('MutationObserver' in window) {
            const layoutObserver = new MutationObserver(scheduleUpdate);
            layoutObserver.observe(main, { childList: true, subtree: true });
        }

        scheduleUpdate();
    };

    /* ---------------------------------------------------------------------
     * Business access copy
     * ------------------------------------------------------------------ */
    const isBusinessPage = () => {
        const heading = normalizeText(document.querySelector('main h1, main h2, main [role="heading"]')?.textContent);
        const form = document.querySelector('main form[action*="business" i], main form[action*="application" i]');

        return /(^|\/)(business-account|business\/register|business\/application)(\/|$)/i.test(safePath())
            || Boolean(form)
            || heading.includes('business account')
            || heading.includes('already shop with bandara');
    };

    const directChildContaining = (parent, descendant) => {
        if (!parent || !descendant) {
            return null;
        }
        return Array.from(parent.children).find((child) => child === descendant || child.contains(descendant)) || null;
    };

    const initBusinessAccess = () => {
        if (!isBusinessPage()) {
            return;
        }

        const main = document.querySelector('main') || document.body;
        const injected = main.querySelector('[data-bandara-business-access]');
        const heading = Array.from(main.querySelectorAll('h2, h3, [role="heading"]')).find((element) => {
            return normalizeText(element.textContent).includes('already shop with bandara');
        });
        let panel = null;

        if (heading) {
            panel = heading.closest('section, article, aside, [data-business-access], div');
            if (panel === main || panel?.querySelector('form')) {
                panel = heading.parentElement;
            }
        }

        if (panel && injected && panel !== injected) {
            injected.remove();
        }
        panel = panel || injected;

        if (!panel) {
            panel = document.createElement('section');
            panel.innerHTML = '<h2>Already shop with Bandara?</h2>';
        }

        panel.classList.add('bandara-business-access');
        panel.setAttribute('data-bandara-business-access', '1');

        const panelHeading = panel.querySelector('h2, h3, [role="heading"]') || document.createElement('h2');
        panelHeading.textContent = 'Already shop with Bandara?';
        if (!panelHeading.parentElement) {
            panel.prepend(panelHeading);
        }

        const paragraphs = Array.from(panel.querySelectorAll(':scope > p'));
        let primary = paragraphs[0];
        if (!primary) {
            primary = document.createElement('p');
            panelHeading.insertAdjacentElement('afterend', primary);
        }
        primary.textContent = 'Use your existing customer login to request business access. After review and approval, eligible wholesale pricing and business ordering features are added to the same account.';

        let reassurance = panel.querySelector('.bandara-business-access__reassurance');
        if (!reassurance) {
            reassurance = document.createElement('p');
            reassurance.className = 'bandara-business-access__reassurance';
            panel.append(reassurance);
        }
        reassurance.innerHTML = '<strong>Same login.</strong> No new account. Your saved addresses and order history stay with you.';

        const form = main.querySelector('form[action*="business" i], form[action*="application" i], form');
        const formBlock = directChildContaining(main, form);
        if (formBlock && panel !== formBlock) {
            main.insertBefore(panel, formBlock);
        } else if (!main.contains(panel)) {
            main.prepend(panel);
        }
    };

    /* ---------------------------------------------------------------------
     * Welcome-credit banner
     * ------------------------------------------------------------------ */
    const initRewardBanner = () => {
        const banner = document.querySelector('[data-bandara-reward-banner]');
        if (!banner || !document.body) {
            return;
        }

        const host = document.querySelector('.page-body, [data-page-body], main, [role="main"]') || document.body;
        if (banner.parentElement !== host || banner !== host.firstElementChild) {
            host.insertBefore(banner, host.firstChild);
        }
        banner.dataset.bandaraRewardHost = host === document.body
            ? 'body'
            : (host.classList.contains('page-body') ? 'page-body' : 'main');

        const storageKey = 'bandara_reward_banner_dismissed_v1_3';
        try {
            if (window.sessionStorage.getItem(storageKey) === '1') {
                banner.hidden = true;
            }
        } catch (_) {
            // Storage may be disabled.
        }

        banner.querySelector('[data-bandara-reward-dismiss]')?.addEventListener('click', () => {
            banner.hidden = true;
            try {
                window.sessionStorage.setItem(storageKey, '1');
            } catch (_) {
                // No-op.
            }
        });
    };

    const init = () => {
        document.documentElement.dataset.bandaraLaunchFixes = VERSION;
        document.documentElement.dataset.bandaraUiSafe = VERSION;
        document.documentElement.dataset.bandaraUiHotfix = BUILD_MARKER;
        initRewardBanner();
        initNewsletterState();
        initThemeToggle();
        initHelpFloatingTools();
        initBusinessAccess();
        initProductDetailCards();
        initScrollReveal();
        window.addEventListener('load', initScrollReveal, { once: true });
        window.setTimeout(initScrollReveal, 320);
    };

    return { init };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', BandaraLaunchUi.init, { once: true });
} else {
    BandaraLaunchUi.init();
}
