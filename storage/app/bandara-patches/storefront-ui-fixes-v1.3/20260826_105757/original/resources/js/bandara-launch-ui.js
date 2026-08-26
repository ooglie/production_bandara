const BandaraLaunchUi = (() => {
    const themeIconMap = new WeakMap();

    const safePath = () => window.location.pathname.replace(/\/+$/, '') || '/';

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

    const normalizeText = (value) => (value || '').replace(/\s+/g, ' ').trim().toLowerCase();

    const initProductDetailCards = () => {
        const path = safePath();
        const looksLikeProductDetail = /\/products?\/[^/]+/i.test(path)
            || Boolean(document.querySelector('[data-product-detail], [data-product-gallery]'));

        if (!looksLikeProductDetail) {
            return;
        }

        const relatedHeading = Array.from(document.querySelectorAll('main h2, main h3')).filter((heading) => {
            return /(related|similar|recommended|you may also|more to explore|recently viewed|bestseller|new arrival)/i.test(heading.textContent || '');
        });

        const markCardsIn = (section) => {
            if (!section || section.querySelector('h1') || section.querySelector('[data-product-gallery]')) {
                return;
            }

            const productLinks = Array.from(section.querySelectorAll('a[href*="/product/"], a[href*="/products/"], a[href*="/shop/"]')).filter((link) => {
                try {
                    return new URL(link.href, window.location.origin).pathname !== window.location.pathname;
                } catch (_) {
                    return false;
                }
            });

            if (productLinks.length < 1) {
                return;
            }

            productLinks.forEach((productLink) => {
                const card = productLink.closest('[data-product-card], article, li, .group');

                if (!card || !section.contains(card) || card.querySelector('h1') || card.querySelector('[data-product-gallery]')) {
                    return;
                }

                card.classList.add('bandara-product-card');
            });
        };

        relatedHeading.forEach((heading) => {
            markCardsIn(heading.closest('section') || heading.parentElement);
        });

        // Fallback for product-detail templates whose related-product section has
        // no conventional heading but clearly contains multiple other products.
        document.querySelectorAll('main section').forEach((section) => {
            const otherProductLinks = Array.from(section.querySelectorAll('a[href*="/product/"], a[href*="/products/"], a[href*="/shop/"]')).filter((link) => {
                try {
                    return new URL(link.href, window.location.origin).pathname !== window.location.pathname;
                } catch (_) {
                    return false;
                }
            });

            if (otherProductLinks.length >= 2) {
                markCardsIn(section);
            }
        });
    };

    const setElementVisible = (element, visible) => {
        if (!element) {
            return;
        }

        element.hidden = !visible;
        element.setAttribute('aria-hidden', visible ? 'false' : 'true');
        const visibleDisplay = element.namespaceURI === 'http://www.w3.org/2000/svg' ? 'inline-block' : 'inline-flex';
        element.style.setProperty('display', visible ? visibleDisplay : 'none', 'important');
    };

    const identifyThemeIcons = (button, dark) => {
        const cached = themeIconMap.get(button);

        if (cached) {
            return cached;
        }

        let sun = button.querySelector([
            '[data-icon="sun"]',
            '[data-theme-icon="sun"]',
            '[data-lucide="sun"]',
            '.icon-sun',
            '.sun-icon',
            '[class*="sun"]',
            '[id*="sun"]',
        ].join(','));

        let moon = button.querySelector([
            '[data-icon="moon"]',
            '[data-theme-icon="moon"]',
            '[data-lucide="moon"]',
            '.icon-moon',
            '.moon-icon',
            '[class*="moon"]',
            '[id*="moon"]',
        ].join(','));

        if (!sun || !moon) {
            const icons = Array.from(button.querySelectorAll('svg')).filter((icon) => !icon.closest('[hidden]'));
            const allIcons = icons.length >= 2 ? icons : Array.from(button.querySelectorAll('svg'));

            if (allIcons.length === 2) {
                const visibleIcon = allIcons.find((icon) => {
                    const style = window.getComputedStyle(icon);
                    return !icon.hidden && style.display !== 'none' && style.visibility !== 'hidden';
                });
                const otherIcon = allIcons.find((icon) => icon !== visibleIcon);

                // The existing toggle shows the current state. Use that known state
                // once to identify anonymous SVGs, then cache the mapping.
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
        const buttons = Array.from(document.querySelectorAll([
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
            const dark = document.documentElement.classList.contains('dark');

            buttons.forEach((button) => {
                const { sun, moon } = identifyThemeIcons(button, dark);

                // The icon communicates the action: moon in light mode, sun in dark mode.
                if (sun || moon) {
                    setElementVisible(sun, dark);
                    setElementVisible(moon, !dark);
                }

                const label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
            });
        };

        sync();

        const observer = new MutationObserver(sync);
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class', 'data-theme'],
        });

        buttons.forEach((button) => button.addEventListener('click', () => window.requestAnimationFrame(sync)));
    };

    const initNewsletterState = () => {
        const meta = document.querySelector('meta[name="bandara-newsletter-subscribed"]');

        if (!meta || meta.content !== '1') {
            return;
        }

        const forms = Array.from(document.querySelectorAll('form[action]')).filter((form) => {
            const action = form.getAttribute('action') || '';
            return /newsletter/i.test(action) && /subscribe/i.test(action);
        });

        forms.forEach((form) => {
            if (form.dataset.bandaraNewsletterReplaced === '1') {
                return;
            }

            form.dataset.bandaraNewsletterReplaced = '1';
            const confirmation = document.createElement('div');
            confirmation.className = 'bandara-newsletter-confirmation';
            confirmation.setAttribute('role', 'status');
            confirmation.innerHTML = [
                '<strong>You’re on the Bandara list</strong>',
                '<span>Thank you for subscribing. Look out for recipes, new arrivals, product updates and selected offers in your inbox.</span>',
                '<a href="/recipes">View this week’s recipe</a>',
            ].join('');
            form.replaceWith(confirmation);
        });
    };

    const commonAncestorContainingAnchors = (searchElement) => {
        let element = searchElement.parentElement;

        while (element && element !== document.body) {
            if (element.tagName === 'MAIN') {
                break;
            }

            const anchorCount = element.querySelectorAll('a[href^="#"]').length;
            const height = element.getBoundingClientRect().height;

            if (anchorCount >= 2 && height > 0 && height <= 520) {
                return element;
            }

            element = element.parentElement;
        }

        return searchElement.closest('form, [role="search"], section, aside, nav, div');
    };

    const initHelpStickyTools = () => {
        const path = safePath();
        const pageText = normalizeText(document.querySelector('main h1')?.textContent);

        if (!/(help|faq)/i.test(path) && !pageText.includes('help') && !pageText.includes('faq')) {
            return;
        }

        const search = document.querySelector([
            'main input[type="search"]',
            'main input[placeholder*="search" i]',
            'main [role="search"] input',
        ].join(','));

        if (!search) {
            return;
        }

        const sticky = commonAncestorContainingAnchors(search);

        if (sticky) {
            sticky.classList.add('bandara-faq-sticky');
        }

        document.querySelectorAll('main [id]').forEach((element) => {
            element.classList.add('bandara-faq-anchor');
        });

        const header = document.querySelector('body > header, [data-site-header], header.sticky, header.fixed');
        const updateOffset = () => {
            const headerHeight = header ? Math.ceil(header.getBoundingClientRect().height) : 72;
            document.documentElement.style.setProperty('--bandara-sticky-offset', `${headerHeight}px`);
        };

        updateOffset();
        window.addEventListener('resize', updateOffset, { passive: true });

        if (header && 'ResizeObserver' in window) {
            const headerObserver = new ResizeObserver(updateOffset);
            headerObserver.observe(header);
        }
    };

    const initBusinessCopyFallback = () => {
        if (!/business-account/i.test(safePath())) {
            return;
        }

        const heading = Array.from(document.querySelectorAll('main h2, main h3')).find((element) => {
            return normalizeText(element.textContent) === 'already shop with bandara?';
        });

        if (!heading) {
            return;
        }

        const paragraph = heading.parentElement?.querySelector('p');

        if (paragraph && /do not create another account/i.test(paragraph.textContent || '')) {
            paragraph.textContent = 'You can request business access using your existing customer account. Simply sign in and submit your business details for review. Once approved, eligible wholesale pricing and business ordering features will be added to the same account—without creating a new login or losing your saved addresses and order history.';
        }
    };

    const initRewardDismissal = () => {
        const banner = document.querySelector('[data-bandara-reward-banner]');

        if (!banner) {
            return;
        }

        try {
            if (window.sessionStorage.getItem('bandara_reward_banner_dismissed') === '1') {
                banner.hidden = true;
                return;
            }
        } catch (_) {
            // Storage may be disabled; dismissal still works for the current page.
        }

        banner.querySelector('[data-bandara-reward-dismiss]')?.addEventListener('click', () => {
            banner.hidden = true;

            try {
                window.sessionStorage.setItem('bandara_reward_banner_dismissed', '1');
            } catch (_) {
                // No-op.
            }
        });
    };

    const init = () => {
        initRewardDismissal();
        initNewsletterState();
        initThemeToggle();
        initHelpStickyTools();
        initBusinessCopyFallback();
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
