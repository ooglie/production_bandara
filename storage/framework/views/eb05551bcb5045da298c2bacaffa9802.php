<?php
    $order = $invoice->order;
    $user  = $order?->user;
?>

<p>Dear <?php echo e($user->name ?? 'Customer'); ?>,</p>

<p>Thank you for your order <strong>#<?php echo e($order->order_number ?? '—'); ?></strong>.</p>

<p>Your tax invoice <strong><?php echo e($invoice->invoice_number); ?></strong> is attached to this email.</p>

<p>Total amount: <strong>₹<?php echo e(number_format($invoice->grand_total, 2)); ?></strong></p>

<p>You can also view this invoice anytime in your account under <strong>Invoices</strong>.</p>

<p>Best regards,<br>
Frozen - Bandara by Maytira</p>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/emails/invoices/created.blade.php ENDPATH**/ ?>