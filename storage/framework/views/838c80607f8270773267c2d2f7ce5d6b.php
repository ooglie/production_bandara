<?php
    $policy = config('bandara_content.policies');
?>

<div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
    <span>Version <?php echo e($policy['version']); ?></span>
    <span>Effective <?php echo e($policy['effective_date']); ?></span>
    <span>Last updated <?php echo e($policy['last_updated']); ?></span>
</div>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/pages/partials/policy-meta.blade.php ENDPATH**/ ?>