const BandaraLaunchUi = (() => {
    const themeIconMap = new WeakMap();
    let productObserver = null;

    const safePath = () => window.location.pathname.replace(/\/+$/, '') || '/';
    const normalizeText = (value) => (value || '').replace(/\s+/g, ' ').trim().toLowerCase();

    const isTransactionalPage = () => /\/(admin|staff|checkout|cart|login|register|account|orders)(\/|$)/i.test(safePath());

    const initScrollReveal = () => {
        if (isTransactionalPage() || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const targets = Array.from(document.querySelectorAll([
            'main > section',
            'main > div > section',
            '[data-home-section]',
            '[data-bandara-reveal]',
        ].join(','))).filter((element) => {
            return !element.closest('form')
                && !element.matches('[data-no-reveal]')
                && !element.closest('[data-no-reveal]');
        });

        if (!targets.length || !('IntersectionObserver' in window)) {
            return;
        }

        document.documentElement.classList.add('bandara-reveal-enabled');

        targets.forEach((element, index) => {
            element.classList.add('bandara-scroll-reveal');
            element.style.setProperty('--bandara-reveal-delay', `${Math.min(index % 4, 3) * 45}ms`);
        });

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                entry.target.classList.toggle('is-bandara-visible', entry.isIntersecting);
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -7% 0px',
        });

        targets.forEach((element) => observer.observe(element));
    };

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
                    // The older control displayed the current-state icon. Use the
                    // declared theme once to identify otherwise anonymous SVGs.
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
        const buttons = Array.from(document.querySelectorAll([
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
        ].join(',')));

        if (!buttons.length) {
            return;
        }

        const sync = () => {
            const dark = isDarkTheme();

            buttons.forEach((button) => {
                const { sun, moon } = identifyThemeIcons(button, dark);

                // The icon communicates the action: moon in light mode, sun in dark mode.
                setElementVisible(sun, dark);
                setElementVisible(moon, !dark);

                const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
            });
        };

        const deferredSync = () => {
            window.requestAnimationFrame(sync);
            window.setTimeout(sync, 40);
            window.setTimeout(sync, 160);
        };

        sync();

        const observer = new MutationObserver(deferredSync);
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

        buttons.forEach((button) => button.addEventListener('click', deferredSync));
    };

    const productLinkSelector = [
        'a[href*="/product/"]',
        'a[href*="/products/"]',
        'a[href*="/shop/"]',
    ].join(',');

    const isOtherProductLink = (link) => {
        try {
            const target = new URL(link.href, window.location.origin);
            const current = new URL(window.location.href);
            return target.origin === current.origin
                && target.pathname.replace(/\/+$/, '') !== current.pathname.replace(/\/+$/, '');
        } catch (_) {
            return false;
        }
    };

    const looksLikeProductDetailPage = () => {
        const path = safePath();
        const pathMatch = /\/(products?|shop)\/[^/]+$/i.test(path);
        const domMatch = Boolean(document.querySelector([
            '[data-product-detail]',
            '[data-product-gallery]',
            'main form[action*="cart" i]',
            'main button[name*="cart" i]',
            'main [data-add-to-cart]',
        ].join(',')));

        return pathMatch || domMatch;
    };

    const findPrimaryProductVisual = (main) => {
        const explicitGallery = main.querySelector([
            '[data-product-gallery]',
            '[data-product-images]',
            '.product-gallery',
            '.product-images',
            '[class*="product-gallery" i]',
            '[class*="product-images" i]',
        ].join(','));
        const image = explicitGallery?.querySelector('img') || Array.from(main.querySelectorAll('img')).find((candidate) => {
            return !candidate.closest('.product-card, [data-product-card], [class*="product-card" i]')
                && !candidate.closest('nav, header, footer, [aria-label*="breadcrumb" i]');
        });

        if (!image) {
            return null;
        }

        let visual = image.closest('picture, figure, a, button, [data-product-image], [class*="image-wrap" i], [class*="image-card" i]');

        if (!visual || visual === main) {
            visual = image.parentElement;
        }

        // Avoid selecting an entire gallery containing thumbnails; keep the hover
        // interaction on the primary image wrapper only.
        while (visual && visual !== main && visual.querySelectorAll('img').length > 1) {
            const child = Array.from(visual.children).find((candidate) => candidate === image || candidate.contains(image));
            if (!child || child === image) {
                break;
            }
            visual = child;
        }

        return visual instanceof HTMLElement ? visual : null;
    };

    const markProductDetailCards = () => {
        if (!looksLikeProductDetailPage()) {
            return;
        }

        document.documentElement.classList.add('bandara-product-detail-page');
        const main = document.querySelector('main') || document.body;
        const pageHeading = main.querySelector('h1');
        const gallery = main.querySelector('[data-product-gallery], [data-product-detail]');
        const primaryVisual = findPrimaryProductVisual(main);
        const links = Array.from(main.querySelectorAll(productLinkSelector)).filter(isOtherProductLink);

        if (primaryVisual) {
            primaryVisual.classList.add('bandara-product-detail-visual');
        }

        const candidates = new Set(Array.from(main.querySelectorAll([
            '.product-card',
            '[data-product-card]',
            '[class*="product-card" i]',
        ].join(','))));

        links.forEach((link) => {
            const card = link.closest('.product-card, [data-product-card], [class*="product-card" i], article, li, .group');
            if (card) {
                candidates.add(card);
            }
        });

        candidates.forEach((card) => {
            if (!(card instanceof HTMLElement) || !main.contains(card)) {
                return;
            }

            if (pageHeading && (card.contains(pageHeading) || pageHeading.contains(card))) {
                return;
            }

            if (gallery && (card.contains(gallery) || gallery.contains(card))) {
                return;
            }

            if (card.matches('[data-product-detail]') || card.querySelector('h1, [data-product-gallery], [data-product-detail]')) {
                return;
            }

            const hasImage = Boolean(card.querySelector('img, picture'));
            const hasOtherProduct = Array.from(card.querySelectorAll(productLinkSelector)).some(isOtherProductLink);
            const hasCardSignal = card.matches('.product-card, [data-product-card], [class*="product-card" i]');

            if (hasImage && (hasOtherProduct || hasCardSignal)) {
                card.classList.add('bandara-product-card');
            }
        });
    };

    const initProductDetailCards = () => {
        if (!looksLikeProductDetailPage()) {
            return;
        }

        markProductDetailCards();

        if ('MutationObserver' in window && !productObserver) {
            productObserver = new MutationObserver(() => markProductDetailCards());
            productObserver.observe(document.querySelector('main') || document.body, {
                childList: true,
                subtree: true,
            });
        }
    };

    const isNewsletterSubscribeAction = (action) => {
        let pathname = action;

        try {
            pathname = new URL(action, window.location.origin).pathname;
        } catch (_) {
            pathname = action.split(/[?#]/, 1)[0];
        }

        const segments = pathname.toLowerCase().split('/').filter(Boolean);

        return segments.includes('newsletter')
            && segments.includes('subscribe')
            && !segments.includes('unsubscribe');
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

    const isHelpPage = () => {
        const heading = normalizeText(document.querySelector('main h1, main [role="heading"][aria-level="1"]')?.textContent);
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
                || /(search|help|faq|question|answer)/i.test(searchable);
        }) || null;
    };

    const compactRoot = (element, main) => {
        if (!element) {
            return null;
        }

        const semantic = element.closest('form, [role="search"], nav, [role="tablist"], [data-faq-search], [data-faq-nav]');
        if (semantic && semantic !== main) {
            return semantic;
        }

        let node = element.parentElement;
        while (node && node !== main && node !== document.body) {
            const interactiveCount = node.querySelectorAll('input, button, a, select').length;
            const contentCount = node.querySelectorAll('details, article, [data-faq-item]').length;
            if (interactiveCount <= 16 && contentCount === 0) {
                return node;
            }
            node = node.parentElement;
        }

        return element.parentElement;
    };

    const isSamePageHashLink = (control) => {
        if (!control?.matches?.('a')) {
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

    const navigationControlCount = (candidate) => {
        return Array.from(candidate.querySelectorAll('a, button, [role="tab"]')).filter((control) => {
            if (control.matches('a')) {
                return isSamePageHashLink(control);
            }

            return control.matches('[role="tab"], button[aria-controls], button[data-target], button[data-faq-target]');
        }).length;
    };

    const findFaqNavigation = (main, search) => {
        const candidates = Array.from(main.querySelectorAll([
            'nav',
            '[role="tablist"]',
            '[data-faq-nav]',
            '[class*="faq-nav" i]',
            '[class*="faq-link" i]',
            '[class*="faq-tabs" i]',
            '[class*="category-nav" i]',
            '[class*="section-link" i]',
        ].join(',')));

        const semanticMatch = candidates.find((candidate) => {
            if (candidate.contains(search)) {
                return false;
            }

            const contentCount = candidate.querySelectorAll('details, article, [data-faq-item]').length;
            return navigationControlCount(candidate) >= 2 && contentCount === 0;
        });

        if (semanticMatch) {
            return semanticMatch;
        }

        // Some FAQ templates render category links in a plain div rather than nav.
        // Group anchor controls by their nearest compact parent and choose the first
        // genuine group instead of relying on a specific class name.
        const grouped = new Map();
        const controls = Array.from(main.querySelectorAll('a[href*="#"], button[aria-controls], [role="tab"]'));
        controls.forEach((control) => {
            if (control.matches('a') && !isSamePageHashLink(control)) {
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

    const sharedToolContainer = (first, second, main) => {
        if (!first || !second) {
            return null;
        }

        let node = first;
        while (node && node !== main && node !== document.body) {
            if (node.contains(second)) {
                const contentCount = node.querySelectorAll('details, article, [data-faq-item]').length;
                const headingCount = node.querySelectorAll('h2, h3').length;
                if (contentCount === 0 && headingCount <= 2) {
                    return node;
                }
            }
            node = node.parentElement;
        }

        return null;
    };

    const markStickyOverflowAncestors = (sticky, main) => {
        let node = sticky.parentElement;

        while (node && node !== document.body) {
            const style = window.getComputedStyle(node);
            const blocksSticky = /(auto|scroll|hidden|clip)/.test(`${style.overflow} ${style.overflowY}`);
            const isNotRealScroller = node.scrollHeight <= node.clientHeight + 4;

            if (blocksSticky && isNotRealScroller) {
                node.classList.add('bandara-faq-overflow-host');
            }

            if (node === main) {
                break;
            }
            node = node.parentElement;
        }
    };

    const calculateStickyOffset = () => {
        let bottom = 0;
        const candidates = Array.from(document.querySelectorAll('body > header, [data-site-header], header.sticky, header.fixed, .site-header, .top-bar'));

        candidates.forEach((element) => {
            const rect = element.getBoundingClientRect();
            const style = window.getComputedStyle(element);
            const visible = rect.height > 0 && style.display !== 'none' && style.visibility !== 'hidden';
            const attachedToTop = rect.top <= 2 && ['fixed', 'sticky'].includes(style.position);

            if (visible && attachedToTop) {
                bottom = Math.max(bottom, Math.ceil(rect.bottom));
            }
        });

        document.documentElement.style.setProperty('--bandara-sticky-offset', `${Math.max(0, bottom)}px`);
    };

    const finishFaqSticky = (sticky, main) => {
        sticky.classList.add('bandara-faq-sticky');
        sticky.setAttribute('data-bandara-faq-sticky', '1');
        main.querySelectorAll('[id]').forEach((element) => element.classList.add('bandara-faq-anchor'));
        markStickyOverflowAncestors(sticky, main);
        calculateStickyOffset();
        window.requestAnimationFrame(calculateStickyOffset);
        window.setTimeout(calculateStickyOffset, 120);
        window.addEventListener('resize', calculateStickyOffset, { passive: true });
    };

    const initHelpStickyTools = () => {
        if (!isHelpPage()) {
            return;
        }

        const main = document.querySelector('main, [role="main"]');
        if (!main) {
            return;
        }

        const search = findHelpSearch(main);
        if (!search) {
            return;
        }

        const existingSticky = main.querySelector('[data-bandara-faq-sticky]');
        if (existingSticky) {
            finishFaqSticky(existingSticky, main);
            return;
        }

        const searchRoot = compactRoot(search, main);
        const navigation = findFaqNavigation(main, search);
        const navRoot = compactRoot(navigation, main);
        const shared = sharedToolContainer(searchRoot, navRoot, main);

        if (shared) {
            finishFaqSticky(shared, main);
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'bandara-faq-sticky';
        wrapper.setAttribute('data-bandara-faq-sticky', '1');

        // Keep controls in their current local context whenever they are siblings;
        // this preserves Alpine scopes and existing event listeners.
        if (searchRoot && navRoot && searchRoot.parentElement === navRoot.parentElement) {
            searchRoot.parentElement.insertBefore(wrapper, searchRoot);
        } else {
            const firstDirectChild = Array.from(main.children).find((child) => child.contains(search));
            if (firstDirectChild) {
                main.insertBefore(wrapper, firstDirectChild);
            } else {
                main.prepend(wrapper);
            }
        }

        if (searchRoot && searchRoot !== main && !wrapper.contains(searchRoot)) {
            wrapper.append(searchRoot);
        } else {
            wrapper.append(search);
        }

        if (navRoot && navRoot !== main && navRoot !== searchRoot && !wrapper.contains(navRoot)) {
            wrapper.append(navRoot);
        }

        finishFaqSticky(wrapper, main);
    };

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

    const initRewardBanner = () => {
        const banner = document.querySelector('[data-bandara-reward-banner]');

        if (!banner || !document.body) {
            return;
        }

        // The storefront has a fixed header and a visible .page-body content host.
        // Keep the reward at the top of that host: visible on desktop, outside any
        // mobile-only navigation wrapper, and below the fixed header/trust strip.
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
            // Storage may be disabled; the banner still remains usable.
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
        document.documentElement.dataset.bandaraLaunchFixes = '1.3';
        initRewardBanner();
        initNewsletterState();
        initThemeToggle();
        initHelpStickyTools();
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
