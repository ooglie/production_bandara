<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

final class BandaraLaunchUiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $this->rememberSuccessfulNewsletterSubscription($request, $response);
        $this->forgetSuccessfulNewsletterUnsubscription($request, $response);

        if (! $this->shouldTransformHtml($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        $content = $this->replaceLaunchCopy($content);
        $content = $this->replaceRetiredBranding($content);
        $content = $this->injectStateMeta($content, $this->isNewsletterSubscribed($request));

        if ($this->shouldShowRewardBanner($request)) {
            $content = $this->injectRewardBanner($content, $this->rewardBanner());
        }

        if ($this->isBusinessPage($request) && ! str_contains($content, 'data-bandara-business-access')) {
            $content = $this->injectBusinessAccessPanel($content, $this->businessAccessPanel());
        }

        if (
            $this->isAboutPage($request)
            && ! str_contains($content, 'Parag Parulekar')
            && ! str_contains($content, 'Maytira Mala')
        ) {
            $content = $this->injectBeforeMainClose($content, $this->founderSection());
        }

        $response->setContent($content);
        $response->headers->remove('Content-Length');
        $response->headers->remove('Content-MD5');
        $response->headers->remove('ETag');
        $response->setVary('Cookie', false);
        $response->headers->set('X-Bandara-Launch-UI', '1.3');

        return $response;
    }

    private function shouldTransformHtml(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        if (
            $request->is('admin*')
            || $request->is('staff*')
            || $request->is('api*')
            || $request->is('_debugbar*')
            || $request->is('telescope*')
            || $request->is('horizon*')
        ) {
            return false;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }

        if ((string) $response->headers->get('Content-Encoding') !== '') {
            return false;
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type'));

        return $contentType === '' || str_contains($contentType, 'text/html');
    }

    private function replaceRetiredBranding(string $html): string
    {
        return str_ireplace(
            [
                'powered by Frozen by Bandara',
                'Powered by Frozen by Bandara',
                'Frozen by Bandara',
                'Bandara by Maytira',
                'Bandara Frozen',
            ],
            [
                'from Bandara',
                'From Bandara',
                'Bandara',
                'Bandara',
                'Bandara',
            ],
            $html,
        );
    }

    private function replaceLaunchCopy(string $html): string
    {
        $qualityCopy = 'Premium meats, seafood, cheese and speciality foods for homes, chefs and businesses—with GST-ready invoicing and a seamless mobile-first shopping experience from Bandara.';
        $html = str_ireplace(
            [
                'Quality frozen products, GST-ready invoicing, and a mobile-first shopping experience powered by Frozen by Bandara.',
                'Quality frozen products, GST‑ready invoicing, and a mobile‑first shopping experience powered by Frozen by Bandara.',
            ],
            $qualityCopy,
            $html,
        );
        $qualityPattern = '~Quality\s+frozen\s+products,\s*GST(?:-|‑)ready\s+invoicing,\s*and\s+a\s+mobile(?:-|‑)first\s+shopping\s+experience\s+powered\s+by\s+Frozen\s+by\s+Bandara\.~iu';
        $html = preg_replace($qualityPattern, $qualityCopy, $html, 1) ?? $html;

        $html = str_ireplace(
            [
                'Chef Spotlight',
                'Chef spotlight',
                'Recipe of the Day',
                'Recipe of the day',
                'Browse chef picks',
                'Use this space for serving notes, quick prep guidance, and practical suggestions that make the product feel easier to cook and easier to order again.',
            ],
            [
                'Bandara Kitchen',
                'Bandara Kitchen',
                'Today’s Recipes',
                'Today’s recipes',
                'Explore Bandara Kitchen',
                'Discover a fresh selection of recipes chosen for today, with practical ideas and products that make them easier to prepare.',
            ],
            $html,
        );

        $html = str_replace(['"recipe_limit": 1', '"recipe_limit": 3'], '"recipe_limit": 4', $html);
        $html = str_ireplace(
            [
                'Recipe of the Week',
                'Recipe of the week',
                'Discover a carefully selected weekly recipe, a useful kitchen tip, and products that make it easier to prepare.',
            ],
            [
                'Today’s Recipes',
                'Today’s recipes',
                'Discover a fresh selection of recipes chosen for today, with practical ideas and products that make them easier to prepare.',
            ],
            $html,
        );

        $businessPattern = '~Do\s+not\s+create\s+another\s+account\.\s*Sign\s+in\s+with\s+the\s+existing\s+B2C\s+customer\s+account\s+and\s+submit\s+the\s+business\s+application\.\s*Addresses,\s+orders\s+and\s+account\s+history\s+remain\s+attached\s+to\s+the\s+same\s+login\.~iu';
        $businessCopy = 'You can request business access using your existing customer account. Simply sign in and submit your business details for review. Once approved, eligible wholesale pricing and business ordering features will be added to the same account—without creating a new login or losing your saved addresses and order history.';

        return preg_replace($businessPattern, $businessCopy, $html, 1) ?? $html;
    }

    private function injectStateMeta(string $html, bool $newsletterSubscribed): string
    {
        if (str_contains($html, 'name="bandara-newsletter-subscribed"')) {
            return $html;
        }

        $meta = sprintf(
            '<meta name="bandara-newsletter-subscribed" content="%s">'."\n".
            '<meta name="bandara-launch-ui" content="1.3">'."\n",
            $newsletterSubscribed ? '1' : '0',
        );

        return preg_replace('~</head>~i', $meta.'</head>', $html, 1) ?? $html;
    }

    private function isNewsletterSubscribed(Request $request): bool
    {
        if ($request->attributes->getBoolean('bandara_newsletter_unsubscribed')) {
            return false;
        }

        try {
            $user = Auth::guard('web')->user();

            // An authenticated customer's exact account email is authoritative.
            // A guest cookie from another email must never hide the form for the
            // currently signed-in account.
            if (is_object($user)) {
                $email = trim((string) ($user->email ?? ''));

                if ($email === '') {
                    return false;
                }

                return DB::table('newsletter_subscribers')
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->where('status', 'active')
                    ->exists();
            }
        } catch (Throwable) {
            return false;
        }

        if ($request->cookies->get('bandara_newsletter_subscribed') === '1') {
            return true;
        }

        try {
            return $request->hasSession()
                && $request->session()->get('newsletter_subscribed') === true;
        } catch (Throwable) {
            // Session availability differs between route groups; fail open to normal form display.
            return false;
        }
    }

    private function rememberSuccessfulNewsletterSubscription(Request $request, Response $response): void
    {
        if (
            ! $request->isMethod('POST')
            || ! $request->is(
                'newsletter/subscribe',
                'newsletter/subscribe/*',
                '*/newsletter/subscribe',
                '*/newsletter/subscribe/*',
            )
            || $response->getStatusCode() >= 400
        ) {
            return;
        }

        $email = trim((string) $request->input('email', ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $activeSubscriptionExists = DB::table('newsletter_subscribers')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                ->where('status', 'active')
                ->exists();

            if (! $activeSubscriptionExists) {
                return;
            }

            if ($request->hasSession()) {
                $request->session()->put('newsletter_subscribed', true);
            }

            $cookie = Cookie::make(
                'bandara_newsletter_subscribed',
                '1',
                60 * 24 * 365,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            );

            if ($response instanceof RedirectResponse || method_exists($response, 'withCookie')) {
                $response->withCookie($cookie);
            } else {
                $response->headers->setCookie($cookie);
            }
        } catch (Throwable) {
            // Newsletter submission itself has already completed. A secondary UI-state
            // failure must never turn a successful subscription into an HTTP error.
        }
    }

    private function forgetSuccessfulNewsletterUnsubscription(Request $request, Response $response): void
    {
        if (
            ! $request->is(
                'newsletter/unsubscribe',
                'newsletter/unsubscribe/*',
                '*/newsletter/unsubscribe',
                '*/newsletter/unsubscribe/*',
            )
            || $response->getStatusCode() >= 400
        ) {
            return;
        }

        try {
            $request->attributes->set('bandara_newsletter_unsubscribed', true);

            if ($request->hasSession()) {
                $request->session()->forget('newsletter_subscribed');
            }

            $cookie = Cookie::forget('bandara_newsletter_subscribed', '/');

            if ($response instanceof RedirectResponse || method_exists($response, 'withCookie')) {
                $response->withCookie($cookie);
            } else {
                $response->headers->setCookie($cookie);
            }
        } catch (Throwable) {
            // The application's unsubscribe flow remains authoritative. Clearing a
            // secondary convenience cookie must never turn it into an HTTP error.
        }
    }

    private function shouldShowRewardBanner(Request $request): bool
    {
        if (
            $request->is('login')
            || $request->is('admin*')
            || $request->is('staff*')
            || $request->is('account*')
            || $request->is('orders*')
            || $request->is('checkout*')
            || $request->is('business-account*')
            || $request->is('business*')
        ) {
            return false;
        }

        try {
            return Auth::guard('web')->guest();
        } catch (Throwable) {
            return false;
        }
    }

    private function isAboutPage(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return preg_match('~(^|/)(about|about-us)(/|$)~i', $path) === 1;
    }

    private function isBusinessPage(Request $request): bool
    {
        $path = trim($request->path(), '/');

        return preg_match('~(^|/)(business-account|business/register|business/application)(/|$)~i', $path) === 1;
    }

    private function injectRewardBanner(string $html, string $fragment): string
    {
        if (str_contains($html, 'data-bandara-reward-banner')) {
            return $html;
        }

        // The accepted storefront uses a fixed header/trust strip followed by a
        // visible .page-body container. Put the reward at the top of that content
        // host so it is visible on desktop and cannot inherit a mobile-only rule.
        $hostPatterns = [
            '~<(?:div|main)\b[^>]*\bclass=(["\'])[^"\']*\bpage-body\b[^"\']*\1[^>]*>~i',
            '~<main\b[^>]*>~i',
            '~<body\b[^>]*>~i',
        ];

        foreach ($hostPatterns as $pattern) {
            $count = 0;
            $updated = preg_replace_callback(
                $pattern,
                static fn (array $match): string => $match[0].$fragment,
                $html,
                1,
                $count,
            );

            if ($count === 1 && is_string($updated)) {
                return $updated;
            }
        }

        return $html;
    }

    private function injectBusinessAccessPanel(string $html, string $fragment): string
    {
        if (str_contains($html, 'data-bandara-business-access')) {
            return $html;
        }

        if (preg_match('~<main\b[^>]*>~i', $html) === 1) {
            return preg_replace('~(<main\b[^>]*>)~i', '$1'.$fragment, $html, 1) ?? $html;
        }

        return preg_replace('~(<body\b[^>]*>)~i', '$1'.$fragment, $html, 1) ?? $html;
    }

    private function injectBeforeMainClose(string $html, string $fragment): string
    {
        if (str_contains($html, 'data-bandara-founder-section')) {
            return $html;
        }

        if (preg_match('~</main>~i', $html) === 1) {
            return preg_replace('~</main>~i', $fragment.'</main>', $html, 1) ?? $html;
        }

        return preg_replace('~</body>~i', $fragment.'</body>', $html, 1) ?? $html;
    }

    private function rewardBanner(): string
    {
        return <<<'HTML'
<div class="bandara-launch-reward" data-bandara-reward-banner data-bandara-reward-version="1.3" role="region" aria-label="Bandara welcome credit">
    <div class="bandara-launch-reward__inner">
        <p><strong>New to Bandara?</strong> Register and complete your first eligible order to receive <strong>100 Bandara Credit (₹100)</strong>.</p>
        <a href="/register">Join and get ₹100</a>
        <span>B2C customers only. Terms apply.</span>
        <button type="button" data-bandara-reward-dismiss aria-label="Dismiss welcome credit message">×</button>
    </div>
</div>
HTML;
    }

    private function businessAccessPanel(): string
    {
        return <<<'HTML'
<section class="bandara-business-access" data-bandara-business-access data-bandara-reveal aria-labelledby="bandara-business-access-title">
    <h2 id="bandara-business-access-title">Already shop with Bandara?</h2>
    <p>Use your existing customer login to request business access. After review and approval, eligible wholesale pricing and business ordering features are added to the same account.</p>
    <p class="bandara-business-access__reassurance"><strong>Same login.</strong> No new account. Your saved addresses and order history stay with you.</p>
</section>
HTML;
    }

    private function founderSection(): string
    {
        return <<<'HTML'
<section class="bandara-founder-section" data-bandara-founder-section data-bandara-reveal aria-labelledby="bandara-founders-title">
    <div class="bandara-founder-section__inner">
        <p class="bandara-founder-section__eyebrow">Our founders</p>
        <h2 id="bandara-founders-title">The people behind Bandara</h2>
        <p class="bandara-founder-section__intro">Bandara was created around a simple belief: carefully sourced food should reach homes, chefs and businesses with its quality protected at every stage. Our founders bring together brand development, customer relationships, food-industry management, sourcing and operational oversight.</p>
        <div class="bandara-founder-grid">
            <article class="bandara-founder-card">
                <div class="bandara-founder-card__initials" aria-hidden="true">PP</div>
                <div>
                    <h3>Parag Parulekar</h3>
                    <p class="bandara-founder-card__role">Marketing &amp; Business Development</p>
                    <p>Parag leads Bandara’s brand, market development and customer relationships. His focus is on making premium products easier to discover and building dependable relationships with households, chefs, restaurants and business customers.</p>
                </div>
            </article>
            <article class="bandara-founder-card">
                <div class="bandara-founder-card__initials" aria-hidden="true">MM</div>
                <div>
                    <h3>Maytira Mala</h3>
                    <p class="bandara-founder-card__role">Executive Director</p>
                    <p>Maytira guides Bandara’s strategic direction, sourcing and quality oversight. Her focus on supply-chain management and product standards helps protect quality from supplier selection through storage and delivery.</p>
                </div>
            </article>
        </div>
    </div>
</section>
HTML;
    }
}
