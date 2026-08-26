<?php if (! $__env->hasRenderedOnce('b44a8969-cd38-4c2c-a864-64f8f0e68d67')): $__env->markAsRenderedOnce('b44a8969-cd38-4c2c-a864-64f8f0e68d67'); ?>
    <style>
        /*
         * Bandara homepage Phase 1 — hero-only presentation.
         * Header density and product-card elevation now live in the shared
         * storefront refinement so they also apply to /shop.
         */
        .bandara-phase1-hero-button {
            transition:
                transform 160ms ease,
                border-color 180ms ease,
                background-color 180ms ease,
                box-shadow 180ms ease;
        }

        .bandara-phase1-hero-button svg {
            transition: transform 180ms ease;
        }

        .bandara-phase1-hero-image {
            transform: scale(1);
            transition: transform 700ms cubic-bezier(.2, .75, .25, 1);
        }

        @media (hover: hover) and (pointer: fine) {
            .bandara-phase1-hero-button:hover {
                transform: translateY(-1px);
            }

            .bandara-phase1-hero-button-primary:hover svg {
                transform: translateX(3px);
            }

            .bandara-phase1-hero-visual:hover .bandara-phase1-hero-image {
                transform: scale(1.018);
            }
        }

        .bandara-phase1-hero-button:active {
            transform: translateY(0) scale(.99);
        }

        @media (prefers-reduced-motion: no-preference) {
            @keyframes bandara-phase1-rise {
                from { opacity: 0; transform: translateY(12px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .bandara-phase1-hero-copy {
                animation: bandara-phase1-rise 440ms ease both;
            }

            .bandara-phase1-hero-visual {
                animation: bandara-phase1-rise 480ms 70ms ease both;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .bandara-phase1-hero-copy,
            .bandara-phase1-hero-visual,
            .bandara-phase1-hero-button,
            .bandara-phase1-hero-button svg,
            .bandara-phase1-hero-image {
                animation: none !important;
                transition-duration: .01ms !important;
                transform: none !important;
            }
        }
    </style>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/home/partials/phase-one-enhancements.blade.php ENDPATH**/ ?>