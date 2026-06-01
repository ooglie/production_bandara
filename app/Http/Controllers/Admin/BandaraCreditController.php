<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BandaraCreditCampaign;
use App\Models\BandaraCreditTier;
use App\Models\BandaraCreditTransaction;
use App\Models\BandaraCreditWallet;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\BandaraCreditAccountNotification;
use App\Services\BandaraCreditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BandaraCreditController extends Controller
{
    public function index(Request $request, BandaraCreditService $service): View
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'order_id' => ['nullable', 'integer'],
        ]);

        $user = ! empty($data['user_id']) ? User::query()->find($data['user_id']) : null;
        $order = null;
        $warning = null;
        $orderModelClass = config('bandara_credit.order_model');

        if (! empty($data['order_id'])) {
            $order = $orderModelClass::query()->find($data['order_id']);
        }

        if ($order) {
            $orderUserId = (int) data_get($order, config('bandara_credit.order_mapping.user_id'));

            if (! $user) {
                $user = User::query()->find($orderUserId);
            } elseif ((int) $user->id !== $orderUserId) {
                $warning = "Selected order belongs to user #{$orderUserId}. Tier preview uses the selected user, while earn preview uses the selected order.";
            }
        }

        $walletSnapshot = null;
        $tierPreview = null;
        $orderPreview = null;
        $orderSnapshot = null;

        if ($user) {
            $wallet = BandaraCreditWallet::query()->where('user_id', $user->id)->first();
            $walletSnapshot = [
                'exists' => (bool) $wallet,
                'balance' => (int) ($wallet?->balance ?? 0),
                'tier' => (string) ($wallet?->tier ?? 'silver'),
                'eligible' => $service->isEligibleUserForBandaraCredit($user),
            ];
            $tierPreview = $service->previewTierForUser($user);
        }

        if ($order) {
            $orderPreview = $service->previewEarnForOrder($order);
            $orderSnapshot = [
                'id' => (int) $order->getKey(),
                'user_id' => (int) data_get($order, config('bandara_credit.order_mapping.user_id')),
                'status' => (string) data_get($order, config('bandara_credit.order_mapping.status')),
                'placed_at' => data_get($order, config('bandara_credit.order_mapping.placed_at')),
            ];
        }

        return view('admin.rewards.index', [
            'user' => $user,
            'order' => $order,
            'warning' => $warning,
            'walletSnapshot' => $walletSnapshot,
            'tierPreview' => $tierPreview,
            'orderPreview' => $orderPreview,
            'orderSnapshot' => $orderSnapshot,
            'flags' => $this->flags(),
            'dashboardStats' => $this->dashboardStats(),
            'tiers' => $service->tierDefinitions(),
            'activeCampaigns' => $this->activeCampaigns(),
        ]);
    }

    public function tiers(BandaraCreditService $service): View
    {
        $this->ensureDefaultTiers();

        return view('admin.rewards.tiers', [
            'tiers' => BandaraCreditTier::query()->orderBy('sort_order')->orderBy('threshold_min')->get(),
            'definitions' => $service->tierDefinitions(),
        ]);
    }

    public function updateTiers(Request $request, BandaraCreditService $service): RedirectResponse
    {
        $data = $request->validate([
            'tiers' => ['required', 'array'],
            'tiers.*.id' => ['nullable', 'integer'],
            'tiers.*.key' => ['required', 'string', 'max:40'],
            'tiers.*.name' => ['required', 'string', 'max:80'],
            'tiers.*.threshold_min' => ['required', 'integer', 'min:0'],
            'tiers.*.threshold_max' => ['nullable', 'integer', 'min:0'],
            'tiers.*.reward_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tiers.*.sort_order' => ['required', 'integer', 'min:1'],
            'tiers.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($data['tiers'] as $row) {
            BandaraCreditTier::query()->updateOrCreate(
                ['key' => Str::slug((string) $row['key'], '_')],
                [
                    'name' => $row['name'],
                    'threshold_min' => (int) $row['threshold_min'],
                    'threshold_max' => $row['threshold_max'] === null || $row['threshold_max'] === '' ? null : (int) $row['threshold_max'],
                    'reward_rate_percent' => (float) $row['reward_rate_percent'],
                    'sort_order' => (int) $row['sort_order'],
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ]
            );
        }

        return redirect()->route('admin.rewards.tiers')->with('status', 'Reward tiers updated. Run reconciliation to refresh stored wallet tiers.');
    }

    public function campaigns(Request $request): View
    {
        $campaigns = BandaraCreditCampaign::query()
            ->withCount(['products', 'categories'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->orderByRaw("FIELD(status, 'active', 'draft', 'paused', 'expired')")
            ->orderByDesc('starts_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.rewards.campaigns.index', compact('campaigns'));
    }

    public function createCampaign(): View
    {
        return view('admin.rewards.campaigns.form', $this->campaignFormData(new BandaraCreditCampaign()));
    }

    public function storeCampaign(Request $request): RedirectResponse
    {
        $campaign = new BandaraCreditCampaign();
        $this->fillCampaign($campaign, $request);

        return redirect()->route('admin.rewards.campaigns.index')->with('status', 'Reward campaign created.');
    }

    public function editCampaign(BandaraCreditCampaign $campaign): View
    {
        return view('admin.rewards.campaigns.form', $this->campaignFormData($campaign));
    }

    public function updateCampaign(Request $request, BandaraCreditCampaign $campaign): RedirectResponse
    {
        $this->fillCampaign($campaign, $request);

        return redirect()->route('admin.rewards.campaigns.index')->with('status', 'Reward campaign updated.');
    }

    public function destroyCampaign(BandaraCreditCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return back()->with('status', 'Reward campaign deleted.');
    }

    public function ledger(Request $request): View
    {
        $transactions = BandaraCreditTransaction::query()
            ->with(['user', 'campaign'])
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->input('user_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', (string) $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.rewards.ledger', compact('transactions'));
    }

    public function reports(Request $request, BandaraCreditService $service): View
    {
        [$from, $to] = $this->reportDateRange($request);

        return view('admin.rewards.reports', [
            'from' => $from,
            'to' => $to,
            'summary' => $this->reportSummary($from, $to),
            'monthlyRows' => $this->monthlyReportRows($from, $to),
            'campaignRows' => $this->campaignReportRows($from, $to),
            'tierRows' => $this->tierReportRows(),
            'eligibilityAudit' => $service->b2cEligibilityAudit(),
        ]);
    }

    public function exportReport(Request $request, BandaraCreditService $service): StreamedResponse
    {
        [$from, $to] = $this->reportDateRange($request);
        $summary = $this->reportSummary($from, $to);
        $monthlyRows = $this->monthlyReportRows($from, $to);
        $campaignRows = $this->campaignReportRows($from, $to);
        $tierRows = $this->tierReportRows();
        $eligibilityAudit = $service->b2cEligibilityAudit();
        $filename = 'bandara-credit-report-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($summary, $monthlyRows, $campaignRows, $tierRows, $eligibilityAudit, $from, $to) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Bandara Credit report', $from->toDateString(), $to->toDateString()]);
            fputcsv($out, []);
            fputcsv($out, ['Summary metric', 'Value']);
            foreach ($summary as $key => $value) {
                fputcsv($out, [Str::headline((string) $key), $value]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Monthly movement']);
            fputcsv($out, ['Month', 'Issued', 'Redeemed', 'Reserved', 'Reversed', 'Promo bonus', 'Tier points']);
            foreach ($monthlyRows as $row) {
                fputcsv($out, [$row->period, $row->issued, $row->redeemed, $row->reserved, $row->reversed, $row->promo_bonus, $row->tier_points]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Campaign performance']);
            fputcsv($out, ['Campaign', 'Status', 'Bonus issued', 'Tier points', 'Transactions', 'Customers', 'Orders', 'Budget used', 'Budget']);
            foreach ($campaignRows as $row) {
                fputcsv($out, [
                    $row->name ?? 'Unassigned campaign',
                    $row->status ?? '—',
                    $row->bonus_issued,
                    $row->tier_points,
                    $row->transactions_count,
                    $row->customers_count,
                    $row->orders_count,
                    $row->used_budget_points ?? 0,
                    $row->budget_points ?? '',
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, ['Tier liability']);
            fputcsv($out, ['Tier', 'Customers', 'Outstanding wallet balance']);
            foreach ($tierRows as $row) {
                fputcsv($out, [$row->tier, $row->customers_count, $row->balance_sum]);
            }

            fputcsv($out, []);
            fputcsv($out, ['B2C eligibility audit']);
            foreach ($eligibilityAudit as $key => $value) {
                if ($key === 'sample_users') {
                    continue;
                }
                fputcsv($out, [Str::headline((string) $key), is_scalar($value) ? $value : json_encode($value)]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function storeOrderAdjustment(Request $request, BandaraCreditService $service): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer'],
            'adjustment_type' => ['required', 'in:earn_reversal,redeem_restore,manual_credit,manual_debit'],
            'points' => ['required', 'integer', 'min:1'],
            'tier_points' => ['nullable', 'integer', 'min:0'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $orderModelClass = config('bandara_credit.order_model');
        $order = $orderModelClass::query()->find((int) $data['order_id']);

        if (! $order) {
            return back()->withErrors(['order_id' => 'Order not found.'])->withInput();
        }

        $result = $service->postOrderRewardAdjustment(
            order: $order,
            adjustmentType: (string) $data['adjustment_type'],
            points: (int) $data['points'],
            tierPoints: (int) ($data['tier_points'] ?? 0),
            note: (string) $data['note'],
            createdById: $request->user()?->id,
            source: 'admin_partial_refund_or_correction'
        );

        if (($result['action'] ?? null) !== 'adjusted') {
            return back()->withErrors(['order_id' => 'Reward adjustment skipped: '.($result['reason'] ?? 'unknown reason')])->withInput();
        }

        return back()->with('status', 'Order reward adjustment posted and wallet reconciled.');
    }

    public function customers(BandaraCreditService $service): View
    {
        $customers = User::query()
            ->where('customer_type', 'b2c')
            ->leftJoin('bandara_credit_wallets as wallets', 'wallets.user_id', '=', 'users.id')
            ->select('users.*', 'wallets.balance as reward_balance', 'wallets.tier as reward_tier')
            ->orderByDesc(DB::raw('COALESCE(wallets.balance, 0)'))
            ->paginate(25);

        return view('admin.rewards.customers', compact('customers'));
    }

    public function storeAdjustment(Request $request, BandaraCreditService $service): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'amount' => ['required', 'integer', 'not_in:0'],
            'tier_points' => ['nullable', 'integer'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $user = User::query()->findOrFail((int) $data['user_id']);
        if (! $service->isEligibleUserForBandaraCredit($user)) {
            return back()->withErrors(['user_id' => 'Bandara Credit is only available for B2C customers.'])->withInput();
        }

        $amount = (int) $data['amount'];
        $type = $amount > 0 ? 'admin_credit' : 'admin_debit';
        $key = 'manual:'.Str::uuid()->toString();

        BandaraCreditTransaction::query()->create([
            'user_id' => $user->id,
            'order_id' => null,
            'amount' => $amount,
            'tier_points' => (int) ($data['tier_points'] ?? 0),
            'type' => $type,
            'status' => 'posted',
            'idempotency_key' => $key,
            'note' => $data['note'],
            'created_by_id' => $request->user()?->id,
            'meta' => ['source' => 'admin_adjustment'],
        ]);

        $service->syncWalletForUser($user->id);

        if (Schema::hasTable('notifications')) {
            try {
                $user->notify(new BandaraCreditAccountNotification(
                    $amount >= 0 ? 'Bandara Credit adjusted' : 'Bandara Credit deducted',
                    ($amount >= 0 ? '+' : '').number_format($amount).' Bandara Credit point'.(abs($amount) === 1 ? '' : 's').' manually adjusted on your account.',
                    ['event' => 'manual_adjustment', 'points_delta' => $amount]
                ));
            } catch (\Throwable) {
                // Ledger write has already succeeded; notification is best-effort.
            }
        }

        return back()->with('status', 'Manual reward adjustment posted.');
    }

    private function campaignFormData(BandaraCreditCampaign $campaign): array
    {
        return [
            'campaign' => $campaign,
            'tiers' => BandaraCreditTier::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::query()->orderBy('name')->limit(300)->get(['id', 'name', 'sku']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function fillCampaign(BandaraCreditCampaign $campaign, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:draft,active,paused,expired'],
            'type' => ['required', 'in:order,product,category,fixed_bonus'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'eligible_tiers' => ['nullable', 'array'],
            'eligible_tiers.*' => ['string', 'max:40'],
            'multiplier' => ['required', 'numeric', 'min:1', 'max:20'],
            'fixed_bonus_points' => ['nullable', 'integer', 'min:0'],
            'max_bonus_per_order' => ['nullable', 'integer', 'min:0'],
            'max_bonus_per_customer' => ['nullable', 'integer', 'min:0'],
            'budget_points' => ['nullable', 'integer', 'min:0'],
            'counts_toward_tier' => ['nullable', 'boolean'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        $campaign->fill([
            'name' => $data['name'],
            'slug' => $campaign->slug ?: Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'type' => $data['type'],
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'eligible_tiers' => array_values($data['eligible_tiers'] ?? []),
            'multiplier' => $data['multiplier'],
            'fixed_bonus_points' => $data['fixed_bonus_points'] ?? null,
            'max_bonus_per_order' => $data['max_bonus_per_order'] ?? null,
            'max_bonus_per_customer' => $data['max_bonus_per_customer'] ?? null,
            'budget_points' => $data['budget_points'] ?? null,
            'counts_toward_tier' => (bool) ($data['counts_toward_tier'] ?? false),
            'stacking_rule' => 'best_wins',
            'updated_by_id' => $request->user()?->id,
        ]);

        if (! $campaign->exists) {
            $campaign->created_by_id = $request->user()?->id;
        }

        $campaign->save();
        $campaign->products()->sync($data['product_ids'] ?? []);
        $campaign->categories()->sync($data['category_ids'] ?? []);
    }

    private function reportDateRange(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse((string) $request->input('from'))->startOfDay() : now()->startOfYear();
        $to = $request->filled('to') ? Carbon::parse((string) $request->input('to'))->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    private function reportSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        if (! Schema::hasTable('bandara_credit_transactions')) {
            return [];
        }

        $b2cUserIds = User::query()->where('customer_type', 'b2c')->pluck('id');
        $tx = DB::table('bandara_credit_transactions')->whereIn('user_id', $b2cUserIds);
        $this->applyReportRange($tx, $from, $to, 'created_at');

        return [
            'wallet_credits_issued' => (int) (clone $tx)->where('status', 'posted')->where('amount', '>', 0)->sum('amount'),
            'wallet_credits_redeemed' => abs((int) (clone $tx)->where('status', 'posted')->where('amount', '<', 0)->whereIn('type', ['redeem', 'redeemed', 'redemption', 'debit', 'use', 'admin_debit'])->sum('amount')),
            'earn_reversals' => abs((int) (clone $tx)->where('status', 'posted')->where('amount', '<', 0)->whereIn('type', ['earn_reversal', 'reversal'])->sum('amount')),
            'promo_bonus_issued' => (int) (clone $tx)->where('status', 'posted')->where('type', 'promo_bonus')->where('amount', '>', 0)->sum('amount'),
            'tier_points_issued' => (int) (clone $tx)->where('status', 'posted')->sum('tier_points'),
            'reserved_redemptions' => abs((int) (clone $tx)->where('status', 'reserved')->where('amount', '<', 0)->sum('amount')),
            'pending_earned_credits' => (int) (clone $tx)->where('status', 'pending')->where('amount', '>', 0)->sum('amount'),
            'outstanding_wallet_balance' => Schema::hasTable('bandara_credit_wallets') ? (int) DB::table('bandara_credit_wallets')->whereIn('user_id', $b2cUserIds)->sum('balance') : 0,
            'transactions' => (int) (clone $tx)->count(),
        ];
    }

    private function monthlyReportRows(?Carbon $from = null, ?Carbon $to = null)
    {
        if (! Schema::hasTable('bandara_credit_transactions')) {
            return collect();
        }

        $b2cUserIds = User::query()->where('customer_type', 'b2c')->pluck('id');
        $query = DB::table('bandara_credit_transactions')
            ->whereIn('user_id', $b2cUserIds)
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as period"),
                DB::raw("COALESCE(SUM(CASE WHEN status = 'posted' AND amount > 0 THEN amount ELSE 0 END), 0) as issued"),
                DB::raw("ABS(COALESCE(SUM(CASE WHEN status = 'posted' AND amount < 0 AND type IN ('redeem','redeemed','redemption','debit','use','admin_debit') THEN amount ELSE 0 END), 0)) as redeemed"),
                DB::raw("ABS(COALESCE(SUM(CASE WHEN status = 'reserved' AND amount < 0 THEN amount ELSE 0 END), 0)) as reserved"),
                DB::raw("ABS(COALESCE(SUM(CASE WHEN status = 'posted' AND amount < 0 AND type IN ('earn_reversal','reversal') THEN amount ELSE 0 END), 0)) as reversed"),
                DB::raw("COALESCE(SUM(CASE WHEN status = 'posted' AND type = 'promo_bonus' THEN amount ELSE 0 END), 0) as promo_bonus"),
                DB::raw("COALESCE(SUM(CASE WHEN status = 'posted' THEN tier_points ELSE 0 END), 0) as tier_points"),
            ])
            ->groupBy('period')
            ->orderBy('period');

        $this->applyReportRange($query, $from, $to, 'created_at');

        return $query->get();
    }

    private function campaignReportRows(?Carbon $from = null, ?Carbon $to = null)
    {
        if (! Schema::hasTable('bandara_credit_transactions') || ! Schema::hasTable('bandara_credit_campaigns')) {
            return collect();
        }

        $query = DB::table('bandara_credit_transactions as tx')
            ->leftJoin('bandara_credit_campaigns as c', 'c.id', '=', 'tx.campaign_id')
            ->whereNotNull('tx.campaign_id')
            ->where('tx.status', 'posted')
            ->select([
                'tx.campaign_id',
                'c.name',
                'c.status',
                'c.budget_points',
                'c.used_budget_points',
                DB::raw('COALESCE(SUM(CASE WHEN tx.amount > 0 THEN tx.amount ELSE 0 END), 0) as bonus_issued'),
                DB::raw('COALESCE(SUM(tx.tier_points), 0) as tier_points'),
                DB::raw('COUNT(tx.id) as transactions_count'),
                DB::raw('COUNT(DISTINCT tx.user_id) as customers_count'),
                DB::raw('COUNT(DISTINCT tx.order_id) as orders_count'),
            ])
            ->groupBy('tx.campaign_id', 'c.name', 'c.status', 'c.budget_points', 'c.used_budget_points')
            ->orderByDesc('bonus_issued');

        $this->applyReportRange($query, $from, $to, 'tx.created_at');

        return $query->get();
    }

    private function tierReportRows()
    {
        if (! Schema::hasTable('bandara_credit_wallets')) {
            return collect();
        }

        return DB::table('bandara_credit_wallets as wallets')
            ->leftJoin('users', 'users.id', '=', 'wallets.user_id')
            ->where('users.customer_type', 'b2c')
            ->select(['wallets.tier', DB::raw('COUNT(*) as customers_count'), DB::raw('COALESCE(SUM(wallets.balance), 0) as balance_sum')])
            ->groupBy('wallets.tier')
            ->orderBy('wallets.tier')
            ->get();
    }

    private function applyReportRange($query, ?Carbon $from, ?Carbon $to, string $column): void
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }

        if ($to) {
            $query->where($column, '<=', $to);
        }
    }

    private function flags(): array
    {
        return [
            'enabled' => (bool) config('bandara_credit.enabled'),
            'shadow_mode' => (bool) config('bandara_credit.shadow_mode'),
            'earn_enabled' => (bool) config('bandara_credit.earn_enabled'),
            'redeem_enabled' => (bool) config('bandara_credit.redeem_enabled'),
            'auto_post_enabled' => (bool) config('bandara_credit.auto_post_enabled'),
            'campaigns_enabled' => (bool) config('bandara_credit.campaigns.enabled', true),
            'b2c_only' => true,
        ];
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
            'total_pending' => 0,
            'total_reversed' => 0,
            'active_wallet_balance' => 0,
            'wallet_count' => 0,
            'transaction_count' => 0,
            'tier_distribution' => collect(),
            'annual_tier_points' => 0,
            'promo_bonus' => 0,
        ];

        if (! Schema::hasTable('bandara_credit_wallets') || ! Schema::hasTable('bandara_credit_transactions')) {
            return $base;
        }

        $b2cUserIds = User::query()->where('customer_type', 'b2c')->pluck('id');
        $tierOrder = Schema::hasTable('bandara_credit_tiers')
            ? (BandaraCreditTier::query()->orderBy('sort_order')->pluck('key')->all() ?: ['silver', 'gold', 'platinum'])
            : ['silver', 'gold', 'platinum'];
        $tierRank = array_flip($tierOrder);

        $walletRows = DB::table('bandara_credit_wallets')
            ->whereIn('user_id', $b2cUserIds)
            ->select(['tier', DB::raw('COUNT(*) as customers_count'), DB::raw('COALESCE(SUM(balance), 0) as balance_sum')])
            ->groupBy('tier')
            ->get();

        $highestTier = $walletRows->pluck('tier')->filter()->sortByDesc(fn ($tier) => $tierRank[(string) $tier] ?? -1)->first();
        $highestTierCount = $highestTier ? (int) ($walletRows->firstWhere('tier', $highestTier)?->customers_count ?? 0) : 0;

        $topTierCustomers = collect();
        if ($highestTier) {
            $topTierCustomers = DB::table('bandara_credit_wallets as wallets')
                ->leftJoin('users', 'users.id', '=', 'wallets.user_id')
                ->where('wallets.tier', $highestTier)
                ->where('users.customer_type', 'b2c')
                ->select(['wallets.user_id', 'wallets.tier', 'wallets.balance', 'wallets.updated_at', 'users.name', 'users.email'])
                ->orderByDesc('wallets.balance')
                ->limit(5)
                ->get();
        }

        $tx = DB::table('bandara_credit_transactions')->whereIn('user_id', $b2cUserIds);

        return [
            'ready' => true,
            'highest_tier' => $highestTier,
            'highest_tier_count' => $highestTierCount,
            'top_tier_customers' => $topTierCustomers,
            'total_provided' => (int) (clone $tx)->where('status', 'posted')->where('amount', '>', 0)->sum('amount'),
            'total_redeemed' => abs((int) (clone $tx)->where('status', 'posted')->where('amount', '<', 0)->whereIn('type', ['redeem', 'redeemed', 'redemption', 'debit', 'use', 'admin_debit'])->sum('amount')),
            'total_pending' => (int) (clone $tx)->whereIn('status', ['pending', 'reserved'])->where('amount', '>', 0)->sum('amount'),
            'total_reversed' => abs((int) (clone $tx)->where('status', 'posted')->where('amount', '<', 0)->whereIn('type', ['earn_reversal', 'reversal'])->sum('amount')),
            'active_wallet_balance' => (int) DB::table('bandara_credit_wallets')->whereIn('user_id', $b2cUserIds)->sum('balance'),
            'wallet_count' => (int) DB::table('bandara_credit_wallets')->whereIn('user_id', $b2cUserIds)->count(),
            'transaction_count' => (int) DB::table('bandara_credit_transactions')->whereIn('user_id', $b2cUserIds)->count(),
            'tier_distribution' => collect($tierOrder)->map(fn ($tier) => [
                'tier' => $tier,
                'customers_count' => (int) ($walletRows->firstWhere('tier', $tier)?->customers_count ?? 0),
                'balance_sum' => (int) ($walletRows->firstWhere('tier', $tier)?->balance_sum ?? 0),
            ])->values(),
            'annual_tier_points' => (int) (clone $tx)->where('status', 'posted')->whereYear('created_at', now()->year)->sum('tier_points'),
            'promo_bonus' => (int) (clone $tx)->where('status', 'posted')->where('type', 'promo_bonus')->sum('amount'),
        ];
    }

    private function activeCampaigns()
    {
        if (! Schema::hasTable('bandara_credit_campaigns')) {
            return collect();
        }

        return BandaraCreditCampaign::query()
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('ends_at')
            ->limit(5)
            ->get();
    }

    private function ensureDefaultTiers(): void
    {
        if (BandaraCreditTier::query()->exists()) {
            return;
        }

        foreach ([
            ['key' => 'silver', 'name' => 'Silver', 'threshold_min' => 0, 'threshold_max' => 999, 'reward_rate_percent' => 1, 'sort_order' => 1],
            ['key' => 'gold', 'name' => 'Gold', 'threshold_min' => 1000, 'threshold_max' => 3499, 'reward_rate_percent' => 2, 'sort_order' => 2],
            ['key' => 'platinum', 'name' => 'Platinum', 'threshold_min' => 3500, 'threshold_max' => null, 'reward_rate_percent' => 4, 'sort_order' => 3],
        ] as $tier) {
            BandaraCreditTier::query()->create($tier + ['is_active' => true]);
        }
    }
}
