<?php

namespace App\Services;

use App\Models\B2BCustomerTerm;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class B2BPayLaterService
{
    public function termsFor(User $user): ?B2BCustomerTerm
    {
        if (! Schema::hasTable('b2b_customer_terms')) {
            return null;
        }

        return B2BCustomerTerm::query()
            ->where('user_id', $user->id)
            ->first();
    }

    public function saveTerms(User $user, array $data, ?User $actor = null): ?B2BCustomerTerm
    {
        if (! Schema::hasTable('b2b_customer_terms')) {
            return null;
        }

        $existing = $this->termsFor($user);

        $payload = [
            'pay_later_enabled' => (bool) ($data['pay_later_enabled'] ?? false),
            'credit_limit' => round((float) ($data['credit_limit'] ?? 0), 2),
            'payment_terms_days' => max(1, (int) ($data['payment_terms_days'] ?? 7)),
            'credit_status' => in_array(($data['credit_status'] ?? 'active'), ['active', 'on_hold', 'blocked'], true)
                ? (string) ($data['credit_status'] ?? 'active')
                : 'active',
            'notes' => $data['notes'] ?? null,
            'updated_by_id' => $actor?->id,
        ];

        if (! $existing) {
            $payload['user_id'] = $user->id;
            $payload['created_by_id'] = $actor?->id;
        }

        return B2BCustomerTerm::query()->updateOrCreate(
            ['user_id' => $user->id],
            $payload
        );
    }

    public function outstandingAmount(User $user): float
    {
        if (! Schema::hasTable('invoices')) {
            return 0.0;
        }

        $invoices = Invoice::query()
            ->with('payments')
            ->whereHas('order', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotIn('status', ['paid', 'cancelled', 'void'])
            ->get();

        return round((float) $invoices->sum(function (Invoice $invoice) {
            if (method_exists($invoice, 'getBalanceAmountAttribute')) {
                return (float) $invoice->balance_amount;
            }

            return (float) ($invoice->grand_total ?? 0);
        }), 2);
    }

    public function summaryFor(User $user): array
    {
        $terms = $this->termsFor($user);
        $outstanding = $this->outstandingAmount($user);
        $creditLimit = round((float) ($terms?->credit_limit ?? 0), 2);

        return [
            'terms' => $terms,
            'enabled' => (bool) ($terms?->pay_later_enabled ?? false),
            'credit_status' => (string) ($terms?->credit_status ?? 'on_hold'),
            'terms_days' => (int) ($terms?->payment_terms_days ?? 7),
            'credit_limit' => $creditLimit,
            'outstanding_amount' => $outstanding,
            'available_credit' => round(max(0, $creditLimit - $outstanding), 2),
        ];
    }

    public function checkoutOptionFor(User $user, float $orderAmount): array
    {
        if (($user->customer_type ?? 'b2c') !== 'b2b') {
            return [
                'is_b2b' => false,
                'eligible' => false,
                'enabled' => false,
                'reason' => null,
                'terms_days' => null,
                'credit_status' => null,
                'credit_limit' => 0.0,
                'outstanding_amount' => 0.0,
                'available_credit' => 0.0,
            ];
        }

        $terms = $this->termsFor($user);

        if (! $terms) {
            return $this->disabledOption('Pay Later terms have not been configured for this account.');
        }

        $outstanding = $this->outstandingAmount($user);
        $creditLimit = round((float) ($terms->credit_limit ?? 0), 2);
        $available = round(max(0, $creditLimit - $outstanding), 2);
        $termsDays = max(0, (int) ($terms->payment_terms_days ?? 0));

        $base = [
            'is_b2b' => true,
            'enabled' => (bool) ($terms->pay_later_enabled ?? false),
            'eligible' => false,
            'reason' => null,
            'terms_days' => $termsDays,
            'credit_status' => (string) ($terms->credit_status ?? 'on_hold'),
            'credit_limit' => $creditLimit,
            'outstanding_amount' => $outstanding,
            'available_credit' => $available,
        ];

        if (! (bool) ($terms->pay_later_enabled ?? false)) {
            return array_merge($base, ['reason' => 'Pay Later is not enabled for this account.']);
        }

        if ((string) ($terms->credit_status ?? 'on_hold') !== 'active') {
            return array_merge($base, ['reason' => 'This account is currently not active for Pay Later.']);
        }

        if ($termsDays <= 0) {
            return array_merge($base, ['reason' => 'Payment terms days are not configured.']);
        }

        if ($creditLimit <= 0) {
            return array_merge($base, ['reason' => 'Credit limit is not configured.']);
        }

        if ($available + 0.00001 < round($orderAmount, 2)) {
            return array_merge($base, ['reason' => 'This order exceeds available Pay Later credit.']);
        }

        return array_merge($base, [
            'eligible' => true,
            'reason' => null,
        ]);
    }

    public function canUsePayLater(User $user, float $orderAmount): bool
    {
        return (bool) ($this->checkoutOptionFor($user, $orderAmount)['eligible'] ?? false);
    }

    public function dueDateFor(User $user)
    {
        $terms = $this->termsFor($user);
        $days = max(1, (int) ($terms?->payment_terms_days ?? 7));

        return now()->addDays($days);
    }

    protected function disabledOption(string $reason): array
    {
        return [
            'is_b2b' => true,
            'eligible' => false,
            'enabled' => false,
            'reason' => $reason,
            'terms_days' => null,
            'credit_status' => null,
            'credit_limit' => 0.0,
            'outstanding_amount' => 0.0,
            'available_credit' => 0.0,
        ];
    }
}
