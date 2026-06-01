<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\BandaraCreditService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function dashboard(Request $request)
    {
        return redirect()->route('dashboard.customer');
    }

    public function rewards(Request $request, BandaraCreditService $bandaraCreditService)
    {
        if (($request->user()?->customer_type ?? 'b2c') === 'b2b') {
            return redirect()
                ->route('dashboard.customer')
                ->with('status', 'Rewards are available for B2C accounts. Your B2B account pricing is used automatically in the storefront.');
        }

        $snapshot = $bandaraCreditService->snapshotForUser($request->user()->id);

        $perAmount = (int) config('bandara_credit.earning.per_amount_spent', 100);
        $creditAmount = (int) config('bandara_credit.earning.credit_amount', 1);
        $redeemEnabled = (bool) ($snapshot['redemptionEnabled'] ?? false);
        $minimumRedeem = (int) config('bandara_credit.redemption.minimum_points', 500);
        $maxPercentage = (float) config('bandara_credit.redemption.max_order_percentage', 20);

        return view('customer.account.rewards', [
            ...$snapshot,
            'earnRateLabel' => "Earn {$creditAmount} Bandara Credit for every ₹{$perAmount} of eligible order value.",
            'redeemRuleLabel' => $redeemEnabled
                ? "Redeem from {$minimumRedeem} credits on eligible checkout orders, up to {$maxPercentage}% of the payable total."
                : 'Redemption is not enabled yet. Your credits will continue accumulating safely.',
            'expiryLabel' => null,
        ]);
    }
}