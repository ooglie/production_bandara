<?php

namespace App\Services\Finance;

use App\Models\SalaryEntry;
use App\Models\SalaryProfile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class SalaryEntryGenerator
{
    /**
     * @return array{created:int, skipped:int, errors:array<int, string>}
     */
    public function generateForMonth(
        CarbonInterface|string|null $month = null,
        ?int $createdById = null,
    ): array {
        $monthStart = $month === null
            ? CarbonImmutable::today()->startOfMonth()
            : CarbonImmutable::parse($month)->startOfMonth();

        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];

        $profiles = SalaryProfile::query()
            ->with('staffMember')
            ->effectiveOn($monthStart)
            ->whereHas('staffMember', function (Builder $query): void {
                $query->where('customer_type', 'staff')
                    ->where('is_active', true);
            })
            ->orderBy('user_id')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get()
            ->unique('user_id');

        foreach ($profiles as $profile) {
            try {
                if ($profile->staffMember === null) {
                    $result['skipped']++;
                    continue;
                }

                $entry = SalaryEntry::query()->firstOrCreate(
                    [
                        'user_id' => $profile->user_id,
                        'salary_month' => $monthStart->toDateString(),
                    ],
                    [
                        'salary_profile_id' => $profile->id,
                        'staff_name' => $profile->staffMember->name,
                        'basic_salary' => $profile->monthly_salary,
                        'additions' => 0,
                        'deductions' => 0,
                        'net_payable' => $profile->monthly_salary,
                        'payment_status' => SalaryEntry::STATUS_PENDING,
                        'created_by_id' => $createdById,
                        'updated_by_id' => $createdById,
                    ],
                );

                if ($entry->wasRecentlyCreated) {
                    $result['created']++;
                } else {
                    $result['skipped']++;
                }
            } catch (Throwable $exception) {
                report($exception);
                $result['errors'][] = "Staff {$profile->user_id}: {$exception->getMessage()}";
            }
        }

        return $result;
    }
}
