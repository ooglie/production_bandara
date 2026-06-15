<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\DeliveryChargeRule;
use App\Models\DeliveryDistanceRule;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\HandlingChargeRule;
use App\Models\User;
use Illuminate\Support\Carbon;

class DeliveryChargeService
{
    public function quote(?User $user, ?CustomerAddress $address, float $orderValue, string $temperatureMode = 'all'): array
    {
        $orderValue = round(max(0, $orderValue), 2);
        $customerType = $this->customerType($user);
        $pincode = $this->normalizePincode($address?->pincode);
        $zone = $pincode !== null ? $this->zoneForPincode($pincode) : null;
        $requireServiceable = (bool) config('delivery.require_serviceable_pincode', false);
        $distanceEnabled = (bool) config('delivery.distance_enabled', false);
        $distanceRequired = (bool) config('delivery.distance_required', false);
        $fallbackToZone = (bool) config('delivery.distance_fallback_to_zone', true);

        $messages = [];
        $distanceQuote = null;
        $distanceRule = null;
        $deliveryRule = null;
        $deliveryFeeSource = 'none';
        $deliveryFee = 0.0;
        $deliveryTaxRate = (float) config('delivery.default_delivery_tax_rate', 0);

        if (! $pincode) {
            $messages[] = 'Select a delivery address to calculate delivery fees.';
        }

        if ($distanceEnabled && $address) {
            $distanceQuote = app(DeliveryDistanceService::class)->routeForAddress($address);

            if (! ($distanceQuote['success'] ?? false)) {
                $messages[] = $distanceQuote['message'] ?? 'Distance-based delivery calculation is not available.';
            } else {
                $distanceRule = $this->distanceRuleFor(
                    $customerType,
                    $orderValue,
                    (float) ($distanceQuote['distance_km'] ?? 0)
                );

                if ($distanceRule) {
                    $deliveryFee = $this->feeForDistanceRule($distanceRule, $orderValue, (float) $distanceQuote['distance_km']);
                    $deliveryTaxRate = (float) ($distanceRule->tax_rate ?? config('delivery.default_delivery_tax_rate', 0));
                    $deliveryFeeSource = 'distance';
                } else {
                    $messages[] = 'No distance-based delivery fee rule matched this address distance.';
                }
            }
        }

        $canUseZoneFallback = $deliveryFeeSource !== 'distance'
            && (! $distanceEnabled || $fallbackToZone || ! $distanceRequired);

        if ($canUseZoneFallback) {
            $deliveryRule = $zone ? $this->deliveryRuleFor($zone, $customerType, $orderValue) : null;

            if ($deliveryRule) {
                $deliveryFee = $this->feeForRule($deliveryRule, $orderValue, 'delivery_fee', 'free_delivery_above');
                $deliveryTaxRate = (float) ($deliveryRule->tax_rate ?? config('delivery.default_delivery_tax_rate', 0));
                $deliveryFeeSource = 'zone';
            }
        }

        if ($pincode && ! $zone && $deliveryFeeSource !== 'distance') {
            $messages[] = $requireServiceable
                ? 'This pincode is not mapped to a serviceable delivery zone yet.'
                : 'Delivery zone is not mapped for this pincode yet; delivery zone fallback fee is treated as ₹0.';
        }

        $distanceBlocked = $distanceEnabled
            && $distanceRequired
            && ! ($distanceQuote['success'] ?? false)
            && ! $fallbackToZone;

        $serviceable = ! $distanceBlocked && ($zone !== null || ! $requireServiceable || $deliveryFeeSource === 'distance');

        $handlingRule = $this->handlingRuleFor($customerType, $temperatureMode, $orderValue);
        $handlingFee = $handlingRule ? $this->feeForRule($handlingRule, $orderValue, 'handling_fee', 'free_handling_above') : 0.0;
        $handlingTaxRate = (float) ($handlingRule?->tax_rate ?? config('delivery.default_handling_tax_rate', 0));

        $deliveryTax = round($deliveryFee * max($deliveryTaxRate, 0) / 100, 2);
        $handlingTax = round($handlingFee * max($handlingTaxRate, 0) / 100, 2);

        return [
            'serviceable' => $serviceable,
            'messages' => array_values(array_unique(array_filter($messages))),
            'customer_type' => $customerType,
            'pincode' => $pincode,
            'zone_id' => $zone?->id,
            'zone_name' => $zone?->name,
            'zone_code' => $zone?->code,
            'delivery_rule_id' => $deliveryRule?->id,
            'distance_rule_id' => $distanceRule?->id,
            'delivery_fee_source' => $deliveryFeeSource,
            'delivery_distance_km' => $distanceQuote['distance_km'] ?? null,
            'delivery_duration_minutes' => $distanceQuote['duration_minutes'] ?? null,
            'delivery_distance_provider' => $distanceQuote['provider'] ?? null,
            'delivery_distance_calculated_at' => $distanceQuote['calculated_at'] ?? null,
            'delivery_distance_status' => $distanceQuote['status'] ?? null,
            'delivery_fee' => round($deliveryFee, 2),
            'handling_fee' => round($handlingFee, 2),
            'delivery_tax_rate' => round(max($deliveryTaxRate, 0), 2),
            'handling_tax_rate' => round(max($handlingTaxRate, 0), 2),
            'delivery_tax_amount' => $deliveryTax,
            'handling_tax_amount' => $handlingTax,
            'fee_total' => round($deliveryFee + $handlingFee, 2),
            'tax_total' => round($deliveryTax + $handlingTax, 2),
            'grand_total' => round($deliveryFee + $handlingFee + $deliveryTax + $handlingTax, 2),
            'delivery_free_above' => $distanceRule?->free_delivery_above ?? $deliveryRule?->free_delivery_above,
            'handling_free_above' => $handlingRule?->free_handling_above,
        ];
    }

