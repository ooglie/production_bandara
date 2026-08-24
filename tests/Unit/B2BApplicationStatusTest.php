<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\B2BApplicationStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class B2BApplicationStatusTest extends TestCase
{
    #[DataProvider('editableStatuses')]
    public function test_only_expected_customer_statuses_are_editable(B2BApplicationStatus $status, bool $editable): void
    {
        self::assertSame($editable, $status->customerCanEdit());
    }

    public static function editableStatuses(): array
    {
        return [
            'draft' => [B2BApplicationStatus::Draft, true],
            'submitted' => [B2BApplicationStatus::Submitted, false],
            'under review' => [B2BApplicationStatus::UnderReview, false],
            'more information' => [B2BApplicationStatus::MoreInformationRequired, true],
            'approved' => [B2BApplicationStatus::Approved, false],
            'rejected' => [B2BApplicationStatus::Rejected, false],
            'withdrawn' => [B2BApplicationStatus::Withdrawn, false],
        ];
    }
}
