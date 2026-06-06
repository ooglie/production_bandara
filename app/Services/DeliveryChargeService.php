<?php

namespace App\Services;

use App\Models\CustomerAddress;
use App\Models\DeliveryChargeRule;
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

        $deliveryRule = $zone ? $this->deliveryRuleFor($zone, $customerType, $orderValue) : null;
        $handlingRule = $this->handlingRuleFor($customerType, $temperatureMode, $orderValue);

        $serviceable = $zone !== null || ! $requireServiceable;
        $messages = [];

        if (! $pincode) {
            $messages[] = 'Select a delivery address to calculate delivery fees.';
        } elseif (! $zone) {
            $messages[] = $requireServiceable
                ? 'This pincode is not mapped to a serviceable delivery zone yet.'
                : 'Delivery zone is not mapped for this pincode yet; delivery fee is currently treated as ₹0.';
        }

        $deliveryFee = $deliveryRule ? $this->feeForRule($deliveryRule, $orderValue, 'delivery_fee', 'free_delivery_above') : 0.0;
        $handlingFee = $handlingRule ? $this->feeForRule($handlingRule, $orderValue, 'handling_fee', 'free_handling_above') : 0.0;

        $deliveryTaxRate = (float) ($deliveryRule?->tax_rate ?? config('delivery.default_delivery_tax_rate', 0));
        $handlingTaxRate = (float) ($handlingRule?->tax_rate ?? config('delivery.default_handling_tax_rate', 0));

        $deliveryTax = round($deliveryFee * max($deliveryTaxRate, 0) / 100, 2);
        $handlingTax = round($handlingFee * max($handlingTaxRate, 0) / 100, 2);

        return [
            'serviceable' => $serviceable,
            'messages' => $messages,
            'customer_type' => $customerType,
            'pincode' => $pincode,
            'zone_id' => $zone?->id,
            'zone_name' => $zone?->name,
            'zone_code' => $zone?->code,
            'delivery_rule_id' => $deliveryRule?->id,
            'handling_rule_id' => $handlingRule?->id,
            'delivery_fee' => round($deliveryFee, 2),
            'handling_fee' => round($handlingFee, 2),
            'delivery_tax_rate' => round(max($deliveryTaxRate, 0), 2),
            'handling_tax_rate' => round(max($handlingTaxRate, 0), 2),
            'delivery_tax_amount' => $deliveryTax,
            'handling_tax_amount' => $handlingTax,
            'fee_total' => round($deliveryFee + $handlingFee, 2),
            'tax_total' => round($deliveryTax + $handlingTax, 2),
            'grand_total' => round($deliveryFee + $handlingFee + $deliveryTax + $handlingTax, 2),
            'delivery_free_above' => $deliveryRule?->free_delivery_above,
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
}
