<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Page not found | <?php echo e(config('app.name', 'Bandara')); ?></title>
    <style>
        :root {
            --bandara-cream: #fbf7ef;
            --bandara-card: #ffffff;
            --bandara-text: #2d2118;
            --bandara-muted: #75685f;
            --bandara-border: #eadfce;
            --bandara-primary: #7a4f34;
            --bandara-primary-hover: #68412a;
            --bandara-soft: #f4ece0;
            --bandara-shadow: 0 24px 70px rgba(45, 33, 24, 0.12);
            --bandara-radius: 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--bandara-text);
            background:
                radial-gradient(circle at top left, rgba(122, 79, 52, 0.12), transparent 34rem),
                linear-gradient(135deg, var(--bandara-cream) 0%, #fffdf9 100%);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .bandara-error-shell {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 18px;
        }

        .bandara-error-card {
            width: min(960px, 100%);
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 0;
            overflow: hidden;
            border: 1px solid var(--bandara-border);
            border-radius: var(--bandara-radius);
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--bandara-shadow);
        }

        .bandara-error-visual {
            position: relative;
            min-height: 420px;
            padding: 42px;
            background:
                linear-gradient(160deg, rgba(122, 79, 52, 0.95), rgba(84, 51, 31, 0.95)),
                radial-gradient(circle at top right, rgba(255, 255, 255, 0.24), transparent 16rem);
            color: #fffaf3;
        }

        .bandara-error-mark {
            width: 74px;
            height: 74px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.24);
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.08em;
        }

        .bandara-error-code {
            margin: 92px 0 0;
            font-size: clamp(78px, 12vw, 132px);
            line-height: 0.82;
            font-weight: 900;
            letter-spacing: -0.08em;
        }

        .bandara-error-small {
            margin-top: 18px;
            max-width: 320px;
            color: rgba(255, 250, 243, 0.78);
            font-size: 15px;
            line-height: 1.65;
        }

        .bandara-error-content {
            padding: clamp(32px, 6vw, 64px);
        }

        .bandara-error-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            padding: 8px 13px;
            border-radius: 999px;
            background: var(--bandara-soft);
            color: var(--bandara-primary);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .bandara-error-title {
            margin: 0;
            font-size: clamp(32px, 5vw, 48px);
            line-height: 1.05;
            letter-spacing: -0.055em;
        }

        .bandara-error-copy {
            margin: 18px 0 0;
            color: var(--bandara-muted);
            font-size: 16px;
            line-height: 1.75;
        }

        .bandara-error-search {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            padding: 8px;
            border: 1px solid var(--bandara-border);
            border-radius: 18px;
            background: #fff;
        }

        .bandara-error-search input {
            min-width: 0;
            flex: 1;
            border: 0;
            outline: 0;
            padding: 12px 10px;
            color: var(--bandara-text);
            font-size: 15px;
            background: transparent;
        }

        .bandara-error-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 24px;
        }

        .bandara-error-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
        }

        .bandara-error-button:hover {
            transform: translateY(-1px);
        }

        .bandara-error-button-primary {
            background: var(--bandara-primary);
            color: #fff;
            box-shadow: 0 12px 24px rgba(122, 79, 52, 0.2);
        }

        .bandara-error-button-primary:hover {
            background: var(--bandara-primary-hover);
        }

        .bandara-error-button-secondary {
            background: #fff;
            color: var(--bandara-text);
            border-color: var(--bandara-border);
        }

        .bandara-error-help {
            margin-top: 26px;
            color: var(--bandara-muted);
            font-size: 13px;
            line-height: 1.65;
        }

        .bandara-error-help a {
            color: var(--bandara-primary);
            font-weight: 700;
        }

        @media (max-width: 760px) {
            .bandara-error-card {
                grid-template-columns: 1fr;
            }

            .bandara-error-visual {
                min-height: 230px;
                padding: 30px;
            }

            .bandara-error-code {
                margin-top: 48px;
            }

            .bandara-error-search {
                flex-direction: column;
            }

            .bandara-error-search .bandara-error-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="bandara-error-shell" role="main">
        <section class="bandara-error-card" aria-labelledby="bandara-error-title">
            <div class="bandara-error-visual" aria-hidden="true">
                <div class="bandara-error-mark">B</div>
                <div class="bandara-error-code">404</div>
                <p class="bandara-error-small">
                    The page may have moved, the link may be old, or the product may no longer be available.
                </p>
            </div>

            <div class="bandara-error-content">
                <div class="bandara-error-kicker">Page not found</div>
                <h1 id="bandara-error-title" class="bandara-error-title">
                    We could not find that page.
                </h1>
                <p class="bandara-error-copy">
                    The page you are looking for is not available. You can continue shopping, search the catalogue, or go back to the previous page.
                </p>

                <form class="bandara-error-search" method="GET" action="<?php echo e(url('/shop')); ?>">
                    <input
                        type="search"
                        name="q"
                        placeholder="Search products"
                        aria-label="Search products"
                        value="<?php echo e(request('q')); ?>">
                    <button type="submit" class="bandara-error-button bandara-error-button-primary">
                        Search
                    </button>
                </form>

                <div class="bandara-error-actions">
                    <a href="<?php echo e(url('/shop')); ?>" class="bandara-error-button bandara-error-button-primary">
                        Continue shopping
                    </a>
                    <a href="<?php echo e(url('/')); ?>" class="bandara-error-button bandara-error-button-secondary">
                        Go to homepage
                    </a>
                    <button type="button" class="bandara-error-button bandara-error-button-secondary" onclick="window.history.length > 1 ? window.history.back() : window.location.assign('<?php echo e(url('/')); ?>')">
                        Go back
                    </button>
                </div>

                <p class="bandara-error-help">
                    If you reached this page from a product or order link, please contact support and share the link you opened.
                </p>
            </div>
        </section>
    </main>
</body>
</html>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/errors/404.blade.php ENDPATH**/ ?>