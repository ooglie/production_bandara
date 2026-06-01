<?php

/**
 * Bandara rewards runtime hotfix.
 *
 * Fixes two issues without overwriting full source files:
 * 1) Adds BandaraCreditService::tierDefinitions() if a controller/view now calls it.
 * 2) Normalizes checkout Bandara Credit variables so the view does not show
 *    redemption disabled when the service/config are enabled.
 *
 * Run from the Laravel project root:
 * php scripts/fix_bandara_rewards_checkout_and_tiers.php
 */

$root = getcwd();

function fail(string $message): void
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

function backup_file(string $path): void
{
    if (! is_file($path)) {
        return;
    }

    $backup = $path . '.bak_rewards_runtime_' . date('YmdHis');
    copy($path, $backup);
}

function write_if_changed(string $path, string $new, string $label): void
{
    $old = is_file($path) ? file_get_contents($path) : null;
    if ($old === $new) {
        echo "{$label}: already OK\n";
        return;
    }

    backup_file($path);
    file_put_contents($path, $new);
    echo "{$label}: updated\n";
}

function insert_before_class_closing_brace(string $php, string $insert): string
{
    $pos = strrpos($php, '}');
    if ($pos === false) {
        fail('Could not locate final class closing brace.');
    }

    return substr($php, 0, $pos) . rtrim($insert) . "\n" . substr($php, $pos);
}

$servicePath = $root . '/app/Services/BandaraCreditService.php';
$checkoutControllerPath = $root . '/app/Http/Controllers/Customer/CheckoutController.php';
$checkoutViewPath = $root . '/resources/views/customer/checkout/index.blade.php';

if (! is_file($servicePath)) {
    fail('Missing app/Services/BandaraCreditService.php. Run this from the Laravel project root.');
}

$service = file_get_contents($servicePath);

if (! preg_match('/function\s+tierDefinitions\s*\(/', $service)) {
    $tierDefinitionsMethod = <<<'PHP_METHOD'

    public function tierDefinitions()
    {
        $birthdayEnabled = (bool) config('bandara_credit.birthday_bonus_enabled', true);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('bandara_credit_tiers')) {
                $rows = \Illuminate\Support\Facades\DB::table('bandara_credit_tiers')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->orderBy('threshold_min')
                    ->get();

                if ($rows->isNotEmpty()) {
                    return $rows->map(function ($tier) use ($birthdayEnabled) {
                        $key = strtolower((string) ($tier->key ?? $tier->name ?? 'silver'));

                        return [
                            'key' => $key,
                            'name' => (string) ($tier->name ?? ucfirst($key)),
                            'threshold_min' => (int) ($tier->threshold_min ?? 0),
                            'threshold_max' => isset($tier->threshold_max) ? ($tier->threshold_max === null ? null : (int) $tier->threshold_max) : null,
                            'reward_rate_percent' => (float) ($tier->reward_rate_percent ?? config('bandara_credit.tiers.'.$key.'.rate_percent', 1)),
                            'birthday_credit' => $birthdayEnabled ? (int) config('bandara_credit.tiers.'.$key.'.birthday_credit', 0) : 0,
                            'sort_order' => (int) ($tier->sort_order ?? 0),
                        ];
                    })->values();
                }
            }
        } catch (\Throwable $e) {
            // Fall back to config/env defaults if the tier table is not migrated yet.
        }

        $tiers = [
            [
                'key' => 'silver',
                'name' => 'Silver',
                'threshold_min' => (int) config('bandara_credit.tiers.silver.threshold', 0),
                'threshold_max' => (int) config('bandara_credit.tiers.silver.max_threshold', 999),
                'reward_rate_percent' => (float) config('bandara_credit.tiers.silver.rate_percent', 1),
                'birthday_credit' => $birthdayEnabled ? (int) config('bandara_credit.tiers.silver.birthday_credit', 100) : 0,
                'sort_order' => 1,
            ],
            [
                'key' => 'gold',
                'name' => 'Gold',
                'threshold_min' => (int) config('bandara_credit.tiers.gold.threshold', 1000),
                'threshold_max' => (int) config('bandara_credit.tiers.gold.max_threshold', 3499),
                'reward_rate_percent' => (float) config('bandara_credit.tiers.gold.rate_percent', 2),
                'birthday_credit' => $birthdayEnabled ? (int) config('bandara_credit.tiers.gold.birthday_credit', 150) : 0,
                'sort_order' => 2,
            ],
            [
                'key' => 'platinum',
                'name' => 'Platinum',
                'threshold_min' => (int) config('bandara_credit.tiers.platinum.threshold', 3500),
                'threshold_max' => null,
                'reward_rate_percent' => (float) config('bandara_credit.tiers.platinum.rate_percent', 4),
                'birthday_credit' => $birthdayEnabled ? (int) config('bandara_credit.tiers.platinum.birthday_credit', 200) : 0,
                'sort_order' => 3,
            ],
        ];

        return collect($tiers)->sortBy('sort_order')->values();
    }
