<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Print Orders</title>

    <style>
        /* Optional: slightly tighter margins to fit more per page */
        @page { margin: 8mm; }

        body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; margin: 16px; color: #111; }
        .toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:14px; }
        .btn { border:1px solid #111; padding:8px 12px; border-radius:999px; background:#111; color:#fff; font-size:12px; cursor:pointer; }
        .btn.secondary { background:#fff; color:#111; }
        .hint { font-size:12px; color:#444; }

        /* ✅ Continuous printing: no forced page breaks */
        .sheet {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;

            /* Try to keep each order together if it fits on a page */
            break-inside: avoid;
            page-break-inside: avoid;
        }

        /* Separator line that prints between orders (no new page) */
        .separator {
            border-top: 1px dashed #cfcfcf;
            margin: 12px 0;
        }

        .row { display:flex; justify-content:space-between; gap:12px; }
        .title { font-size:16px; font-weight:800; line-height:1.2; }
        .meta { font-size:12px; color:#444; margin-top:2px; }
        .badge { font-size:11px; font-weight:800; border-radius:999px; padding:6px 10px; border:1px solid #ccc; }
        .paid { border-color:#1b7f2a; background:#e9fbe9; color:#1b7f2a; }
        .pending { border-color:#b88b00; background:#fff7d6; color:#7a5b00; }
        .failed { border-color:#b00020; background:#ffe5ea; color:#8a0019; }

        hr { border:none; border-top:1px solid #eee; margin:10px 0; }
        .addr { font-size:12px; line-height:1.35; }
        .addr .name { font-weight:800; }
        .muted { color:#666; font-size:11px; }

        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { text-align:left; padding:8px 6px; font-size:12px; vertical-align:top; }
        thead th { border-bottom:1px solid #ddd; color:#444; font-weight:700; }
        tbody tr + tr td { border-top:1px solid #f0f0f0; }
        .right { text-align:right; }

        .totalBox { margin-top:10px; display:flex; justify-content:flex-end; }
        .totalInner { min-width:260px; border-top:1px solid #ddd; padding-top:10px; }
        .totalRow { display:flex; justify-content:space-between; font-size:12px; padding:3px 0; }
        .grand { font-weight:900; font-size:13px; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }

            /* In print, remove rounded border to look cleaner and save ink */
            .sheet { border: none; border-radius: 0; padding: 0; margin: 0; }
            .separator { border-top: 1px dashed #bbb; margin: 10px 0; }

            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<?php
    use Illuminate\Support\Facades\Route;

    $orderIds = $orders->pluck('id')->values();
    $markPrintedUrl = Route::has('admin.orders.markPrinted')
        ? route('admin.orders.markPrinted')
        : null;
?>

<div class="toolbar no-print">
    <div class="hint">
        <strong><?php echo e($orders->count()); ?></strong> order(s) ready to print.
        <?php if(($mode ?? null) === 'new'): ?>
            <span class="muted">(Unprinted orders)</span>
        <?php endif; ?>
        <div id="print-msg" class="muted" style="margin-top:4px;"></div>
    </div>

    <div style="display:flex; gap:10px; align-items:center;">
        <button type="button" class="btn secondary" onclick="window.close()">Close</button>
        <button type="button" class="btn" onclick="doPrintOrders()">Print</button>
    </div>
</div>

<?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php
        $dt = $order->placed_at ?? $order->created_at;
        $ship = $order->addresses?->firstWhere('type', 'shipping')
            ?? $order->addresses?->firstWhere('type', 'billing')
            ?? $order->addresses?->first();

        $status = (string)($order->payment_status ?? 'pending');

        $badgeClass = 'pending';
        if ($status === 'paid') $badgeClass = 'paid';
        if ($status === 'failed') $badgeClass = 'failed';
    ?>

    <section class="sheet">
        <div class="row">
            <div>
                <div class="title">
                    Order #<?php echo e($order->order_number ?? $order->id); ?>

                    <span class="muted">(ID: <?php echo e($order->id); ?>)</span>
                    
                </div>
                
                
            </div>
            <div class="meta">
                    <?php echo e($dt ? $dt->format('d M Y, h:i A') : ''); ?>

                    <span class="badge <?php echo e($badgeClass); ?>">
                        <?php echo e(strtoupper($status)); ?>

                    </span>
                </div>

        </div>

        <hr>

        <div class="addr">
            <div class="row">
            <div class="name">
                <?php echo e($ship->full_name ?? $order->user?->name ?? 'Customer'); ?> :: 
                Phone: <?php echo e($ship->phone ?? $order->user?->phone ?? '—'); ?>

            </div>

            <div>
                
            </div>

            <div class="muted" style="margin-top:4px;">
                <?php if($ship): ?>
                    <?php echo e($ship->address_line1); ?>

                    <?php if($ship->address_line2): ?>, <?php echo e($ship->address_line2); ?> <?php endif; ?>,
                    <?php echo e($ship->city); ?>, <?php echo e($ship->state); ?> <?php echo e($ship->pincode); ?>

                    <?php if($ship->country): ?>, <?php echo e($ship->country); ?> <?php endif; ?>
                <?php else: ?>
                    <span class="muted">No address on file</span>
                <?php endif; ?>
                
            </div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Products ordered</th>
                <th class="right">Qty</th>
                <th class="right">Line total</th>
            </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $order->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $name = $it->product_name ?? $it->product?->name ?? 'Item';
                    $qty  = (float)($it->quantity ?? 0);
                    $line = (float)($it->total ?? $it->subtotal ?? 0);
                ?>
                <tr>
                    <td>
                        <div style="font-weight:300;"><?php echo e($name); ?></div>
                    </td>
                    <td class="right">
                        <?php echo e(rtrim(rtrim(number_format($qty, 2), '0'), '.')); ?>

                    </td>
                    <td class="right">
                        ₹<?php echo e(number_format($line, 2)); ?>

                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <div class="totalBox">
            <div class="totalInner">
                <div class="totalRow">
                    <span>Subtotal</span>
                    <span>₹<?php echo e(number_format((float)($order->subtotal ?? 0), 2)); ?></span>
                </div>

                <?php if((float)($order->discount_total ?? 0) > 0): ?>
                    <div class="totalRow">
                        <span>Discount</span>
                        <span>-₹<?php echo e(number_format((float)($order->discount_total ?? 0), 2)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if((float)($order->tax_total ?? 0) > 0): ?>
                    <div class="totalRow">
                        <span>Tax</span>
                        <span>₹<?php echo e(number_format((float)($order->tax_total ?? 0), 2)); ?></span>
                    </div>
                <?php endif; ?>

                <?php if((float)($order->shipping_total ?? 0) > 0): ?>
                    <div class="totalRow">
                        <span>Shipping</span>
                        <span>₹<?php echo e(number_format((float)($order->shipping_total ?? 0), 2)); ?></span>
                    </div>
                <?php endif; ?>

                <div class="totalRow grand">
                    <span>Total order value</span>
                    <span>₹<?php echo e(number_format((float)($order->grand_total ?? 0), 2)); ?></span>
                </div>
            </div>
        </div>
    </section>

    <?php if(!$loop->last): ?>
        <div class="separator"></div>
    <?php endif; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <p>No orders to print.</p>
<?php endif; ?>

<script>
    function doPrintOrders() {
        const orderIds = <?php echo json_encode($orderIds, 15, 512) ?>;
        const markUrl  = <?php echo json_encode($markPrintedUrl, 15, 512) ?>;
        const csrf     = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const msgEl    = document.getElementById('print-msg');

        // Mark printed only after clicking Print (fire-and-forget)
        if (markUrl && orderIds.length) {
            if (msgEl) msgEl.textContent = 'Marking as printed…';

            fetch(markUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ order_ids: orderIds }),
            }).then(() => {
                if (msgEl) msgEl.textContent = 'Marked as printed. Opening print dialog…';
            }).catch(() => {
                if (msgEl) msgEl.textContent = 'Print opened. (Could not mark printed — check route/CSRF.)';
            });
        }

        window.focus();
        window.print();
    }
</script>

</body>
</html>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/orders/print-bulk.blade.php ENDPATH**/ ?>