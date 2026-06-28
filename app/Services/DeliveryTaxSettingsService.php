<?php

namespace App\Services;

use App\Models\DeliveryChargeRule;
use App\Models\DeliveryDistanceRule;
use App\Models\HandlingChargeRule;
use App\Models\HsnCode;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DeliveryTaxSettingsService
{
    private ?array $currentCache = null;

    public const DELIVERY_HSN_SETTING = 'delivery.delivery_hsn_code_id';
    public const HANDLING_HSN_SETTING = 'delivery.handling_hsn_code_id';

    /**
     * Return the currently configured service HSN/SAC defaults.
     *
     * These values are intentionally read from the DB settings table first so
     * admins can manage them from the Delivery settings screen. The .env values
     * in config/delivery.php remain as a safe fallback for older installs and
     * deployments that have not used the screen yet.
     */
    public function current(): array
    {
        if ($this->currentCache !== null) {
            return $this->currentCache;
        }

        return $this->currentCache = [
            'delivery' => $this->classification(
                settingKey: self::DELIVERY_HSN_SETTING,
                configCodeKey: 'delivery.delivery_sac_code',
                configRateKey: 'delivery.default_delivery_tax_rate',
            ),
            'handling' => $this->classification(
                settingKey: self::HANDLING_HSN_SETTING,
                configCodeKey: 'delivery.handling_sac_code',
                configRateKey: 'delivery.default_handling_tax_rate',
            ),
        ];
    }

    public function deliverySacCode(): ?string
    {
        return $this->current()['delivery']['code'] ?? null;
    }

    public function handlingSacCode(): ?string
    {
        return $this->current()['handling']['code'] ?? null;
    }

    public function deliveryTaxRate(?float $fallback = null): float
    {
        $settings = $this->current()['delivery'] ?? [];

        if (! empty($settings['code'])) {
            return $this->normaliseRate($settings['gst_rate'] ?? 0);
        }

        return $this->normaliseRate($fallback ?? config('delivery.default_delivery_tax_rate', 0));
    }

    public function handlingTaxRate(?float $fallback = null): float
    {
        $settings = $this->current()['handling'] ?? [];

        if (! empty($settings['code'])) {
            return $this->normaliseRate($settings['gst_rate'] ?? 0);
        }

        return $this->normaliseRate($fallback ?? config('delivery.default_handling_tax_rate', 0));
    }

    /**
     * Persist the selected service classifications and optionally sync existing
     * delivery/handling rule GST rates from the selected HSN/SAC records.
     */
    public function update(
        ?int $deliveryHsnCodeId,
        ?int $handlingHsnCodeId,
        bool $syncDeliveryRules = true,
        bool $syncHandlingRules = true,
    ): array {
        $this->putSetting(self::DELIVERY_HSN_SETTING, $deliveryHsnCodeId ? (string) $deliveryHsnCodeId : null);
        $this->putSetting(self::HANDLING_HSN_SETTING, $handlingHsnCodeId ? (string) $handlingHsnCodeId : null);
        $this->currentCache = null;

        $deliveryHsn = $deliveryHsnCodeId ? HsnCode::query()->find($deliveryHsnCodeId) : null;
        $handlingHsn = $handlingHsnCodeId ? HsnCode::query()->find($handlingHsnCodeId) : null;

        $updated = [
            'delivery_zone_rules' => 0,
            'delivery_distance_rules' => 0,
            'handling_rules' => 0,
        ];

        if ($syncDeliveryRules && $deliveryHsn) {
            $deliveryRate = $this->normaliseRate($deliveryHsn->gst_rate);
            $updated['delivery_zone_rules'] = DeliveryChargeRule::query()->update(['tax_rate' => $deliveryRate]);
            $updated['delivery_distance_rules'] = DeliveryDistanceRule::query()->update(['tax_rate' => $deliveryRate]);
        }

        if ($syncHandlingRules && $handlingHsn) {
            $handlingRate = $this->normaliseRate($handlingHsn->gst_rate);
            $updated['handling_rules'] = HandlingChargeRule::query()->update(['tax_rate' => $handlingRate]);
        }

        return $updated;
    }

    private function classification(string $settingKey, string $configCodeKey, string $configRateKey): array
    {
        $hsn = null;
        $settingValue = $this->getSetting($settingKey);

        try {
            if ($settingValue !== null && $settingValue !== '' && is_numeric($settingValue)) {
                $hsn = HsnCode::query()->find((int) $settingValue);
            }

            $configCode = trim((string) config($configCodeKey, ''));

            if (! $hsn && $configCode !== '') {
                $hsn = HsnCode::query()->where('code', $configCode)->first();
            }
        } catch (Throwable) {
            $hsn = null;
            $configCode = trim((string) config($configCodeKey, ''));
        }

        if ($hsn) {
            return [
                'hsn_code_id' => (int) $hsn->id,
                'code' => (string) $hsn->code,
                'name' => $hsn->name,
                'gst_rate' => $this->normaliseRate($hsn->gst_rate),
                'source' => $settingValue ? 'settings' : 'config',
            ];
        }

        return [
            'hsn_code_id' => null,
            'code' => $configCode !== '' ? $configCode : null,
            'name' => null,
            'gst_rate' => $this->normaliseRate(config($configRateKey, 0)),
            'source' => $configCode !== '' ? 'config' : 'default',
        ];
    }

    private function getSetting(string $key): ?string
    {
        if (! $this->settingsTableAvailable()) {
            return null;
        }

        try {
            $value = Setting::query()->where('key', $key)->value('value');

            return $value === null ? null : (string) $value;
        } catch (Throwable) {
            return null;
        }
    }

    private function putSetting(string $key, ?string $value): void
    {
        if (! $this->settingsTableAvailable()) {
            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => 'string',
                'group' => 'delivery',
            ],
        );
    }

    private function settingsTableAvailable(): bool
    {
        try {
            return Schema::hasTable('settings');
        } catch (Throwable) {
            return false;
        }
    }

    private function normaliseRate(mixed $rate): float
    {
        return round(max((float) ($rate ?? 0), 0), 2);
    }
}
