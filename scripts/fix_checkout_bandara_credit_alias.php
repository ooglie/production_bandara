<?php

$root = getcwd();
$view = $root.'/resources/views/customer/checkout/index.blade.php';
$controller = $root.'/app/Http/Controllers/Customer/CheckoutController.php';

if (! is_file($view)) {
    fwrite(STDERR, "Missing checkout view: {$view}\n");
    exit(1);
}

$viewText = file_get_contents($view);
$originalView = $viewText;

// The checkout code has existed with two variable names across recent reward patches:
// $bandaraCreditRedemption and $bandaraCreditQuote. The view should accept both so
// it never falls back to an empty array and wrongly shows "0 available / disabled".
$viewText = preg_replace(
    '/\$bandaraCredit\s*=\s*\$bandaraCreditRedemption\s*\?\?\s*\[\]\s*;/',
    '$bandaraCredit = $bandaraCreditRedemption ?? $bandaraCreditQuote ?? [];',
    $viewText,
    1
);
$viewText = preg_replace(
    '/\$bandaraCredit\s*=\s*\$bandaraCreditQuote\s*\?\?\s*\[\]\s*;/',
    '$bandaraCredit = $bandaraCreditRedemption ?? $bandaraCreditQuote ?? [];',
    $viewText,
    1
);

// Add robust state variables if the view still only has the older single enabled flag.
if (str_contains($viewText, '$bandaraCreditEnabled = (bool) ($bandaraCredit[\'enabled\'] ?? false);')) {
    $viewText = str_replace(
        '$bandaraCreditEnabled = (bool) ($bandaraCredit[\'enabled\'] ?? false);',
        '$bandaraCreditProgramEnabled = (bool) ($bandaraCredit[\'program_enabled\'] ?? ($bandaraCredit[\'enabled\'] ?? false));' . "\n" .
        '    $bandaraCreditEligibleUser = (bool) ($bandaraCredit[\'eligible_user\'] ?? false);' . "\n" .
        '    $bandaraCreditEnabled = (bool) ($bandaraCredit[\'redemption_enabled\'] ?? ($bandaraCreditProgramEnabled && $bandaraCreditEligibleUser));' . "\n" .
        '    $bandaraCreditCanRedeem = (bool) ($bandaraCredit[\'can_redeem\'] ?? ($bandaraCreditEnabled && (int) ($bandaraCredit[\'max_redeemable_points\'] ?? 0) > 0));' . "\n" .
        '    $bandaraCreditMessages = array_values(array_filter((array) ($bandaraCredit[\'messages\'] ?? [])));',
        $viewText
    );
}

// If the variables already exist but message array is missing, add it after enabled line.
if (! str_contains($viewText, '$bandaraCreditMessages = array_values')) {
    $viewText = str_replace(
        '$bandaraCreditAvailable = (int) ($bandaraCredit[\'available_points\'] ?? 0);',
        '$bandaraCreditMessages = array_values(array_filter((array) ($bandaraCredit[\'messages\'] ?? [])));' . "\n" .
        '    $bandaraCreditAvailable = (int) ($bandaraCredit[\'available_points\'] ?? 0);',
        $viewText
    );
}

// Replace the old three-way block conditions/messages when present. These replacements are harmless
// if already patched because they simply won't match.
$viewText = str_replace(
    '@if($bandaraCreditEnabled && $bandaraCreditMaxPoints > 0)',
    '@if(($bandaraCreditCanRedeem ?? ($bandaraCreditEnabled && $bandaraCreditMaxPoints > 0)))',
    $viewText
);
// Prefer clear state-specific messages: disabled, ineligible, or not enough credits.
$oldBlock = <<<'BLADE'
                        @elseif($bandaraCreditEnabled)
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                You do not currently have enough eligible Bandara Credit for this order.
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.
                            </div>
                        @endif
BLADE;
$newBlock = <<<'BLADE'
                        @elseif(! ($bandaraCreditProgramEnabled ?? false))
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                {{ $bandaraCreditMessages[0] ?? 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.' }}
                            </div>
                        @elseif(! ($bandaraCreditEligibleUser ?? false))
                            <div class="rounded-xl border border-amber-200 dark:border-amber-900 bg-amber-50 dark:bg-amber-950/20 px-3 py-2 text-[11px] text-amber-800 dark:text-amber-300">
                                {{ $bandaraCreditMessages[0] ?? 'Bandara Credit redemption is available only for eligible B2C customer accounts.' }}
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                {{ $bandaraCreditMessages[0] ?? 'You do not currently have enough eligible Bandara Credit for this order.' }}
                            </div>
                        @endif
BLADE;
$viewText = str_replace($oldBlock, $newBlock, $viewText);

$oldPatchedBlock = <<<'BLADE'
                        @elseif(! ($bandaraCreditProgramEnabled ?? $bandaraCreditEnabled))
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                {{ $bandaraCreditMessages[0] ?? 'You do not currently have enough eligible Bandara Credit for this order.' }}
                            </div>
                        @else
                            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-950/40 px-3 py-2 text-[11px] text-gray-600 dark:text-gray-300">
                                {{ $bandaraCreditMessages[0] ?? 'Bandara Credit redemption is currently disabled. You can still earn credits on eligible orders.' }}
                            </div>
                        @endif
BLADE;
$viewText = str_replace($oldPatchedBlock, $newBlock, $viewText);

if ($viewText !== $originalView) {
    copy($view, $view.'.bak_bandara_credit_alias');
    file_put_contents($view, $viewText);
    echo "Updated checkout view and created backup: {$view}.bak_bandara_credit_alias\n";
} else {
    echo "Checkout view did not need alias changes.\n";
}

if (is_file($controller)) {
    $controllerText = file_get_contents($controller);
    $originalController = $controllerText;

    // Make controller provide both variable names when it provides one of them.
    if (str_contains($controllerText, "'bandaraCreditQuote'") && ! str_contains($controllerText, "'bandaraCreditRedemption'")) {
        $controllerText = preg_replace(
            "/('bandaraCreditQuote'\s*=>\s*\$bandaraCreditQuote\s*,)/",
            "$1\n            'bandaraCreditRedemption' => \$bandaraCreditQuote,",
            $controllerText,
            1
        );
    }

    if (str_contains($controllerText, "'bandaraCreditRedemption'") && ! str_contains($controllerText, "'bandaraCreditQuote'")) {
        $controllerText = preg_replace(
            "/('bandaraCreditRedemption'\s*=>\s*\$bandaraCreditRedemption\s*,)/",
            "$1\n            'bandaraCreditQuote'      => \$bandaraCreditRedemption,",
            $controllerText,
            1
        );
    }

    if ($controllerText !== $originalController) {
        copy($controller, $controller.'.bak_bandara_credit_alias');
        file_put_contents($controller, $controllerText);
        echo "Updated checkout controller and created backup: {$controller}.bak_bandara_credit_alias\n";
    } else {
        echo "Checkout controller did not need alias changes.\n";
    }
}

echo "Done. Run: php artisan view:clear && php artisan optimize:clear\n";
