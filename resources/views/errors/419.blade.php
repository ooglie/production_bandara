@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('home') ? route('home') : url('/');
    $shopUrl = \Illuminate\Support\Facades\Route::has('shop.index') ? route('shop.index') : url('/shop');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 | Bandara</title>
    <style>
        :root {
            --bg: #f8f4ee;
            --card: #fffdf9;
            --ink: #2e241d;
            --muted: #7c6f65;
            --line: #e8ddd1;
            --accent: #7a4f34;
            --accent-soft: #f1e7dc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem;
            background: radial-gradient(circle at top left, #fff7ef, var(--bg) 45%, #efe3d6);
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .bandara-error {
            width: min(100%, 720px);
            border: 1px solid var(--line);
            border-radius: 28px;
            background: rgba(255, 253, 249, .96);
            box-shadow: 0 28px 80px rgba(50, 38, 28, .12);
            padding: clamp(1.4rem, 4vw, 2.6rem);
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            padding: .45rem .8rem;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 1rem 0 .5rem;
            font-size: clamp(2rem, 6vw, 4rem);
            line-height: 1;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 1rem;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: .75rem;
            margin-top: 1.5rem;
        }
        a, button {
            appearance: none;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: .75rem 1.05rem;
            font-size: .9rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .primary { background: var(--accent); color: #fff; }
        .secondary { background: #fff; color: var(--ink); border-color: var(--line); }
        .footer-note { margin-top: 1.5rem; font-size: .82rem; }
        @media (max-width: 520px) {
            body { padding: 1rem; }
            .actions a, .actions button { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <main class="bandara-error" role="main">
        <span class="eyebrow">419 · Session expired</span>
        <h1>Your session has expired.</h1>
        <p>For your security, this page needs to be refreshed before you continue. This often happens when checkout or a form has been open for a while.</p>
        <div class="actions">
            <button type="button" class="primary" onclick="window.location.reload()">Refresh page</button>
            <a class="secondary" href="{{ $shopUrl }}">Continue shopping</a>
            <a class="secondary" href="{{ $homeUrl }}">Go home</a>
        </div>
        <p class="footer-note">Bandara · Quality you can freeze on.</p>
    </main>
</body>
</html>
