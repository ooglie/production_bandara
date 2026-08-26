/*
 * Bandara storefront launch refinements — safe UI correction v1.4.
 *
 * Important design rule:
 * - Existing page elements are never moved out of their original containers.
 * - The Help & FAQ floating tools are non-destructive clones shown only after
 *   the original controls have scrolled out of view.
 */
const BandaraLaunchUi = (() => {
    const VERSION = '1.4';
    const themeIconMap = new WeakMap();
    let productObserver = null;
    let revealObserver = null;

    const safePath = () => window.location.pathname.replace(/\/+$/, '') || '/';
    const normalizeText = (value) => (value || '').replace(/\s+/g, ' ').trim().toLowerCase();
    const uniqueElements = (elements) => Array.from(new Set(elements.filter(Boolean)));

    const isTransactionalPage = () => /\/(admin|staff|checkout|cart|login|register|account|orders)(\/|$)/i.test(safePath());

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
            '.page-body > .section-gap',
            '.page-body > .section-gap-sm',
            'main > section',
            'main > article',
            '[role="main"] > section',
            '[role="main"] > article',
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

                const substantialDiv = child.tagName === 'DIV'
                    && child.getBoundingClientRect().height >= 80
                    && Boolean(child.querySelector('h2, h3, img, picture, [data-home-section]'))
                    && !child.matches('.container, .page-body, .page-content');

                if (['SECTION', 'ARTICLE'].includes(child.tagName) || classSignal || child.hasAttribute('data-section') || substantialDiv) {
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

        // Do not settle for one page-sized wrapper: reveal its meaningful direct
        // children instead so scrolling produces visible transitions.
        if (targets.length === 1 && targets[0].getBoundingClientRect().height > window.innerHeight * 1.8) {
            const wrapper = targets[0];
            const children = Array.from(wrapper.children).filter((child) => {
                return isRevealCandidate(child)
                    && (['SECTION', 'ARTICLE'].includes(child.tagName)
                        || Boolean(child.querySelector('h2, h3, img, picture')));
            });
            if (children.length >= 2) {
                targets = children;
            }
        }

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
            element.dataset.bandaraRevealBound = '1';
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
     * Theme icon — icon describes the action, not the current state
     * ------------------------------------------------------------------ */
    const setElementVisible = (element, visible) => {
        if (!element) {
            return;
        }

        element.hidden = !visible;
        element.setAttribute('aria-hidden', visible ? 'false' : 'true');
        const visibleDisplay = element.namespaceURI === 'http://www.w3.org/2000/svg' ? 'block' : 'inline-flex';
        element.style.setProperty('display', visible ? visibleDisplay : 'none', 'important');
    };

    const isDarkTheme = () => {
        const html = document.documentElement;
        const body = document.body;
        const declared = (html.getAttribute('data-theme') || body?.getAttribute('data-theme') || '').toLowerCase();

        return declared === 'dark'
            || html.classList.contains('dark')
            || Boolean(body?.classList.contains('dark'));
    };

    const identifyThemeIcons = (button, dark) => {
        const cached = themeIconMap.get(button);
        if (cached) {
            return cached;
        }

        let sun = button.querySelector('#sunIcon, [data-icon="sun"], [data-theme-icon="sun"], [data-lucide="sun"], .icon-sun, .sun-icon, [class*="sun" i], [id*="sun" i]');
        let moon = button.querySelector('#moonIcon, [data-icon="moon"], [data-theme-icon="moon"], [data-lucide="moon"], .icon-moon, .moon-icon, [class*="moon" i], [id*="moon" i]');

        if (!sun || !moon) {
            const icons = Array.from(button.querySelectorAll('svg'));
            if (icons.length === 2) {
                const visibleIcon = icons.find((icon) => {
                    const style = window.getComputedStyle(icon);
                    return !icon.hidden && style.display !== 'none' && style.visibility !== 'hidden';
                });
                const otherIcon = icons.find((icon) => icon !== visibleIcon);

                if (visibleIcon && otherIcon) {
                    if (dark) {
                        moon = moon || visibleIcon;
                        sun = sun || otherIcon;
                    } else {
                        sun = sun || visibleIcon;
                        moon = moon || otherIcon;
                    }
                }
            }
        }

        const mapping = { sun, moon };
        themeIconMap.set(button, mapping);
        return mapping;
    };

    const initThemeToggle = () => {
        const buttons = uniqueElements(Array.from(document.querySelectorAll([
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

        if (!buttons.length) {
            return;
        }

        let syncQueued = false;
        const sync = () => {
            syncQueued = false;
            const dark = isDarkTheme();

            buttons.forEach((button) => {
                const { sun, moon } = identifyThemeIcons(button, dark);
                setElementVisible(sun, dark);
                setElementVisible(moon, !dark);

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
        window.setTimeout(sync, 50);
        window.setTimeout(sync, 250);

        const observer = new MutationObserver(queueSync);
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class', 'data-theme'],
        });

        if (document.body) {
            observer.observe(document.body, {
                attributes: true,
                attributeFilter: ['class', 'data-theme'],
            });
        }

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
        const main = document.querySelector('main, [role="main"]');
        if (!main) {
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

        const h1 = main.querySelector('h1');
        const images = Array.from(main.querySelectorAll('img')).filter((image) => {
            if (image.closest('header, nav, footer, .bandara-faq-floating')) {
                return false;
            }
            const cardAncestor = image.closest(cardSelector);
            if (cardAncestor && !(h1 && cardAncestor.contains(h1)) && !(addControl && cardAncestor.contains(addControl))) {
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

        let wrapper = image.closest([
            '[data-product-image]',
            '[data-product-gallery-main]',
            '.product-image',
            '.product-main-image',
            '.product-gallery__main',
            '[class*="main-image" i]',
            'figure',
            'picture',
            'a',
            'button',
        ].join(','));

        if (!wrapper || wrapper === main) {
            wrapper = image.parentElement;
        }

        // Never apply the effect to a container which also owns thumbnails or the
        // whole product information column.
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

        const main = document.querySelector('main, [role="main"]');
        if (!main) {
            return;
        }

        document.documentElement.classList.add('bandara-product-detail-page');

        const primaryImage = findPrimaryProductImage(main);
        const primaryVisual = primaryVisualWrapper(primaryImage, main);
        if (primaryVisual) {
            primaryVisual.classList.add('bandara-product-detail-visual');
        }

        const candidates = new Set(Array.from(main.querySelectorAll('.product-card, [data-product-card], [class*="product-card" i], [class*="product-tile" i]')));
        Array.from(main.querySelectorAll('a[href]')).filter(isProductLikeLink).forEach((link) => {
            const card = nearestProductCard(link, main);
            if (card) {
                candidates.add(card);
            }
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

            card.classList.add('bandara-product-card');
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
            productObserver.observe(document.querySelector('main, [role="main"]') || document.body, {
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

    const calculateFixedHeaderBottom = () => {
        let bottom = 0;
        const candidates = uniqueElements(Array.from(document.querySelectorAll([
            'body > header',
            '[data-site-header]',
            '.site-header',
            'header.sticky',
            'header.fixed',
        ].join(','))));

        candidates.forEach((element) => {
            const rect = element.getBoundingClientRect();
            const style = window.getComputedStyle(element);
            const visible = rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
            const attached = rect.top <= 2 && ['fixed', 'sticky'].includes(style.position);
            if (visible && attached) {
                bottom = Math.max(bottom, Math.ceil(rect.bottom));
            }
        });

        return Math.max(0, bottom);
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

    const createFloatingFaqLink = (control) => {
        if (control instanceof HTMLAnchorElement && isSamePageHashLink(control)) {
            const link = document.createElement('a');
            link.className = 'bandara-faq-floating__link';
            link.href = control.getAttribute('href') || '#';
            link.textContent = (control.textContent || '').replace(/\s+/g, ' ').trim();
            link.addEventListener('click', () => {
                control.classList.add('is-active');
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

    const initHelpFloatingTools = () => {
        if (!isHelpPage() || document.querySelector('[data-bandara-faq-floating]')) {
            return;
        }

        const main = document.querySelector('main, [role="main"]');
        if (!main) {
            return;
        }

        const sourceInput = findHelpSearch(main);
        if (!sourceInput) {
            return;
        }

        const sourceNavigation = findFaqNavigation(main, sourceInput);
        const sourceBoundary = sourceNavigation || sourceInput.closest('form, [role="search"]') || sourceInput;

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
                const clone = createFloatingFaqLink(control);
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
            const headerBottom = calculateFixedHeaderBottom();
            document.documentElement.style.setProperty('--bandara-faq-floating-top', `${headerBottom}px`);

            const boundaryRect = sourceBoundary.getBoundingClientRect();
            const shouldShow = window.scrollY > 24 && boundaryRect.bottom <= headerBottom + 8;
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
        initRewardBanner();
        initNewsletterState();
        initThemeToggle();
        initHelpFloatingTools();
        initBusinessAccess();
        initProductDetailCards();
        initScrollReveal();
    };

    return { init };
})();

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', BandaraLaunchUi.init, { once: true });
} else {
    BandaraLaunchUi.init();
}
