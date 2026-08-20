<?php

namespace App\Services;

use InvalidArgumentException;
use LogicException;

class GstPlaceOfSupplyService
{
    public const BASIS_BILL_TO_GSTIN = 'bill_to_gstin';
    public const BASIS_SHIPPING_ADDRESS = 'shipping_address';

    public function normalizeGstin(?string $gstin): ?string
    {
        $normalized = strtoupper((string) preg_replace('/[\s-]+/', '', trim((string) $gstin)));

        return $normalized !== '' ? $normalized : null;
    }

    public function isValidFormat(?string $gstin): bool
    {
        $gstin = $this->normalizeGstin($gstin);

        return $gstin !== null
            && preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/', $gstin) === 1;
    }

    public function gstStateCodeFromGstin(?string $gstin): ?string
    {
        $gstin = $this->normalizeGstin($gstin);

        return $gstin !== null && strlen($gstin) >= 2
            ? substr($gstin, 0, 2)
            : null;
    }

    public function stateByGstCode(?string $gstCode): ?array
    {
        $gstCode = str_pad(trim((string) $gstCode), 2, '0', STR_PAD_LEFT);
        $state = config("gst.states.{$gstCode}");

        return is_array($state) ? $state : null;
    }

    public function gstStateCodesForAddress(?string $stateCode, ?string $stateName = null): array
    {
        $stateCode = strtoupper(trim((string) $stateCode));
        $stateName = trim((string) $stateName);
        $matches = [];

        foreach ((array) config('gst.states', []) as $gstCode => $state) {
            $codeMatches = $stateCode !== ''
                && strtoupper((string) ($state['code'] ?? '')) === $stateCode;
            $nameMatches = $stateName !== ''
                && strcasecmp((string) ($state['name'] ?? ''), $stateName) === 0;

            if ($codeMatches || $nameMatches) {
                $matches[(string) $gstCode] = (bool) ($state['canonical_for_address'] ?? true);
            }
        }

        uksort($matches, static function (string $left, string $right) use ($matches): int {
            $canonicalComparison = ((int) $matches[$right]) <=> ((int) $matches[$left]);

            return $canonicalComparison !== 0
                ? $canonicalComparison
                : strcmp($left, $right);
        });

        return array_map(
            static fn (int|string $gstCode): string => str_pad((string) $gstCode, 2, '0', STR_PAD_LEFT),
            array_keys($matches),
        );
    }

    public function gstStateCodeForAddress(?string $stateCode, ?string $stateName = null): ?string
    {
        return $this->gstStateCodesForAddress($stateCode, $stateName)[0] ?? null;
    }

    public function supplierGstin(): string
    {
        $gstin = $this->normalizeGstin((string) config(
            'gst.supplier_gstin',
            config('store.invoice.seller.gstin_no')
        ));

        if (! $this->isValidFormat($gstin) || ! $this->stateByGstCode($this->gstStateCodeFromGstin($gstin))) {
            throw new LogicException('Bandara supplier GSTIN is missing or invalid. Check STORE_INVOICE_GSTIN_NO.');
        }

        return (string) $gstin;
    }

    public function assertValidGstin(
        ?string $gstin,
        ?string $expectedAddressStateCode = null,
        ?string $expectedAddressStateName = null,
    ): string {
        $gstin = $this->normalizeGstin($gstin);

        if ($gstin === null) {
            throw new InvalidArgumentException('Please enter a GSTIN.');
        }

        if (! $this->isValidFormat($gstin)) {
            throw new InvalidArgumentException('Enter a GSTIN in the standard 15-character format.');
        }

        $gstStateCode = $this->gstStateCodeFromGstin($gstin);
        $gstState = $this->stateByGstCode($gstStateCode);

        if (! $gstState) {
            throw new InvalidArgumentException('The GSTIN begins with an unrecognised GST state code.');
        }

        $expectedStateCodes = $this->gstStateCodesForAddress(
            $expectedAddressStateCode,
            $expectedAddressStateName
        );
        $expectedStateCode = $expectedStateCodes[0] ?? null;

        if (($expectedAddressStateCode || $expectedAddressStateName) && $expectedStateCode === null) {
            throw new InvalidArgumentException('The selected billing state could not be mapped to a GST state code.');
        }

        if ($expectedStateCode !== null && ! in_array($gstStateCode, $expectedStateCodes, true)) {
            $expected = $this->stateByGstCode($expectedStateCode);

            throw new InvalidArgumentException(sprintf(
                'The GSTIN belongs to %s (code %s), but the selected billing address is in %s (code %s).',
                $gstState['name'],
                $gstStateCode,
                $expected['name'] ?? ($expectedAddressStateName ?: $expectedAddressStateCode),
                $expectedStateCode,
            ));
        }

        return $gstin;
    }

    /**
     * Resolve GST mode for a normal domestic goods order.
     *
     * When a Bill-To GSTIN is supplied, its state code is the place of supply
     * for the Bill-To/Ship-To treatment selected by Bandara. Without a GSTIN,
     * the Ship-To state determines the place of supply.
     */
    public function resolve(
        ?string $billToGstin,
        ?string $billingStateCode,
        ?string $billingStateName,
        ?string $shippingStateCode,
        ?string $shippingStateName,
    ): array {
        $supplierGstin = $this->supplierGstin();
        $supplierStateCode = (string) $this->gstStateCodeFromGstin($supplierGstin);
        $shipToStateCode = $this->gstStateCodeForAddress($shippingStateCode, $shippingStateName);

        if ($shipToStateCode === null) {
            throw new InvalidArgumentException('The selected delivery state could not be mapped to a GST state code.');
        }

        $billToGstin = $this->normalizeGstin($billToGstin);
        $billToStateCode = null;
        $basis = self::BASIS_SHIPPING_ADDRESS;

        if ($billToGstin !== null) {
            $billToGstin = $this->assertValidGstin(
                $billToGstin,
                $billingStateCode,
                $billingStateName,
            );
            $billToStateCode = (string) $this->gstStateCodeFromGstin($billToGstin);
            $placeOfSupplyStateCode = $billToStateCode;
            $basis = self::BASIS_BILL_TO_GSTIN;
        } else {
            $placeOfSupplyStateCode = $shipToStateCode;
        }

        $placeOfSupply = $this->stateByGstCode($placeOfSupplyStateCode);
        $shipToState = $this->stateByGstCode($shipToStateCode);
        $isBillToShipTo = $billToGstin !== null && $billToStateCode !== $shipToStateCode;
        $gstType = $supplierStateCode === $placeOfSupplyStateCode
            ? 'intra_state'
            : 'inter_state';

        return [
            'supplier_gstin' => $supplierGstin,
            'supplier_gst_state_code' => $supplierStateCode,
            'bill_to_gstin' => $billToGstin,
            'bill_to_gst_state_code' => $billToStateCode,
            'ship_to_gst_state_code' => $shipToStateCode,
            'place_of_supply_gst_state_code' => $placeOfSupplyStateCode,
            'place_of_supply_state_name' => $placeOfSupply['name'] ?? null,
            'ship_to_state_name' => $shipToState['name'] ?? null,
            'gst_determination_basis' => $basis,
            'is_bill_to_ship_to' => $isBillToShipTo,
            'gst_type' => $gstType,
            'tax_label' => $gstType === 'intra_state' ? 'CGST + SGST' : 'IGST',
        ];
    }
}