    public function splitChargeTaxForState(array $quote, ?string $state): array
    {
        $taxTotal = round((float) ($quote['tax_total'] ?? 0), 2);
        $isMaharashtra = trim((string) $state) !== '' && strcasecmp(trim((string) $state), 'Maharashtra') === 0;

        if ($isMaharashtra) {
            $cgst = round($taxTotal / 2, 2);
            return [
                'gst_type' => 'intra_state',
                'cgst_amount' => $cgst,
                'sgst_amount' => round($taxTotal - $cgst, 2),
                'igst_amount' => null,
                'tax_total' => $taxTotal,
            ];
        }

        return [
            'gst_type' => 'inter_state',
            'cgst_amount' => null,
            'sgst_amount' => null,
            'igst_amount' => $taxTotal,
            'tax_total' => $taxTotal,
        ];
    }

    private function customerType(?User $user): string
    {
        if (! $user) {
            return 'guest';
        }

        return (($user->customer_type ?? 'b2c') === 'b2b') ? 'b2b' : 'b2c';
    }

    private function normalizePincode(?string $pincode): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $pincode);
        return $digits !== '' ? $digits : null;
    }

    private function zoneForPincode(string $pincode): ?DeliveryZone
    {
        $mapping = DeliveryZonePincode::query()
            ->with('zone')
            ->where('pincode', $pincode)
            ->where('is_active', true)
            ->first();

        return ($mapping?->zone?->is_active) ? $mapping->zone : null;
    }

    private function deliveryRuleFor(DeliveryZone $zone, string $customerType, float $orderValue): ?DeliveryChargeRule
    {
        return DeliveryChargeRule::query()
            ->where('delivery_zone_id', $zone->id)
            ->where('is_active', true)
            ->whereIn('customer_type', [$customerType, 'all'])
            ->where('min_order_value', '<=', $orderValue)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', Carbon::now());
            })
            ->orderByRaw("CASE WHEN customer_type = ? THEN 0 ELSE 1 END", [$customerType])
            ->orderByDesc('min_order_value')
            ->orderByDesc('id')
            ->first();
    }

    private function distanceRuleFor(string $customerType, float $orderValue, float $distanceKm): ?DeliveryDistanceRule
    {
        if ($distanceKm <= 0) {
            return null;
        }

        return DeliveryDistanceRule::query()
            ->where('is_active', true)
            ->whereIn('customer_type', [$customerType, 'all'])
            ->where('min_order_value', '<=', $orderValue)
            ->where('min_distance_km', '<=', $distanceKm)
            ->where(function ($query) use ($distanceKm) {
                $query->whereNull('max_distance_km')->orWhere('max_distance_km', '>=', $distanceKm);
            })
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', Carbon::now());
            })
            ->orderByRaw("CASE WHEN customer_type = ? THEN 0 ELSE 1 END", [$customerType])
            ->orderByDesc('min_order_value')
            ->orderByDesc('min_distance_km')
            ->orderByDesc('id')
            ->first();
    }

    private function handlingRuleFor(string $customerType, string $temperatureMode, float $orderValue): ?HandlingChargeRule
    {
        $temperatureMode = in_array($temperatureMode, ['all', 'frozen', 'chilled', 'ambient'], true) ? $temperatureMode : 'all';

        return HandlingChargeRule::query()
            ->where('is_active', true)
            ->whereIn('customer_type', [$customerType, 'all'])
            ->whereIn('temperature_mode', [$temperatureMode, 'all'])
            ->where('min_order_value', '<=', $orderValue)
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', Carbon::now());
            })
            ->orderByRaw("CASE WHEN customer_type = ? THEN 0 ELSE 1 END", [$customerType])
            ->orderByRaw("CASE WHEN temperature_mode = ? THEN 0 ELSE 1 END", [$temperatureMode])
            ->orderByDesc('min_order_value')
            ->orderByDesc('id')
            ->first();
    }

    private function feeForRule(object $rule, float $orderValue, string $feeField, string $freeAboveField): float
    {
        $freeAbove = $rule->{$freeAboveField};
        if ($freeAbove !== null && (float) $freeAbove > 0 && $orderValue >= (float) $freeAbove) {
            return 0.0;
        }

        if ($freeAbove !== null && (float) $freeAbove === 0.0) {
            return 0.0;
        }

        return round(max(0, (float) ($rule->{$feeField} ?? 0)), 2);
    }

    private function feeForDistanceRule(DeliveryDistanceRule $rule, float $orderValue, float $distanceKm): float
    {
        $freeAbove = $rule->free_delivery_above;
        if ($freeAbove !== null && (float) $freeAbove > 0 && $orderValue >= (float) $freeAbove) {
            return 0.0;
        }

        if ($freeAbove !== null && (float) $freeAbove === 0.0) {
            return 0.0;
        }

        $baseFee = max(0, (float) ($rule->delivery_fee ?? 0));
        $perKmFee = max(0, (float) ($rule->per_km_fee ?? 0));
        $includedDistanceKm = max(0, (float) ($rule->included_distance_km ?? 0));

        if ($perKmFee <= 0) {
            return round($baseFee, 2);
        }

        $chargeableDistanceKm = max(0, $distanceKm - $includedDistanceKm);
        $chargeableKmUnits = (int) ceil($chargeableDistanceKm);

        return round($baseFee + ($perKmFee * $chargeableKmUnits), 2);
    }
}
