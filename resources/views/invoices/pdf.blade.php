<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page { margin: 34px 44px 38px 44px; }
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.22;
            color: #111;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .brand-header { margin-bottom: 24px; }
        .brand-header td { border: 0; vertical-align: middle; }
        .logo { width: 52px; height: 52px; display: block; }
        .tax-invoice-title {
            font-size: 17px;
            font-weight: 700;
            text-align: center;
            letter-spacing: .5px;
        }
        .header-table td, .bank-table td { border: 0; vertical-align: top; }
        .header-table td { padding: 0 12px 0 0; }
        .header-table td:last-child { padding-right: 0; }
        .heading {
            font-size: 14px;
            font-weight: 700;
            color: #333;
            letter-spacing: .2px;
            margin-bottom: 12px;
        }
        .invoice-meta td { border: 0; padding: 0 0 5px 0; vertical-align: top; }
        .invoice-meta .label { width: 72px; white-space: nowrap; }
        .party-name { font-size: 10.5px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .party-meta { font-size: 8px; line-height: 1.3; margin-bottom: 4px; }
        .party-address { font-size: 8px; line-height: 1.3; }

        .items-table { margin-top: 30px; table-layout: fixed; }
        .items-table th, .items-table td {
            border: .5px dotted #b7b7b7;
            padding: 5px 5px;
            vertical-align: top;
        }
        .items-table th { background: #f1f1f1; font-size: 9px; font-weight: 700; line-height: 1.05; }
        .items-table td { font-size: 9px; }
        .items-table .sr { width: 5%; text-align: center; }
        .items-table .desc { width: 32%; text-align: left; }
        .items-table .hsn { width: 12%; text-align: center; }
        .items-table .qty { width: 8%; text-align: right; }
        .items-table .wt { width: 10%; text-align: right; }
        .items-table .rate { width: 15%; text-align: right; }
        .items-table .amount-col { width: 18%; text-align: right; }
        .line-description, .service-description { font-weight: 700; }
        .rate-unit { font-size: 7px; white-space: nowrap; }
        .summary-label { text-align: right; font-weight: 400; }
        .summary-total { background: #f6f6f6; font-weight: 700; }
        .summary-total td { font-weight: 700; }
        .money { white-space: nowrap; }

        .amount-words { margin: 14px 0 0 6px; font-size: 10px; font-weight: 700; }
        .tax-title { margin: 20px 0 5px 0; font-size: 10px; font-weight: 700; }
        .tax-table { table-layout: fixed; }
        .tax-table th, .tax-table td {
            border: .6px solid #777;
            padding: 4px 4px;
            font-size: 8px;
            text-align: right;
            vertical-align: middle;
        }
        .tax-table th { background: #f3f3f3; font-weight: 700; text-align: center; }
        .tax-table .code { text-align: left; }
        .tax-table .tax-total-row td { font-weight: 700; background: #fafafa; }

        .lower-section { margin-top: 18px; }
        .bank-title { font-size: 10px; font-weight: 700; margin-bottom: 2px; }
        .bank-table { width: 330px; font-size: 9px; }
        .bank-table td { padding: 1px 0; line-height: 1.08; }
        .bank-table .bank-label { width: 100px; }
        .bank-table .bank-colon { width: 15px; text-align: center; }
        .bank-table .bank-value { width: 205px; }
        .qr-wrap { text-align: right; padding-top: 2px; }
        .qr-wrap img { width: 108px; height: 108px; display: inline-block; }
        .thanks { margin-top: 28px; font-size: 9px; }
        .signoff { margin-top: 24px; font-size: 9px; }
        .company-footer { margin-top: 6px; margin-left: 22px; font-size: 8px; line-height: 1.25; }
        .company-address { margin-top: 12px; margin-left: 22px; font-size: 8px; }
        .generated-note { margin-top: 20px; margin-left: 22px; font-size: 8px; font-style: italic; }
        .page-number {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -18px;
            text-align: center;
            font-size: 9px;
        }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
@php
    $seller = config('store.invoice.seller', []);
    $bank = config('store.invoice.bank', []);
    $qrPayload = (string) config('store.invoice.qr_payload', '');

    $gstType = ($gst_type ?? ($invoice->gst_type ?? ($order->gst_type ?? null))) === 'inter_state'
        ? 'inter_state'
        : 'intra_state';

    $cgst = (float) ($cgst_amount ?? ($invoice->cgst_amount ?? ($order->cgst_amount ?? 0)));
    $sgst = (float) ($sgst_amount ?? ($invoice->sgst_amount ?? ($order->sgst_amount ?? 0)));
    $igst = (float) ($igst_amount ?? ($invoice->igst_amount ?? ($order->igst_amount ?? 0)));
    $taxTotal = (float) ($tax_total ?? ($invoice->tax_total ?? ($order->tax_total ?? ($cgst + $sgst + $igst))));

    $productSubtotal = (float) ($invoice->subtotal ?? 0);
    $deliveryFee = (float) ($invoice->delivery_fee ?? 0);
    $coldChainFee = (float) ($invoice->handling_fee ?? 0);
    $discountTotal = (float) ($invoice->discount_total ?? 0);
    $invoiceCustomerType = strtolower((string) ($invoice->order?->user?->customer_type ?? $order?->user?->customer_type ?? 'b2c'));
    $bandaraCreditRedeemed = $invoiceCustomerType === 'b2b'
        ? 0.0
        : (float) (($invoice->bandara_credit_redeemed_amount ?? 0) ?: ($invoice->bandara_credit_discount_total ?? 0));

    $grossSubtotal = round($productSubtotal + max(0, $deliveryFee) + max(0, $coldChainFee), 2);
    $taxableSubtotal = round(max($grossSubtotal - max(0, $discountTotal), 0), 2);
    $grandTotal = round((float) ($invoice->grand_total ?? ($taxableSubtotal + $taxTotal - $bandaraCreditRedeemed)), 2);
    $calculatedGrandTotal = round($taxableSubtotal + $taxTotal - $bandaraCreditRedeemed, 2);
    $roundOff = round($grandTotal - $calculatedGrandTotal, 2);

    $paidAmount = (float) ($invoice->amount_paid ?? 0);
    $balanceAmount = (float) ($invoice->balance_amount ?? max(0, $grandTotal - $paidAmount));

    $taxSummary = $tax_summary ?? ['gst_type' => $gstType, 'rows' => [], 'totals' => []];
    $taxSummaryRows = collect($taxSummary['rows'] ?? []);
    $taxSummaryTotals = $taxSummary['totals'] ?? [];

    $serviceChargeFallbacks = [];
    try {
        $serviceChargeFallbacks = app(\App\Services\DeliveryTaxSettingsService::class)->current();
    } catch (\Throwable $e) {
        $serviceChargeFallbacks = [];
    }
    $deliverySacCode = trim((string) ($invoice->delivery_sac_code ?? ''))
        ?: (data_get($serviceChargeFallbacks, 'delivery.code') ?: '-');
    $handlingSacCode = trim((string) ($invoice->handling_sac_code ?? ''))
        ?: (data_get($serviceChargeFallbacks, 'handling.code') ?: '-');

    $fmtQty = function ($value): string {
        $value = (float) $value;
        return abs($value - round($value)) < 0.00001
            ? number_format($value, 0)
            : rtrim(rtrim(number_format($value, 3), '0'), '.');
    };

    $fmtWeight = function ($value): string {
        $value = (float) $value;
        return $value > 0 ? rtrim(rtrim(number_format($value, 3), '0'), '.') : '';
    };

    $fmtMoney = fn ($value): string => number_format((float) $value, 2);

    $fmtPercent = function ($value): string {
        $value = (float) $value;
        return abs($value - round($value)) < 0.00001
            ? number_format($value, 0) . '%'
            : rtrim(rtrim(number_format($value, 2), '0'), '.') . '%';
    };

    $rateUnit = function ($item): string {
        $pricingUnit = strtolower(trim((string) ($item->pricing_unit ?? '')));
        $sellUnit = strtolower(trim((string) ($item->sell_unit ?? '')));

        if ($pricingUnit === 'kg') {
            return 'kg';
        }

        if (in_array($sellUnit, ['piece', 'pieces', 'pc', 'pcs'], true)) {
            return 'pc';
        }

        return 'pack';
    };

    $addressLines = function ($address): array {
        if (! $address) {
            return [];
        }

        return array_values(array_filter([
            trim((string) ($address->address_line1 ?? '')),
            trim((string) ($address->address_line2 ?? '')),
            trim(implode(', ', array_filter([
                $address->city ?? null,
                $address->state ?? null,
            ]))),
            trim(implode(' ', array_filter([
                $address->pincode ?? null,
                $address->country ?? null,
            ]))),
        ]));
    };

    $wordsBelowThousand = function (int $number): string {
        $ones = [
            0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
            15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen', 19 => 'nineteen',
        ];
        $tens = [
            2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
            6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety',
        ];

        $parts = [];
        if ($number >= 100) {
            $parts[] = $ones[intdiv($number, 100)] . ' hundred';
            $number %= 100;
        }

        if ($number >= 20) {
            $ten = intdiv($number, 10);
            $unit = $number % 10;
            $parts[] = ($parts ? 'and ' : '') . $tens[$ten] . ($unit ? ' ' . $ones[$unit] : '');
        } elseif ($number > 0) {
            $parts[] = ($parts ? 'and ' : '') . $ones[$number];
        }

        return implode(' ', array_filter($parts));
    };

    $numberToWords = function (int $number) use (&$numberToWords, $wordsBelowThousand): string {
        if ($number === 0) {
            return 'zero';
        }

        $parts = [];
        foreach ([10000000 => 'crore', 100000 => 'lakh', 1000 => 'thousand'] as $value => $label) {
            if ($number >= $value) {
                $parts[] = $numberToWords(intdiv($number, $value)) . ' ' . $label;
                $number %= $value;
            }
        }

        if ($number > 0) {
            $parts[] = $wordsBelowThousand($number);
        }

        return implode(' ', array_filter($parts));
    };

    $grandTotalPaise = (int) round($grandTotal * 100);
    $rupees = intdiv($grandTotalPaise, 100);
    $paise = $grandTotalPaise % 100;
    $amountWords = 'INR ' . ucfirst($numberToWords($rupees));
    if ($paise > 0) {
        $amountWords .= ' and ' . $numberToWords($paise) . ' paise';
    }
    $amountWords .= ' only';

    $invoiceDate = $invoice->invoice_date ? \Illuminate\Support\Carbon::parse($invoice->invoice_date) : now();
    $billingAddress = $billing;
    $shippingAddress = $shipping ?: $billing;
    $billingName = strtoupper((string) ($billingAddress->full_name ?? $customer->business_name ?? $customer->name ?? 'Customer'));
    $shippingName = strtoupper((string) ($shippingAddress->full_name ?? $billingName));
    $customerGstin = $billingAddress->gstin ?? $invoice->bill_to_gstin ?? $order->bill_to_gstin ?? $customer->gst_number ?? $customer->gstin ?? null;
    $customerFssai = $customer->fssai_number ?? null;
    $placeOfSupplyCode = $invoice->place_of_supply_gst_state_code ?? $order->place_of_supply_gst_state_code ?? null;
    $placeOfSupplyState = $placeOfSupplyCode
        ? app(\App\Services\GstPlaceOfSupplyService::class)->stateByGstCode((string) $placeOfSupplyCode)
        : null;
    $isBillToShipTo = (bool) ($invoice->is_bill_to_ship_to ?? $order->is_bill_to_ship_to ?? false);

    $qrPng = null;
    if ($qrPayload !== '' && class_exists(\Milon\Barcode\DNS2D::class)) {
        try {
            $qrPng = (new \Milon\Barcode\DNS2D())->getBarcodePNG($qrPayload, 'QRCODE', 5, 5);
        } catch (\Throwable $e) {
            $qrPng = null;
        }
    }
@endphp

<div class="page-number"></div>

<table class="brand-header">
    <tr>
        <td style="width: 20%;">
            @if(file_exists(public_path('storage/images/logo-bandara-print.png')))
                <img class="logo" src="{{ public_path('storage/images/logo-bandara-print.png') }}" alt="Bandara Logo">
            @endif
        </td>
        <td class="tax-invoice-title" style="width: 60%;">TAX INVOICE</td>
        <td style="width: 20%;"></td>
    </tr>
</table>

<table class="header-table">
    <tr>
        <td style="width: 28%;">
            <div class="heading">INVOICE</div>
            <table class="invoice-meta">
                <tr>
                    <td class="label">Invoice Dt</td>
                    <td>{{ $invoiceDate->format('F j, Y') }}</td>
                </tr>
                <tr>
                    <td class="label">Invoice No</td>
                    <td>{{ $invoice->invoice_number }}</td>
                </tr>
                <tr>
                    <td class="label">Order No</td>
                    <td>{{ $order->order_number ?? '-' }}</td>
                </tr>
                @if($placeOfSupplyCode)
                    <tr>
                        <td class="label">Place of Supply</td>
                        <td>{{ $placeOfSupplyState['name'] ?? '-' }} ({{ $placeOfSupplyCode }})</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Tax Mode</td>
                    <td>{{ $gstType === 'intra_state' ? 'CGST + SGST' : 'IGST' }}</td>
                </tr>
                @if($isBillToShipTo)
                    <tr>
                        <td class="label">Supply</td>
                        <td>Bill-To / Ship-To</td>
                    </tr>
                @endif
                @if($invoice->due_date)
                    <tr>
                        <td class="label">Due Dt</td>
                        <td>{{ $invoice->due_date->format('F j, Y') }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Payment</td>
                    <td>{{ $invoice->payment_method_label }}</td>
                </tr>
            </table>
        </td>

        <td style="width: 36%;">
            <div class="heading">BILLED TO</div>
            <div class="party-name">{{ $billingName }}</div>
            <div class="party-meta">
                GSTIN: {{ $customerGstin ?: '-' }}<br>
                FSSAI No: {{ $customerFssai ?: '-' }}
            </div>
            @forelse($addressLines($billingAddress) as $line)
                <div class="party-address">{{ $line }}</div>
            @empty
                <div class="party-address">Billing address unavailable</div>
            @endforelse
            @if($billingAddress?->phone)
                <div class="party-address">Phone: {{ $billingAddress->phone }}</div>
            @endif
        </td>

        <td style="width: 36%;">
            <div class="heading">SHIPPED TO</div>
            <div class="party-name">{{ $shippingName }}</div>
            @forelse($addressLines($shippingAddress) as $line)
                <div class="party-address">{{ $line }}</div>
            @empty
                <div class="party-address">Same as billing address</div>
            @endforelse
            @if($shippingAddress?->phone)
                <div class="party-address">Phone: {{ $shippingAddress->phone }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="items-table">
    <thead>
        <tr>
            <th class="sr">Sr<br>No</th>
            <th class="desc">Description</th>
            <th class="hsn">HSN/SAC</th>
            <th class="qty">Qty<br>PCS</th>
            <th class="wt">Wt<br>(Kg)</th>
            <th class="rate">Rate</th>
            <th class="amount-col">Amount</th>
        </tr>
    </thead>
    <tbody>
        @php $count = 0; @endphp
        @forelse($invoice->items as $item)
            @php
                $count++;
                $unit = $rateUnit($item);
            @endphp
            <tr>
                <td class="sr">{{ $count }}</td>
                <td class="desc"><span class="line-description">{{ $item->description }}</span></td>
                <td class="hsn">{{ $item->hsn_sac_code ?: '-' }}</td>
                <td class="qty">{{ $fmtQty($item->quantity) }}</td>
                <td class="wt">{{ $fmtWeight($item->item_weight) }}</td>
                <td class="rate money">₹{{ $fmtMoney($item->unit_price) }}<span class="rate-unit">/{{ $unit }}</span></td>
                <td class="amount-col money">₹{{ $fmtMoney($item->subtotal) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No invoice items.</td>
            </tr>
        @endforelse

        @if($deliveryFee > 0)
            @php $count++; @endphp
            <tr>
                <td class="sr">{{ $count }}</td>
                <td class="desc"><span class="service-description">Delivery Fee</span></td>
                <td class="hsn">{{ $deliverySacCode }}</td>
                <td class="qty"></td>
                <td class="wt"></td>
                <td class="rate">Flat</td>
                <td class="amount-col money">₹{{ $fmtMoney($deliveryFee) }}</td>
            </tr>
        @endif

        @if($coldChainFee > 0)
            @php $count++; @endphp
            <tr>
                <td class="sr">{{ $count }}</td>
                <td class="desc"><span class="service-description">Cold Chain Fee</span></td>
                <td class="hsn">{{ $handlingSacCode }}</td>
                <td class="qty"></td>
                <td class="wt"></td>
                <td class="rate">Flat</td>
                <td class="amount-col money">₹{{ $fmtMoney($coldChainFee) }}</td>
            </tr>
        @endif

        @if($discountTotal > 0)
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">Discount</td>
                <td class="amount-col money">- ₹{{ $fmtMoney($discountTotal) }}</td>
            </tr>
        @endif

        <tr>
            <td colspan="5"></td>
            <td class="summary-label">Subtotal</td>
            <td class="amount-col money">₹{{ $fmtMoney($taxableSubtotal) }}</td>
        </tr>

        @if($gstType === 'intra_state')
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">CGST</td>
                <td class="amount-col money">₹{{ $fmtMoney($cgst) }}</td>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">SGST</td>
                <td class="amount-col money">₹{{ $fmtMoney($sgst) }}</td>
            </tr>
        @else
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">IGST</td>
                <td class="amount-col money">₹{{ $fmtMoney($igst) }}</td>
            </tr>
        @endif

        @if($bandaraCreditRedeemed > 0)
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">Bandara Credit</td>
                <td class="amount-col money">- ₹{{ $fmtMoney($bandaraCreditRedeemed) }}</td>
            </tr>
        @endif

        @if(abs($roundOff) >= 0.005)
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">Round Off</td>
                <td class="amount-col money">{{ $roundOff < 0 ? '- ' : '' }}₹{{ $fmtMoney(abs($roundOff)) }}</td>
            </tr>
        @endif

        <tr class="summary-total">
            <td colspan="5"></td>
            <td class="summary-label">Total</td>
            <td class="amount-col money">₹{{ $fmtMoney($grandTotal) }}</td>
        </tr>

        @if($paidAmount > 0 || $balanceAmount + 0.005 < $grandTotal)
            <tr>
                <td colspan="5"></td>
                <td class="summary-label">Paid</td>
                <td class="amount-col money">₹{{ $fmtMoney($paidAmount) }}</td>
            </tr>
            <tr>
                <td colspan="5"></td>
                <td class="summary-label"><strong>Balance Due</strong></td>
                <td class="amount-col money"><strong>₹{{ $fmtMoney($balanceAmount) }}</strong></td>
            </tr>
        @endif
    </tbody>
</table>

<div class="amount-words">{{ $amountWords }}</div>

@if($taxSummaryRows->isNotEmpty())
    <div class="tax-title">HSN/SAC Tax Summary</div>

    @if($gstType === 'intra_state')
        <table class="tax-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 16%;">HSN/SAC</th>
                    <th rowspan="2" style="width: 18%;">Taxable Value</th>
                    <th colspan="2" style="width: 25%;">CGST</th>
                    <th colspan="2" style="width: 25%;">SGST</th>
                    <th rowspan="2" style="width: 16%;">Total Tax</th>
                </tr>
                <tr>
                    <th>Rate</th>
                    <th>Amount</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxSummaryRows as $row)
                    <tr>
                        <td class="code">{{ $row['hsn_sac_code'] }}</td>
                        <td>₹{{ $fmtMoney($row['taxable_value']) }}</td>
                        <td>{{ $fmtPercent($row['cgst_rate']) }}</td>
                        <td>₹{{ $fmtMoney($row['cgst_amount']) }}</td>
                        <td>{{ $fmtPercent($row['sgst_rate']) }}</td>
                        <td>₹{{ $fmtMoney($row['sgst_amount']) }}</td>
                        <td>₹{{ $fmtMoney($row['total_tax']) }}</td>
                    </tr>
                @endforeach
                <tr class="tax-total-row">
                    <td class="code">Total</td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['taxable_value'] ?? 0) }}</td>
                    <td></td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['cgst_amount'] ?? 0) }}</td>
                    <td></td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['sgst_amount'] ?? 0) }}</td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['total_tax'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="tax-table">
            <thead>
                <tr>
                    <th style="width: 22%;">HSN/SAC</th>
                    <th style="width: 25%;">Taxable Value</th>
                    <th style="width: 18%;">IGST Rate</th>
                    <th style="width: 20%;">IGST Amount</th>
                    <th style="width: 15%;">Total Tax</th>
                </tr>
            </thead>
            <tbody>
                @foreach($taxSummaryRows as $row)
                    <tr>
                        <td class="code">{{ $row['hsn_sac_code'] }}</td>
                        <td>₹{{ $fmtMoney($row['taxable_value']) }}</td>
                        <td>{{ $fmtPercent($row['igst_rate']) }}</td>
                        <td>₹{{ $fmtMoney($row['igst_amount']) }}</td>
                        <td>₹{{ $fmtMoney($row['total_tax']) }}</td>
                    </tr>
                @endforeach
                <tr class="tax-total-row">
                    <td class="code">Total</td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['taxable_value'] ?? 0) }}</td>
                    <td></td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['igst_amount'] ?? 0) }}</td>
                    <td>₹{{ $fmtMoney($taxSummaryTotals['total_tax'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
@endif

<table class="header-table lower-section">
    <tr>
        <td style="width: 64%; padding-left: 6px;">
            <div class="bank-title">Bank details</div>
            <table class="bank-table">
                <tr>
                    <td class="bank-label">Account No</td>
                    <td class="bank-colon">:</td>
                    <td class="bank-value">{{ $bank['account_no'] ?? '129663700000319' }}</td>
                </tr>
                <tr>
                    <td class="bank-label">Account name</td>
                    <td class="bank-colon">:</td>
                    <td class="bank-value">{{ $bank['account_name'] ?? 'Bandara LLP' }}</td>
                </tr>
                <tr>
                    <td class="bank-label">IFSC</td>
                    <td class="bank-colon">:</td>
                    <td class="bank-value">{{ $bank['ifsc'] ?? 'YESB0001296' }}</td>
                </tr>
                <tr>
                    <td class="bank-label">Bank</td>
                    <td class="bank-colon">:</td>
                    <td class="bank-value">{{ $bank['bank_name'] ?? 'Yes Bank Ltd.' }}</td>
                </tr>
            </table>
        </td>
        <td class="qr-wrap" style="width: 36%;">
            @if($qrPng)
                <img src="data:image/png;base64,{{ $qrPng }}" alt="Payment QR">
            @endif
        </td>
    </tr>
</table>

<div class="thanks">We appreciate your business.</div>

<div class="signoff">
    Sincerely,<br><br>
    <strong>{{ $seller['signature_name'] ?? 'For Bandara' }}</strong>
</div>

<div class="company-footer">
    FSSAI No: {{ $seller['fssai_no'] ?? '21526079001348' }}<br>
    GSTIN No: {{ $seller['gstin_no'] ?? '27ABEFB3240N1ZE' }}
</div>

<div class="company-address">
    {{ $seller['address'] ?? '303B, Nityanand Complex, 247A, Bund Garden Road, Pune 411001. MH. India' }}
</div>

<div class="generated-note">Computer generated. No signature required.</div>
</body>
</html>