PHP_METHOD;

    $service = insert_before_class_closing_brace($service, $tierDefinitionsMethod);
    write_if_changed($servicePath, $service, 'BandaraCreditService::tierDefinitions');
} else {
    echo "BandaraCreditService::tierDefinitions: already present\n";
}

// Make older checkout controllers pass both names used by different view versions.
if (is_file($checkoutControllerPath)) {
    $controller = file_get_contents($checkoutControllerPath);
    $updated = $controller;

    if (strpos($updated, "'bandaraCreditQuote'") !== false && strpos($updated, "'bandaraCreditRedemption'") === false) {
        $updated = str_replace(
            "'bandaraCreditQuote'  => \$bandaraCreditQuote,",
            "'bandaraCreditQuote'  => \$bandaraCreditQuote,\n            'bandaraCreditRedemption' => \$bandaraCreditQuote,",
            $updated
        );
        $updated = str_replace(
            "'bandaraCreditQuote' => \$bandaraCreditQuote,",
            "'bandaraCreditQuote' => \$bandaraCreditQuote,\n            'bandaraCreditRedemption' => \$bandaraCreditQuote,",
            $updated
        );
    }

    if (strpos($updated, "'bandaraCreditRedemption'") !== false && strpos($updated, "'bandaraCreditQuote'") === false) {
        $updated = str_replace(
            "'bandaraCreditRedemption' => \$bandaraCreditRedemption,",
            "'bandaraCreditRedemption' => \$bandaraCreditRedemption,\n            'bandaraCreditQuote' => \$bandaraCreditRedemption,",
            $updated
        );
    }

    write_if_changed($checkoutControllerPath, $updated, 'CheckoutController Bandara Credit aliases');
}

if (! is_file($checkoutViewPath)) {
    echo "Checkout view: missing; skipped\n";
    exit(0);
}

$view = file_get_contents($checkoutViewPath);
$marker = 'BANDARA_CREDIT_RUNTIME_NORMALIZER_V1';

