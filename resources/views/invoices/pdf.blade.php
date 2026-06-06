<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 20px;
        }
        .clearfix:after { content: ""; display: table; clear: both; }
        h1 { font-size: 18px; margin: 0 0 5px 0; }
        h2 { font-size: 13px; margin: 0 0 4px 0; }
        .header { margin-bottom: 20px; }
        .company, .customer-info { width: 48%; }
        .company { float: left; }
        .customer-info { float: right; text-align: right; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            padding: 6px 4px;
            border-bottom: 1px solid #ddd;
        }
        th { background-color: #f5f5f5; text-align: left; }
        .text-right { text-align: right; }

        .totals {
            margin-top: 15px;
            width: 45%;
            float: right;
        }
        .totals td { padding: 4px 4px; }
        .totals .label { text-align: left; }
        .totals .value { text-align: right; }

        .small { font-size: 10px; color: #555; }
    </style>
</head>
<body>

@php
    // ✅ Use amounts passed from service; fall back safely if not passed
    $gstType = $gst_type ?? ($order->gst_type ?? null);

    $cgst = (float)($cgst_amount ?? ($invoice->cgst_amount ?? ($order->cgst_amount ?? 0)));
    $sgst = (float)($sgst_amount ?? ($invoice->sgst_amount ?? ($order->sgst_amount ?? 0)));
    $igst = (float)($igst_amount ?? ($invoice->igst_amount ?? ($order->igst_amount ?? 0)));

    $taxTotal = (float)($tax_total ?? ($invoice->tax_total ?? ($order->tax_total ?? ($cgst + $sgst + $igst))));

    $unitLabel = function (?string $u) {
        $u = strtolower((string)$u);
        return match ($u) {
            'kg' => 'kg',
            'pack' => 'pack',
            'box' => 'box',
            default => 'pc',
        };
    };

    $paidAmount = (float)($invoice->amount_paid ?? 0);
    $balanceAmount = (float)($invoice->balance_amount ?? max(0, ($invoice->grand_total ?? 0) - $paidAmount));
@endphp

<div class="header clearfix" style="width:100%; margin-bottom:20px;">
    <table style="width:100%; border:0; border-collapse:collapse;">
        <tr>
            <!-- LEFT: Logo + Company -->
            <td style="width:60%; border:0; padding:0; vertical-align:top;">
                <table style="border:0; border-collapse:collapse; width:auto;">
                    <tr>
                        <td style="border:0; padding:0; vertical-align:middle;">
                            <img
                                src="{{ public_path('storage/images/logo-bandara-print.png') }}"
                                alt="Bandara Logo"
                                style="width:64px; height:64px; display:block;"
                            >
                        </td>
                        <td style="border:0; padding:0 0 0 10px; vertical-align:middle;">
                            <div style="font-size:14px; font-weight:700; line-height:1.2;">
                                Bandara by Maytira
                            </div>
                            <div style="font-size:10px; color:#555; margin-top:2px;">
                                <!-- Optional: add address/tagline here -->
                            </div>
                        </td>
                    </tr>
                </table>
            </td>

        </tr>
    </table>
</div>

<div style="margin-bottom:12px;">
    <table style="width:100%; border:0; border-collapse:collapse; font-size:9.5px; line-height:1.15;">
        <tr>
            <!-- Bill To -->
            <td style="width:34%; border:0; padding:0 10px 0 0; vertical-align:top;">
                <div style="font-weight:700; font-size:10px; margin-bottom:2px;">Bill to</div>

                @if($billing)
                    <div style="font-size:10px; font-weight:700;">{{ $billing->full_name }}</div>
                    <div style="font-size:8px;">{{ $billing->address_line1 }}</div>
                    @if($billing->address_line2)
                        <div style="font-size:8px;">{{ $billing->address_line2 }}</div>
                    @endif
                    <div style="font-size:8px;">{{ $billing->city }}, {{ $billing->state }} - {{ $billing->pincode }}</div>
                    <div style="font-size:8px;">{{ $billing->country }}</div>
                    <div style="font-size:8px;">Phone: {{ $billing->phone }}</div>
                    @if($billing->gstin)
                        <div style="font-size:8px;">GSTIN: {{ $billing->gstin }}</div>
                    @endif
                @elseif($customer)
                    <div style="font-weight:700;">{{ $customer->name }}</div>
                    <div style="font-size:8px;">{{ $customer->email }}</div>
                @else
                    <div style="font-size:8px;">Customer information unavailable</div>
                @endif
            </td>

            <!-- Ship To -->
            <td style="width:34%; border:0; padding:0 10px; vertical-align:top;">
                <div style="font-weight:700; font-size:10px; margin-bottom:2px;">Ship to</div>

                @if($shipping)
                    <div style="font-weight:700;">{{ $shipping->full_name }}</div>
                    <div style="font-size:8px;">{{ $shipping->address_line1 }}</div>
                    @if($shipping->address_line2)
                        <div style="font-size:8px;">{{ $shipping->address_line2 }}</div>
                    @endif
                    <div style="font-size:8px;">{{ $shipping->city }}, {{ $shipping->state }} - {{ $shipping->pincode }}</div>
                    <div style="font-size:8px;">{{ $shipping->country }}</div>
                    <div style="font-size:8px;">Phone: {{ $shipping->phone }}</div>
                    @if($shipping->gstin)
                        <div style="font-size:8px;">GSTIN: {{ $shipping->gstin }}</div>
                    @endif
                @else
                    <div style="font-size:8px;">Same as billing address</div>
                @endif
            </td>

            <!-- Invoice Info -->
            <td style="width:32%; border:0; padding:0 0 0 10px; vertical-align:top; text-align:right;">
                <div style="margin-bottom:3px;"><span style="font-weight:700;">Invoice #</span>
                     {{ $invoice->invoice_number }}
                </div>
                <div><span style="font-weight:700;">Order:</span> {{ $order->order_number ?? '—' }}</div>
                <div><span style="font-weight:700;">Date:</span> {{ optional($invoice->invoice_date)->format('d M Y') ?? now()->format('d M Y') }}</div>
                @if($invoice->due_date)
                    <div><span style="font-weight:700;">Due:</span> {{ $invoice->due_date->format('d M Y') }}</div>
                @endif
                <div><span style="font-weight:700;">Payment:</span> {{ $invoice->payment_method_label }}</div>
                <div style="color:#555;"><span style="font-weight:700;">Status:</span> {{ $invoice->payment_status_label }}</div>
            </td>
        </tr>
    </table>
</div>
{{-- 
<h2>Items</h2> --}}
<table>
    <thead>
        <tr>
            <th style="width: 5%; font-size:10px;">#</th>
            <th style="width: 45%; font-size:10px;">Description</th>
            <th style="width: 10%; font-size:10px;" class="text-right">Qty</th>
            <th style="width: 10%; font-size:10px;" class="text-right">Wt</th>
            <th style="width: 15%; font-size:10px;" class="text-right">Price excl GST</th>
            <th style="width: 15%; font-size:10px;" class="text-right">Total excl GST</th>
        </tr>
    </thead>
    <tbody>
        @php $count = 0; @endphp
        @forelse($invoice->items as $item)
            @php
                $sellUnit = strtolower((string)($item->sell_unit ?? 'piece'));
                $count++;
            @endphp
            <tr>
                <td>{{ $count }}</td>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ number_format((float)$item->quantity, 2) }}</td>
                <td class="text-right">{{ number_format((float)$item->item_weight, 2) }}</td>
                <td class="text-right">₹{{ number_format((float)$item->unit_price, 2) }}<span style="font-size:6px;">/{{ $unitLabel($sellUnit) }}</span></td>
                <td class="text-right">₹{{ number_format((float)$item->subtotal, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No invoice items.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table class="totals">
    <tbody>
        <tr>
            <td class="label">Subtotal excl GST</td>
            <td class="value">₹{{ number_format((float)$invoice->subtotal, 2) }}</td>
        </tr>
        @if(number_format((float)($invoice->discount_total ?? 0), 2) > 0)
            <tr>
                <td class="label">Discount</td>
                <td class="value">- ₹{{ number_format((float)($invoice->discount_total ?? 0), 2) }}</td>
            </tr>                               
        @endif

        @if((float)($invoice->delivery_fee ?? 0) > 0)
            <tr>
                <td class="label">Delivery fee excl GST</td>
                <td class="value">₹{{ number_format((float)$invoice->delivery_fee, 2) }}</td>
            </tr>
        @endif
        @if((float)($invoice->handling_fee ?? 0) > 0)
            <tr>
                <td class="label">Cold-chain handling excl GST</td>
                <td class="value">₹{{ number_format((float)$invoice->handling_fee, 2) }}</td>
            </tr>
        @endif

        {{-- ✅ GST display logic --}}
        @if(($gstType ?? '') === 'intra_state')
            <tr>
                <td class="label">CGST</td>
                <td class="value">₹{{ number_format($cgst, 2) }}</td>
            </tr>
            <tr>
                <td class="label">SGST</td>
                <td class="value">₹{{ number_format($sgst, 2) }}</td>
            </tr>
        @else
            <tr>
                <td class="label">IGST</td>
                <td class="value">₹{{ number_format($igst, 2) }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">GST total</td>
            <td class="value">₹{{ number_format($taxTotal, 2) }}</td>
        </tr>

        @if((float)($invoice->bandara_credit_redeemed_amount ?? 0) > 0)
            <tr>
                <td class="label">Bandara Credit redeemed ({{ number_format((int)($invoice->bandara_credit_redeemed_points ?? 0)) }} pts)</td>
                <td class="value">- ₹{{ number_format((float)$invoice->bandara_credit_redeemed_amount, 2) }}</td>
            </tr>
        @endif

        <tr>
            <td class="label" style="font-size:10px;"><strong>Grand total incl GST</strong></td>
            <td class="value" style="font-size:10px;"><strong>₹{{ number_format((float)$invoice->grand_total, 2) }}</strong></td>
        </tr>
        <tr>
            <td class="label">Paid</td>
            <td class="value">₹{{ number_format($paidAmount, 2) }}</td>
        </tr>
        <tr>
            <td class="label"><strong>Balance due</strong></td>
            <td class="value"><strong>₹{{ number_format($balanceAmount, 2) }}</strong></td>
        </tr>
    </tbody>
</table>

<div style="clear: both; margin-top: 40px;" class="small">
    <div style="font-size:8px;">GST type:
        @if($gstType)
            {{ strtoupper(str_replace('_', ' ', $gstType)) }}
        @else
            Not set
        @endif
    </div>
    <div style="font-size:8px;">Order placed: {{ optional($order?->placed_at)->format('d M Y H:i') ?? '—' }}</div>
    <div style="font-size:8px;">Thank you for shopping with Bandara by Maytira.</div>
</div>

</body>
</html>