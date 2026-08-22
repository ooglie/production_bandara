<?php
    $name = $subscriber->name ?? 'there';
?>

<p>Hi <?php echo e($name); ?>,</p>

<p>Thank you for your interest in the Frozen - Bandara newsletter.</p>

<p>
    Please confirm your subscription by clicking the link below:
</p>

<p>
    <a href="<?php echo e($confirmUrl); ?>">
        Confirm my subscription
    </a>
</p>

<p>
    If you did not request this, you can ignore this email,
    or unsubscribe here:
    <a href="<?php echo e($unsubscribeUrl); ?>">Unsubscribe</a>
</p>

<p>Warm regards,<br>
Frozen - Bandara by Maytira</p>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/emails/newsletter/confirm.blade.php ENDPATH**/ ?>