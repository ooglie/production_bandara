<?php

declare(strict_types=1);

namespace Tests\Unit\Authentication;

use App\Support\Authentication\StaffDashboardResolver;
use Tests\TestCase;

final class StaffDashboardResolverTest extends TestCase
{
    public function test_it_preserves_existing_role_specific_staff_dashboards(): void
    {
        $resolver = app(StaffDashboardResolver::class);

        self::assertSame(route('admin.dashboard'), $resolver->url((object) ['role' => 'Admin']));
        self::assertSame(route('manager.dashboard'), $resolver->url((object) ['role' => 'Manager']));
        self::assertSame(route('support.dashboard'), $resolver->url((object) ['role' => 'Support']));
        self::assertSame(route('accountant.dashboard'), $resolver->url((object) ['role' => 'CAAccountant']));
        self::assertSame(route('stores.dashboard'), $resolver->url((object) ['role' => 'Stores']));
        self::assertSame(route('delivery.index'), $resolver->url((object) ['role' => 'DeliveryAgent']));
    }
}
