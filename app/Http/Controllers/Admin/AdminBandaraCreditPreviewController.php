<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BandaraCreditWallet;
use App\Models\User;
use App\Services\BandaraCreditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminBandaraCreditPreviewController extends Controller
{
    public function index(Request $request, BandaraCreditService $bandaraCreditService): View
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer'],
        ]);

        /** @var class-string<Model> $orderModelClass */
        $orderModelClass = config('bandara_credit.order_model');

        $user = null;
        $order = null;
        $warning = null;

        if (! empty($data['user_id'])) {
            $user = User::query()->find($data['user_id']);
        }

        if (! empty($data['order_id'])) {
            $order = $orderModelClass::query()->find($data['order_id']);
        }

        if ($order) {
            $orderUserId = (int) data_get($order, config('bandara_credit.order_mapping.user_id'));

            if (! $user) {
                $user = User::query()->find($orderUserId);
            } elseif ($user->id !== $orderUserId) {
                $warning = "Selected order belongs to user #{$orderUserId}. Tier preview uses the selected user, while earn preview uses the selected order.";
            }
        }

        $walletSnapshot = null;
        $tierPreview = null;
        $orderPreview = null;
        $orderSnapshot = null;

        if ($user) {
            $wallet = BandaraCreditWallet::query()
                ->where('user_id', $user->id)
                ->first();

            $walletSnapshot = [
                'exists' => (bool) $wallet,
                'balance' => (int) ($wallet?->balance ?? 0),
                'tier' => (string) ($wallet?->tier ?? 'silver'),
            ];

            $tierPreview = $bandaraCreditService->previewTierForUser($user);
        }

        if ($order) {
            $orderPreview = $bandaraCreditService->previewEarnForOrder($order);

            $orderSnapshot = [
                'id' => (int) $order->getKey(),
                'user_id' => (int) data_get($order, config('bandara_credit.order_mapping.user_id')),
                'status' => (string) data_get($order, config('bandara_credit.order_mapping.status')),
                'placed_at' => data_get($order, config('bandara_credit.order_mapping.placed_at')),
            ];
        }

        $flags = [
            'enabled' => (bool) config('bandara_credit.enabled'),
            'shadow_mode' => (bool) config('bandara_credit.shadow_mode'),
            'earn_enabled' => (bool) config('bandara_credit.earn_enabled'),
            'redeem_enabled' => (bool) config('bandara_credit.redeem_enabled'),
            'auto_post_enabled' => (bool) config('bandara_credit.auto_post_enabled'),
            'repeat_bonus_enabled' => (bool) config('bandara_credit.repeat_bonus_enabled'),
            'welcome_bonus_enabled' => (bool) config('bandara_credit.welcome_bonus_enabled'),
            'birthday_bonus_enabled' => (bool) config('bandara_credit.birthday_bonus_enabled'),
            'tiers_enabled' => (bool) config('bandara_credit.tiers_enabled'),
        ];

        return view('admin.rewards.index', [
            'user' => $user,
            'order' => $order,
            'warning' => $warning,
            'walletSnapshot' => $walletSnapshot,
            'tierPreview' => $tierPreview,
            'orderPreview' => $orderPreview,
            'orderSnapshot' => $orderSnapshot,
            'flags' => $flags,
            'dashboardStats' => $this->dashboardStats(),
        ]);
    }

    private function dashboardStats(): array
    {
        $base = [
            'ready' => false,
            'highest_tier' => null,
            'highest_tier_count' => 0,
            'top_tier_customers' => collect(),
            'total_provided' => 0,
            'total_redeemed' => 0,
            'total_reserved_redemptions' => 0,
            'total_pending' => 0,
            'total_reversed' => 0,
            'active_wallet_balance' => 0,
            'wallet_count' => 0,
            'transaction_count' => 0,
            'tier_distribution' => collect(),
        ];

        if (! Schema::hasTable('bandara_credit_wallets') || ! Schema::hasTable('bandara_credit_transactions')) {
            return $base;
        }

        $tierOrder = array_keys((array) config('bandara_credit.tiers', []));

        if (empty($tierOrder)) {
            $tierOrder = ['silver', 'gold', 'platinum'];
        }

        $tierRank = array_flip($tierOrder);

        $walletRows = DB::table('bandara_credit_wallets')
            ->select([
                'tier',
                DB::raw('COUNT(*) as customers_count'),
                DB::raw('COALESCE(SUM(balance), 0) as balance_sum'),
            ])
            ->groupBy('tier')
            ->get();

        $highestTier = $walletRows
            ->pluck('tier')
            ->filter()
            ->sortByDesc(fn ($tier) => $tierRank[(string) $tier] ?? -1)
            ->first();

        $highestTierCount = $highestTier
            ? (int) ($walletRows->firstWhere('tier', $highestTier)?->customers_count ?? 0)
            : 0;

        $topTierCustomers = collect();

        if ($highestTier) {
            $topTierCustomers = DB::table('bandara_credit_wallets as wallets')
                ->leftJoin('users', 'users.id', '=', 'wallets.user_id')
                ->where('wallets.tier', $highestTier)
                ->select([
                    'wallets.user_id',
                    'wallets.tier',
                    'wallets.balance',
                    'wallets.updated_at',
                    'users.name',
                    'users.email',
                ])
                ->orderByDesc('wallets.balance')
                ->orderBy('users.name')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'user_id' => (int) $row->user_id,
                    'name' => $row->name ?: 'User #'.(int) $row->user_id,
                    'email' => $row->email,
                    'tier' => (string) $row->tier,
                    'balance' => (int) $row->balance,
                    'updated_at' => $row->updated_at,
                ]);
        }

        $totalProvided = (int) DB::table('bandara_credit_transactions')
            ->where('status', 'posted')
            ->where('amount', '>', 0)
            ->whereNotIn('type', ['redeem_reversal', 'redeem_release'])
            ->sum('amount');

        $totalPending = (int) DB::table('bandara_credit_transactions')
            ->whereIn('status', ['pending', 'reserved'])
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalRedeemed = abs((int) DB::table('bandara_credit_transactions')
            ->where('status', 'posted')
            ->where('amount', '<', 0)
            ->whereIn('type', ['redeem', 'redeemed', 'redemption', 'debit', 'use', 'admin_debit'])
            ->sum('amount'));

        $totalReservedRedemptions = abs((int) DB::table('bandara_credit_transactions')
            ->where('status', 'reserved')
            ->where('amount', '<', 0)
            ->where('type', 'redeem')
            ->sum('amount'));

        $totalReversed = abs((int) DB::table('bandara_credit_transactions')
            ->where('status', 'posted')
            ->where('amount', '<', 0)
            ->whereIn('type', ['earn_reversal', 'reversal'])
            ->sum('amount'));

        $activeWalletBalance = (int) DB::table('bandara_credit_wallets')->sum('balance');
        $walletCount = (int) DB::table('bandara_credit_wallets')->count();
        $transactionCount = (int) DB::table('bandara_credit_transactions')->count();

        $tierDistribution = collect($tierOrder)
            ->map(function (string $tier) use ($walletRows) {
                $row = $walletRows->firstWhere('tier', $tier);

                return [
                    'tier' => $tier,
                    'customers_count' => (int) ($row?->customers_count ?? 0),
                    'balance_sum' => (int) ($row?->balance_sum ?? 0),
                ];
            })
            ->values();

        return [
            'ready' => true,
            'highest_tier' => $highestTier,
            'highest_tier_count' => $highestTierCount,
            'top_tier_customers' => $topTierCustomers,
            'total_provided' => $totalProvided,
            'total_redeemed' => $totalRedeemed,
            'total_reserved_redemptions' => $totalReservedRedemptions,
            'total_pending' => $totalPending,
            'total_reversed' => $totalReversed,
            'active_wallet_balance' => $activeWalletBalance,
            'wallet_count' => $walletCount,
            'transaction_count' => $transactionCount,
            'tier_distribution' => $tierDistribution,
        ];
    }
}
