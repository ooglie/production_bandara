<?php

namespace Tests\Unit;

use App\Models\RecurringExpenseTemplate;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RecurringExpenseFrequencyTest extends TestCase
{
    public static function frequencies(): array
    {
        return [
            [RecurringExpenseTemplate::FREQUENCY_MONTHLY, '2026-01-31', '2026-02-28'],
            [RecurringExpenseTemplate::FREQUENCY_QUARTERLY, '2026-01-31', '2026-04-30'],
            [RecurringExpenseTemplate::FREQUENCY_HALF_YEARLY, '2026-01-31', '2026-07-31'],
            [RecurringExpenseTemplate::FREQUENCY_YEARLY, '2024-02-29', '2025-02-28'],
        ];
    }

    #[DataProvider('frequencies')]
    public function test_due_date_advances_without_month_overflow(string $frequency, string $from, string $expected): void
    {
        $template = new RecurringExpenseTemplate(['frequency' => $frequency]);

        self::assertSame($expected, $template->nextDateAfter(CarbonImmutable::parse($from))->toDateString());
    }
}
