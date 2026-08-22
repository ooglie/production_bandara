<?php
    $order = $invoice->order;
    $user  = $order?->user;
?>

<p>Dear <?php echo e($user->name ?? 'Customer'); ?>,</p>

<p>We’ve received your payment for invoice <strong><?php echo e($invoice->invoice_number); ?></strong>
for order <strong>#<?php echo e($order->order_number ?? '—'); ?></strong>.</p>

<p>Amount paid: <strong>₹<?php echo e(number_format($invoice->grand_total, 2)); ?></strong></p>

<p>Your tax invoice is attached again for your records. You can also download it anytime from your account.</p>

<p>Thank you for shopping with Frozen - Bandara by Maytira.</p>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/emails/invoices/paid.blade.php ENDPATH**/ ?>