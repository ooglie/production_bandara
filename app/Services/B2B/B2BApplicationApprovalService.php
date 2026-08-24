<?php

declare(strict_types=1);

namespace App\Services\B2B;

use App\Enums\B2BApplicationStatus;
use App\Events\B2BApplicationApproved;
use App\Models\B2BApplication;
use App\Models\B2BCustomerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class B2BApplicationApprovalService
{
    public function __construct(
        private readonly B2BCommercialTermsSynchronizer $commercialTerms,
        private readonly B2BApplicationNotifier $notifier,
    ) {}

    public function approve(B2BApplication $application, User $actor, array $terms): B2BApplication
    {
        $approved = DB::transaction(function () use ($application, $actor, $terms): B2BApplication {
            /** @var B2BApplication $locked */
            $locked = B2BApplication::query()->lockForUpdate()->findOrFail($application->getKey());

            if (! in_array($locked->status, [B2BApplicationStatus::Submitted, B2BApplicationStatus::UnderReview], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Only a submitted or under-review application can be approved.',
                ]);
            }

            /** @var User $customer */
            $customer = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $from = $locked->status;
            $approvedAt = now();

            $locked->forceFill([
                'status' => B2BApplicationStatus::Approved,
                'approved_by' => $actor->getKey(),
                'approved_at' => $approvedAt,
                'reviewed_by' => $locked->reviewed_by ?: $actor->getKey(),
                'reviewed_at' => $locked->reviewed_at ?: $approvedAt,
                'approved_price_group_id' => $terms['approved_price_group_id'] ?? null,
                'pay_later_enabled' => (bool) ($terms['pay_later_enabled'] ?? false),
                'credit_limit' => (float) ($terms['credit_limit'] ?? 0),
                'payment_terms_days' => (int) ($terms['payment_terms_days'] ?? 0),
                'minimum_order_value' => (float) ($terms['minimum_order_value'] ?? 0),
                'delivery_arrangement' => $terms['delivery_arrangement'] ?? null,
                'approved_account_manager_id' => $terms['approved_account_manager_id'] ?? null,
                'customer_message' => $terms['customer_message'] ?? 'Your Bandara Business Account has been approved.',
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            B2BCustomerProfile::query()->updateOrCreate(
                ['user_id' => $customer->getKey()],
                [
                    'b2b_application_id' => $locked->getKey(),
                    'legal_business_name' => $locked->legal_business_name,
                    'trading_name' => $locked->trading_name,
                    'business_type' => $locked->business_type,
                    'gstin' => $locked->gstin,
                    'pan' => $locked->pan,
                    'fssai_number' => $locked->fssai_number,
                    'address_line_1' => $locked->address_line_1,
                    'address_line_2' => $locked->address_line_2,
                    'state_id' => $locked->state_id,
                    'city_id' => $locked->city_id,
                    'state_name' => $locked->state_name,
                    'city_name' => $locked->city_name,
                    'postal_code' => $locked->postal_code,
                    'price_group_id' => $locked->approved_price_group_id,
                    'pay_later_enabled' => $locked->pay_later_enabled,
                    'credit_limit' => $locked->credit_limit,
                    'payment_terms_days' => $locked->payment_terms_days,
                    'minimum_order_value' => $locked->minimum_order_value,
                    'delivery_arrangement' => $locked->delivery_arrangement,
                    'account_manager_id' => $locked->approved_account_manager_id,
                    'approved_by' => $actor->getKey(),
                    'approved_at' => $approvedAt,
                    'is_active' => true,
                ],
            );

            // This changes only compatible fields; roles, addresses, orders and credit history are untouched.
            $this->commercialTerms->sync($customer, $locked);

            $locked->recordHistory(
                event: 'approved',
                actor: $actor,
                from: $from,
                to: B2BApplicationStatus::Approved,
                message: $locked->customer_message,
                visibility: 'customer',
                metadata: [
                    'pay_later_enabled' => $locked->pay_later_enabled,
                    'payment_terms_days' => $locked->payment_terms_days,
                    'minimum_order_value' => $locked->minimum_order_value,
                ],
            );

            return $locked->fresh(['user', 'profile', 'histories']) ?? $locked;
        });

        $this->notifier->customer(
                $approved,
                'Your Bandara Business Account is approved',
                $approved->customer_message ?: 'You can now sign in to view business pricing and place B2B orders.',
        );
        B2BApplicationApproved::dispatch($approved);

        return $approved;
    }
}