if (strpos($view, $marker) === false) {
    $normalizer = <<<'BLADE'

    // BANDARA_CREDIT_RUNTIME_NORMALIZER_V1
    // Normalize Bandara Credit state across older/newer checkout controllers.
    // This prevents a stale/empty view array from rendering redemption as disabled
    // when App\Services\BandaraCreditService says redemption is enabled.
    $bandaraCreditState = [];
    foreach ([$bandaraCredit ?? null, $bandaraCreditRedemption ?? null, $bandaraCreditQuote ?? null] as $candidate) {
        if (is_array($candidate) && ! empty($candidate)) {
            $bandaraCreditState = array_merge($bandaraCreditState, $candidate);
        }
    }

    try {
        $bandaraCreditUser = auth()->user();
        if ($bandaraCreditUser) {
            $bandaraCreditService = app(\App\Services\BandaraCreditService::class);
            $bandaraCreditStatus = method_exists($bandaraCreditService, 'redemptionStatusForUser')
                ? (array) $bandaraCreditService->redemptionStatusForUser($bandaraCreditUser)
                : [];

            $bandaraOrderAmount = (float) ($grandTotalBeforeBandaraCredit ?? $grandTotal ?? $cartTotal ?? $subtotal ?? 0);
            $bandaraRequestedPoints = old('bandara_credit_points', request('bandara_credit_points', $bandaraCreditState['requested_points'] ?? $bandaraCreditState['applied_points'] ?? null));
            $bandaraRequestedPoints = $bandaraRequestedPoints === null || $bandaraRequestedPoints === ''
                ? (int) ($bandaraCreditState['requested_points'] ?? $bandaraCreditState['applied_points'] ?? 0)
                : max(0, (int) $bandaraRequestedPoints);

            if (method_exists($bandaraCreditService, 'redemptionQuoteForCheckout')) {
                $bandaraFreshQuote = (array) $bandaraCreditService->redemptionQuoteForCheckout(
                    $bandaraCreditUser,
                    $bandaraOrderAmount,
                    $bandaraRequestedPoints,
                    ['source' => 'checkout_view_normalizer']
                );
            } elseif (method_exists($bandaraCreditService, 'previewRedemptionForAmount')) {
                $bandaraFreshQuote = (array) $bandaraCreditService->previewRedemptionForAmount(
                    $bandaraCreditUser,
                    $bandaraOrderAmount,
                    $bandaraRequestedPoints
                );
            } else {
                $bandaraFreshQuote = [];
            }

            $bandaraCreditState = array_merge($bandaraCreditState, $bandaraFreshQuote);

            if ($bandaraCreditStatus) {
                $bandaraCreditState['program_enabled'] = (bool) ($bandaraCreditStatus['program_enabled'] ?? $bandaraCreditState['program_enabled'] ?? $bandaraCreditState['enabled'] ?? false);
                $bandaraCreditState['shadow_mode'] = (bool) ($bandaraCreditStatus['shadow_mode'] ?? $bandaraCreditState['shadow_mode'] ?? false);
                $bandaraCreditState['redeem_enabled'] = (bool) ($bandaraCreditStatus['redeem_enabled'] ?? $bandaraCreditState['redeem_enabled'] ?? false);
                $bandaraCreditState['eligible_user'] = (bool) ($bandaraCreditStatus['eligible_user'] ?? $bandaraCreditState['eligible_user'] ?? false);
                $bandaraCreditState['enabled'] = (bool) ($bandaraCreditStatus['enabled'] ?? $bandaraCreditState['enabled'] ?? false);
                $bandaraCreditState['redemption_enabled'] = (bool) ($bandaraCreditStatus['enabled'] ?? $bandaraCreditState['redemption_enabled'] ?? $bandaraCreditState['enabled'] ?? false);

                if (! empty($bandaraCreditStatus['reason'])) {
                    $bandaraCreditState['reason'] = $bandaraCreditStatus['reason'];
                }

                if (! empty($bandaraCreditStatus['message']) && empty($bandaraCreditState['message'])) {
                    $bandaraCreditState['message'] = $bandaraCreditStatus['message'];
                }
            }
        }
    } catch (\Throwable $e) {
        $bandaraCreditState = is_array($bandaraCreditState) ? $bandaraCreditState : [];
    }

    $bandaraCredit = $bandaraCreditRedemption = $bandaraCreditQuote = $bandaraCreditState;
    $bandaraCreditProgramEnabled = (bool) ($bandaraCreditState['program_enabled'] ?? ($bandaraCreditState['enabled'] ?? false));
    $bandaraCreditEligibleUser = (bool) ($bandaraCreditState['eligible_user'] ?? false);
    $bandaraCreditEnabled = (bool) ($bandaraCreditState['redemption_enabled'] ?? ($bandaraCreditState['enabled'] ?? false));
    $bandaraCreditCanRedeem = (bool) ($bandaraCreditState['can_redeem'] ?? ($bandaraCreditEnabled && (int) ($bandaraCreditState['max_redeemable_points'] ?? $bandaraCreditState['max_points'] ?? 0) > 0));
    $bandaraCreditAvailable = (int) ($bandaraCreditState['available_points'] ?? $bandaraCreditState['availablePoints'] ?? 0);
    $bandaraCreditReserved = (int) ($bandaraCreditState['reserved_points'] ?? $bandaraCreditState['reservedPoints'] ?? 0);
    $bandaraCreditMinimum = (int) ($bandaraCreditState['minimum_points'] ?? $bandaraCreditState['minimumPoints'] ?? 0);
    $bandaraCreditMaxPoints = (int) ($bandaraCreditState['max_redeemable_points'] ?? $bandaraCreditState['max_points'] ?? 0);
    $bandaraCreditMaxAmount = (float) ($bandaraCreditState['max_redeem_amount'] ?? $bandaraCreditState['max_amount'] ?? 0);
    $bandaraCreditRequested = (int) ($bandaraCreditState['requested_points'] ?? $bandaraCreditState['applied_points'] ?? 0);
    $bandaraCreditAppliedPoints = (int) ($bandaraCreditState['points_to_redeem'] ?? $bandaraCreditState['applied_points'] ?? 0);
    $bandaraCreditAppliedAmount = (float) ($bandaraCreditState['redeem_amount'] ?? $bandaraCreditState['applied_amount'] ?? 0);
    $bandaraCreditAmount = (float) ($bandaraCreditAmount ?? $bandaraCreditAppliedAmount ?? 0);

    $bandaraCreditMessages = array_values(array_filter((array) ($bandaraCreditState['messages'] ?? [])));
    $bandaraCreditMessage = $bandaraCreditState['message'] ?? ($bandaraCreditMessages[0] ?? null);

    if ($bandaraCreditEnabled && is_string($bandaraCreditMessage) && str_contains(strtolower($bandaraCreditMessage), 'redemption is currently disabled')) {
        $bandaraCreditMessage = null;
    }
BLADE;

    $firstEndPhp = strpos($view, '@endphp');
    if ($firstEndPhp === false) {
        fail('Could not find initial @endphp in checkout view.');
    }

    $view = substr($view, 0, $firstEndPhp) . $normalizer . "\n" . substr($view, $firstEndPhp);
}

// Make the common hard-coded disabled fallback safer in case the view reaches it while redemption is enabled.
$view = str_replace(
    "{{ \$bandaraCreditMessage ?: 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.' }}",
    "{{ \$bandaraCreditMessage ?: (\$bandaraCreditEnabled ? 'You do not currently have enough eligible Bandara Credit for this order.' : 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.') }}",
    $view
);

$view = str_replace(
    "Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.\n                            </div>",
    "{{ \$bandaraCreditEnabled ? 'You do not currently have enough eligible Bandara Credit for this order.' : 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.' }}\n                            </div>",
    $view
);

write_if_changed($checkoutViewPath, $view, 'Checkout view Bandara Credit normalizer');

echo "\nDone. Now run:\n";
echo "php -l app/Services/BandaraCreditService.php\n";
echo "php -l app/Http/Controllers/Customer/CheckoutController.php\n";
echo "php artisan view:clear\n";
echo "php artisan config:clear\n";
echo "php artisan optimize:clear\n";
