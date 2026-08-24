<?php
    $businessApplicationHref = auth()->check()
        ? route('account.business-application.show')
        : route('business-account.index');
?>
<a href="<?php echo e($businessApplicationHref); ?>" class="inline-flex items-center rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
    Business account
</a>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/b2b-application/customer-nav-link.blade.php ENDPATH**/ ?>