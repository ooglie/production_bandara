<?php

namespace Tests\Unit;

use App\Models\SalaryEntry;
use PHPUnit\Framework\TestCase;

class SalaryEntryCalculationTest extends TestCase
{
    public function test_net_salary_adds_additions_and_subtracts_deductions(): void
    {
        self::assertSame('53500.00', SalaryEntry::calculateNet('50000.00', '5000.00', '1500.00'));
    }

    public function test_net_salary_never_returns_a_negative_number(): void
    {
        self::assertSame('0.00', SalaryEntry::calculateNet('1000.00', '0.00', '1500.00'));
    }

    public function test_paid_and_cancelled_records_are_locked_for_audit_history(): void
    {
        self::assertTrue((new SalaryEntry(['payment_status' => SalaryEntry::STATUS_PAID]))->isLockedForEditing());
        self::assertTrue((new SalaryEntry(['payment_status' => SalaryEntry::STATUS_CANCELLED]))->isLockedForEditing());
    }

    public function test_pending_and_held_records_remain_reviewable(): void
    {
        self::assertFalse((new SalaryEntry(['payment_status' => SalaryEntry::STATUS_PENDING]))->isLockedForEditing());
        self::assertFalse((new SalaryEntry(['payment_status' => SalaryEntry::STATUS_HELD]))->isLockedForEditing());
    }
}
