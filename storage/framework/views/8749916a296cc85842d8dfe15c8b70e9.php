<?php
    $name = $subscriber->name ?? 'there';
?>

<p>Hi <?php echo e($name); ?>,</p>

<?php echo $campaign->content_html; ?>


<hr>

<p style="font-size: 11px; color: #777;">
    You are receiving this email because you subscribed to the Frozen - Bandara newsletter.
    If you no longer wish to receive these emails, you can
    <a href="<?php echo e($unsubscribeUrl); ?>">unsubscribe here</a>.
</p>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/emails/newsletter/campaign.blade.php ENDPATH**/ ?>