<?php

namespace Tests\Unit;

use App\Services\AutoTranslationService;
use App\Services\StockReservationService;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    public function test_auto_translation_reads_configured_driver_and_key(): void
    {
        config([
            'services.google_translate.driver' => 'google',
            'services.google_translate.key' => 'test-key',
        ]);

        $this->assertTrue(app(AutoTranslationService::class)->configured());

        config(['services.google_translate.key' => null]);

        $this->assertFalse(app(AutoTranslationService::class)->configured());
    }

    public function test_stock_reservation_ttl_reads_store_configuration_and_is_bounded(): void
    {
        $service = app(StockReservationService::class);

        config(['store.stock_reservation_ttl_minutes' => 5]);
        $this->assertSame(5, $service->holdMinutes());

        config(['store.stock_reservation_ttl_minutes' => 0]);
        $this->assertSame(1, $service->holdMinutes());

        config(['store.stock_reservation_ttl_minutes' => 60]);
        $this->assertSame(30, $service->holdMinutes());
    }
}
