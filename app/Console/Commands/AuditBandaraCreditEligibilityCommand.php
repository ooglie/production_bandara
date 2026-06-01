<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditBandaraCreditEligibilityCommand extends Command
{
    protected $signature = 'bandara-credit:audit-eligibility
                            {--json : Output machine-readable JSON}
                            {--fail-on-issues : Return non-zero exit code when ineligible reward data exists}';

    protected $description = 'Audit Bandara Credit data for non-B2C eligibility leakage';

    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->error('users table is missing.');

            return self::FAILURE;
        }

        $b2cValue = (string) config('bandara_credit.eligibility.b2c_value', 'b2c');

        $ineligibleUserIds = DB::table('users')
            ->where(fn ($query) => $query->whereNull('customer_type')->orWhere('customer_type', '!=', $b2cValue))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $summary = [
            'ineligible_users' => count($ineligibleUserIds),
            'ineligible_wallets' => 0,
            'ineligible_wallet_balance' => 0,
            'ineligible_transactions' => 0,
            'ineligible_posted_amount' => 0,
            'ineligible_reserved_amount' => 0,
            'sample_users' => [],
        ];

        if (! empty($ineligibleUserIds) && Schema::hasTable('bandara_credit_wallets')) {
            $summary['ineligible_wallets'] = (int) DB::table('bandara_credit_wallets')
                ->whereIn('user_id', $ineligibleUserIds)
                ->count();

            $summary['ineligible_wallet_balance'] = (int) DB::table('bandara_credit_wallets')
                ->whereIn('user_id', $ineligibleUserIds)
                ->sum('balance');
        }

        if (! empty($ineligibleUserIds) && Schema::hasTable('bandara_credit_transactions')) {
            $summary['ineligible_transactions'] = (int) DB::table('bandara_credit_transactions')
                ->whereIn('user_id', $ineligibleUserIds)
                ->count();

            $summary['ineligible_posted_amount'] = (int) DB::table('bandara_credit_transactions')
                ->whereIn('user_id', $ineligibleUserIds)
                ->where('status', 'posted')
                ->sum('amount');

            $summary['ineligible_reserved_amount'] = (int) DB::table('bandara_credit_transactions')
                ->whereIn('user_id', $ineligibleUserIds)
                ->where('status', 'reserved')
                ->sum('amount');
        }

        if (! empty($ineligibleUserIds)) {
            $sampleQuery = DB::table('users')
                ->whereIn('users.id', $ineligibleUserIds)
                ->limit(20);

            if (Schema::hasTable('bandara_credit_wallets')) {
                $sampleQuery
                    ->leftJoin('bandara_credit_wallets as wallets', 'wallets.user_id', '=', 'users.id')
                    ->select(['users.id', 'users.name', 'users.email', 'users.customer_type', 'wallets.balance'])
                    ->orderByDesc(DB::raw('COALESCE(wallets.balance, 0)'));
            } else {
                $sampleQuery
                    ->select(['users.id', 'users.name', 'users.email', 'users.customer_type'])
                    ->orderBy('users.id');
            }

            $summary['sample_users'] = $sampleQuery
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->name ?? ''),
                    'email' => (string) ($row->email ?? ''),
                    'customer_type' => (string) ($row->customer_type ?? ''),
                    'wallet_balance' => (int) ($row->balance ?? 0),
                ])
                ->all();
        }

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            $this->info('Bandara Credit B2C eligibility audit');
            $this->table(
                ['Metric', 'Value'],
                collect($summary)
                    ->except('sample_users')
                    ->map(fn ($value, $key) => [str($key)->headline()->toString(), is_numeric($value) ? number_format((float) $value) : $value])
                    ->values()
                    ->all()
            );

            if (! empty($summary['sample_users'])) {
                $this->warn('Sample non-B2C users with possible reward data:');
                $this->table(['ID', 'Name', 'Email', 'Type', 'Wallet'], collect($summary['sample_users'])->map(fn ($row) => [
                    $row['id'], $row['name'], $row['email'], $row['customer_type'], $row['wallet_balance'],
                ])->all());
            }
        }

        $hasIssues = $summary['ineligible_wallets'] > 0 || $summary['ineligible_transactions'] > 0;

        return $hasIssues && $this->option('fail-on-issues') ? self::FAILURE : self::SUCCESS;
    }
}
