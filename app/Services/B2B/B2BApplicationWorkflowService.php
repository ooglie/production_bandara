<?php

declare(strict_types=1);

namespace App\Services\B2B;

use App\Enums\B2BApplicationStatus;
use App\Models\B2BApplication;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class B2BApplicationWorkflowService
{
    private const BUSINESS_FIELDS = [
        'contact_first_name', 'contact_last_name', 'email', 'phone', 'whatsapp', 'preferred_contact_method',
        'legal_business_name', 'trading_name', 'business_type', 'gst_registered', 'gstin', 'pan',
        'fssai_number', 'website', 'address_line_1', 'address_line_2', 'state_id', 'city_id',
        'state_name', 'city_name', 'postal_code',
    ];

    private const REQUIREMENT_FIELDS = [
        'interested_categories', 'estimated_monthly_purchase', 'purchase_frequency', 'requirements_message',
        'terms_accepted_at',
    ];

    public function __construct(private readonly B2BApplicationNotifier $notifier) {}

    public function saveBusinessDetails(User $user, array $data): B2BApplication
    {
        return DB::transaction(function () use ($user, $data): B2BApplication {
            $application = $this->lockOrCreate($user, $data);
            $this->assertCustomerEditable($application);

            $application->fill(Arr::only($data, self::BUSINESS_FIELDS));
            $application->last_customer_edit_at = now();
            $application->lock_version++;
            $application->save();

            $application->recordHistory(
                event: 'business_details_saved',
                actor: $user,
                from: $application->status,
                to: $application->status,
                message: 'Business and contact details saved.',
                visibility: 'internal',
            );

            return $application->fresh() ?? $application;
        });
    }

    public function saveRequirements(User $user, B2BApplication $application, array $data): B2BApplication
    {
        return DB::transaction(function () use ($user, $application, $data): B2BApplication {
            $locked = $this->lockOwned($user, $application);
            $this->assertCustomerEditable($locked);

            $locked->fill(Arr::only($data, self::REQUIREMENT_FIELDS));
            $locked->last_customer_edit_at = now();
            $locked->lock_version++;
            $locked->save();

            $locked->recordHistory(
                event: 'requirements_saved',
                actor: $user,
                from: $locked->status,
                to: $locked->status,
                message: 'Purchase requirements saved.',
                visibility: 'internal',
            );

            // Deliberately do not change more_information_required to draft here.
            return $locked->fresh() ?? $locked;
        });
    }

    public function submit(User $user, B2BApplication $application): B2BApplication
    {
        $submitted = DB::transaction(function () use ($user, $application): B2BApplication {
            $locked = $this->lockOwned($user, $application);

            if (! $locked->status->customerCanSubmit()) {
                throw ValidationException::withMessages(['status' => 'This application cannot currently be submitted.']);
            }

            $this->assertComplete($locked);
            $from = $locked->status;
            $now = now();

            $locked->forceFill([
                'status' => B2BApplicationStatus::Submitted,
                'submitted_at' => $locked->submitted_at ?: $now,
                'resubmitted_at' => $from === B2BApplicationStatus::MoreInformationRequired ? $now : $locked->resubmitted_at,
                'customer_message' => null,
                'last_customer_edit_at' => $now,
                'lock_version' => $locked->lock_version + 1,
            ])->save();

            $locked->recordHistory(
                event: $from === B2BApplicationStatus::MoreInformationRequired ? 'resubmitted' : 'submitted',
                actor: $user,
                from: $from,
                to: B2BApplicationStatus::Submitted,
                message: $from === B2BApplicationStatus::MoreInformationRequired
                    ? 'Additional information submitted for review.'
                    : 'Business account application submitted.',
                visibility: 'customer',
            );

            return $locked->fresh(['user']) ?? $locked;
        });

        $this->notifier->admins(
            $submitted,
            'New B2B application received',
            $submitted->legal_business_name.' submitted business account application '.$submitted->application_number.'.',
            $user,
        );

        return $submitted;
    }

    public function assign(B2BApplication $application, User $actor, ?int $assigneeId): B2BApplication
    {
        return DB::transaction(function () use ($application, $actor, $assigneeId): B2BApplication {
            $locked = B2BApplication::query()->lockForUpdate()->findOrFail($application->getKey());
            $locked->assigned_to = $assigneeId;
            $locked->lock_version++;
            $locked->save();
            $locked->recordHistory('assigned', $actor, $locked->status, $locked->status, 'Application assignment updated.', 'internal', ['assigned_to' => $assigneeId]);

            return $locked->fresh(['assignee']) ?? $locked;
        });
    }

    public function startReview(B2BApplication $application, User $actor): B2BApplication
    {
        return DB::transaction(function () use ($application, $actor): B2BApplication {
            $locked = B2BApplication::query()->lockForUpdate()->findOrFail($application->getKey());

            if ($locked->status === B2BApplicationStatus::UnderReview) {
                return $locked;
            }

            if ($locked->status !== B2BApplicationStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Only a submitted application can be moved under review.']);
            }

            $from = $locked->status;
            $locked->forceFill([
                'status' => B2BApplicationStatus::UnderReview,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
                'assigned_to' => $locked->assigned_to ?: $actor->getKey(),
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $locked->recordHistory('review_started', $actor, $from, B2BApplicationStatus::UnderReview, 'Application review started.', 'customer');

            return $locked->fresh() ?? $locked;
        });
    }

    public function requestInformation(B2BApplication $application, User $actor, string $message): B2BApplication
    {
        $updated = DB::transaction(function () use ($application, $actor, $message): B2BApplication {
            $locked = B2BApplication::query()->lockForUpdate()->findOrFail($application->getKey());

            if (! in_array($locked->status, [B2BApplicationStatus::Submitted, B2BApplicationStatus::UnderReview], true)) {
                throw ValidationException::withMessages(['status' => 'Additional information can only be requested from an active review.']);
            }

            $from = $locked->status;
            $locked->forceFill([
                'status' => B2BApplicationStatus::MoreInformationRequired,
                'customer_message' => $message,
                'information_requested_at' => now(),
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => $locked->reviewed_at ?: now(),
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $locked->recordHistory('information_requested', $actor, $from, B2BApplicationStatus::MoreInformationRequired, $message, 'customer');

            return $locked->fresh(['user']) ?? $locked;
        });

        $this->notifier->customer($updated, 'Additional information required', $message);

        return $updated;
    }

    public function reject(B2BApplication $application, User $actor, string $message): B2BApplication
    {
        $updated = DB::transaction(function () use ($application, $actor, $message): B2BApplication {
            $locked = B2BApplication::query()->lockForUpdate()->findOrFail($application->getKey());

            if (! in_array($locked->status, [B2BApplicationStatus::Submitted, B2BApplicationStatus::UnderReview, B2BApplicationStatus::MoreInformationRequired], true)) {
                throw ValidationException::withMessages(['status' => 'This application cannot currently be rejected.']);
            }

            $from = $locked->status;
            $locked->forceFill([
                'status' => B2BApplicationStatus::Rejected,
                'customer_message' => $message,
                'rejected_by' => $actor->getKey(),
                'rejected_at' => now(),
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $locked->recordHistory('rejected', $actor, $from, B2BApplicationStatus::Rejected, $message, 'customer');

            return $locked->fresh(['user']) ?? $locked;
        });

        $this->notifier->customer($updated, 'Business account application update', $message);

        return $updated;
    }

    public function addInternalNote(B2BApplication $application, User $actor, string $message): void
    {
        DB::transaction(function () use ($application, $actor, $message): void {
            $locked = B2BApplication::query()->lockForUpdate()->findOrFail($application->getKey());
            $locked->recordHistory('internal_note', $actor, $locked->status, $locked->status, $message, 'internal');
        });
    }

    public function withdraw(User $user, B2BApplication $application): B2BApplication
    {
        $updated = DB::transaction(function () use ($user, $application): B2BApplication {
            $locked = $this->lockOwned($user, $application);

            if (! $locked->status->customerCanWithdraw()) {
                throw ValidationException::withMessages(['status' => 'This application cannot be withdrawn.']);
            }

            $from = $locked->status;
            $locked->forceFill([
                'status' => B2BApplicationStatus::Withdrawn,
                'withdrawn_at' => now(),
                'customer_message' => null,
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $locked->recordHistory('withdrawn', $user, $from, B2BApplicationStatus::Withdrawn, 'Application withdrawn by customer.', 'customer');

            return $locked->fresh() ?? $locked;
        });

        $this->notifier->admins($updated, 'B2B application withdrawn', $updated->application_number.' was withdrawn by the customer.', $user);

        return $updated;
    }

    public function restart(User $user, B2BApplication $application): B2BApplication
    {
        return DB::transaction(function () use ($user, $application): B2BApplication {
            $locked = $this->lockOwned($user, $application);

            if (! in_array($locked->status, [B2BApplicationStatus::Rejected, B2BApplicationStatus::Withdrawn], true)) {
                throw ValidationException::withMessages(['status' => 'This application does not need to be restarted.']);
            }

            $from = $locked->status;
            $locked->forceFill([
                'status' => B2BApplicationStatus::Draft,
                'customer_message' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'withdrawn_at' => null,
                'lock_version' => $locked->lock_version + 1,
            ])->save();
            $locked->recordHistory('restarted', $user, $from, B2BApplicationStatus::Draft, 'A new review cycle was started.', 'customer');

            return $locked->fresh() ?? $locked;
        });
    }

    private function lockOrCreate(User $user, array $data): B2BApplication
    {
        $existing = B2BApplication::query()->where('user_id', $user->getKey())->lockForUpdate()->first();

        if ($existing) {
            return $existing;
        }

        return B2BApplication::query()->create([
            'user_id' => $user->getKey(),
            'status' => B2BApplicationStatus::Draft,
            'contact_first_name' => $data['contact_first_name'],
            'contact_last_name' => $data['contact_last_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'],
            'preferred_contact_method' => $data['preferred_contact_method'] ?? 'phone',
            'legal_business_name' => $data['legal_business_name'],
            'business_type' => $data['business_type'],
            'address_line_1' => $data['address_line_1'],
            'state_name' => $data['state_name'],
            'city_name' => $data['city_name'],
            'postal_code' => $data['postal_code'],
        ]);
    }

    private function lockOwned(User $user, B2BApplication $application): B2BApplication
    {
        return B2BApplication::query()
            ->whereKey($application->getKey())
            ->where('user_id', $user->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function assertCustomerEditable(B2BApplication $application): void
    {
        if (! $application->status->customerCanEdit()) {
            throw ValidationException::withMessages(['status' => 'This application is locked while Bandara reviews it.']);
        }
    }

    private function assertComplete(B2BApplication $application): void
    {
        $required = [
            'contact_first_name', 'email', 'phone', 'legal_business_name', 'business_type', 'address_line_1',
            'state_name', 'city_name', 'postal_code', 'estimated_monthly_purchase', 'purchase_frequency',
        ];
        $missing = [];

        foreach ($required as $field) {
            if (blank($application->getAttribute($field))) {
                $missing[$field] = 'This field is required before submission.';
            }
        }

        if (empty($application->interested_categories)) {
            $missing['interested_categories'] = 'Select at least one product category.';
        }

        if (! $application->terms_accepted_at) {
            $missing['terms_accepted'] = 'Please accept the declaration before submission.';
        }

        if ($application->gst_registered && blank($application->gstin)) {
            $missing['gstin'] = 'GSTIN is required because the business is registered for GST.';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }
    }
}
