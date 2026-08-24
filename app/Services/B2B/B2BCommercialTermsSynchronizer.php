<?php

declare(strict_types=1);

namespace App\Services\B2B;

use App\Models\B2BApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class B2BCommercialTermsSynchronizer
{
    public function sync(User $user, B2BApplication $application): void
    {
        $this->syncUserColumns($user, $application);
        $this->syncExistingTermsRow($user, $application);
    }

    private function syncUserColumns(User $user, B2BApplication $application): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $map = (array) config('b2b_application.commercial_integration.user_columns', []);
        $values = [
            'customer_type' => (string) config('b2b_application.customer_type.b2b', 'b2b'),
            'pay_later_enabled' => $application->pay_later_enabled,
            'credit_limit' => $application->credit_limit,
            'payment_terms_days' => $application->payment_terms_days,
            'minimum_order_value' => $application->minimum_order_value,
            'price_group_id' => $application->approved_price_group_id,
            'account_manager_id' => $application->approved_account_manager_id,
        ];

        $updates = [];
        foreach ($values as $semantic => $value) {
            $column = $map[$semantic] ?? null;
            if (is_string($column) && $column !== '' && Schema::hasColumn('users', $column)) {
                $updates[$column] = $value;
            }
        }

        // customer_type is the one mandatory conversion field and is detected independently as a fallback.
        if (! array_key_exists('customer_type', $updates) && Schema::hasColumn('users', 'customer_type')) {
            $updates['customer_type'] = (string) config('b2b_application.customer_type.b2b', 'b2b');
        }

        if ($updates !== []) {
            DB::table('users')->where('id', $user->getKey())->update($updates);
        }
    }

    private function syncExistingTermsRow(User $user, B2BApplication $application): void
    {
        $integration = config('b2b_application.commercial_integration.existing_terms');

        if (! is_array($integration)) {
            return;
        }

        $table = $integration['table'] ?? null;
        $userKey = $integration['user_key'] ?? null;
        $columns = (array) ($integration['columns'] ?? []);

        if (! is_string($table) || $table === '' || ! is_string($userKey) || $userKey === '' || ! Schema::hasTable($table)) {
            return;
        }

        $query = DB::table($table)->where($userKey, $user->getKey());
        if (! $query->exists() && (bool) ($integration['update_existing_only'] ?? true)) {
            return;
        }

        $semanticValues = [
            'pay_later_enabled' => $application->pay_later_enabled,
            'credit_limit' => $application->credit_limit,
            'payment_terms_days' => $application->payment_terms_days,
            'minimum_order_value' => $application->minimum_order_value,
            'price_group_id' => $application->approved_price_group_id,
            'account_manager_id' => $application->approved_account_manager_id,
            'updated_at' => now(),
            'created_at' => now(),
        ];

        $updates = [];
        foreach ($semanticValues as $semantic => $value) {
            $column = $columns[$semantic] ?? null;
            if (is_string($column) && $column !== '' && Schema::hasColumn($table, $column)) {
                $updates[$column] = $value;
            }
        }

        if ($query->exists()) {
            unset($updates[$columns['created_at'] ?? '__never__']);
            if ($updates !== []) {
                $query->update($updates);
            }
            return;
        }

        if ($updates !== [] && ! (bool) ($integration['update_existing_only'] ?? true)) {
            $updates[$userKey] = $user->getKey();
            DB::table($table)->insert($updates);
        }
    }
}
