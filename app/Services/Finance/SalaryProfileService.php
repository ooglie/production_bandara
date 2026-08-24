<?php

namespace App\Services\Finance;

use App\Models\SalaryProfile;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class SalaryProfileService
{
    public function profileForMonth(int $userId, CarbonInterface|string $month): ?SalaryProfile
    {
        $start = CarbonImmutable::parse($month)->startOfMonth();

        return SalaryProfile::query()
            ->where('user_id', $userId)
            ->effectiveOn($start)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    public function overlaps(
        int $userId,
        CarbonInterface|string $effectiveFrom,
        CarbonInterface|string|null $effectiveTo,
        ?int $ignoreProfileId = null,
    ): bool {
        $start = CarbonImmutable::parse($effectiveFrom)->startOfDay();
        $end = $effectiveTo === null
            ? CarbonImmutable::create(9999, 12, 31)
            : CarbonImmutable::parse($effectiveTo)->startOfDay();

        return SalaryProfile::query()
            ->where('user_id', $userId)
            ->when($ignoreProfileId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreProfileId))
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->where(function (Builder $query) use ($start): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $start->toDateString());
            })
            ->exists();
    }
}
