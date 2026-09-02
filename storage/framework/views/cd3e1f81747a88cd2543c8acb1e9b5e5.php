<?php
    $bandaraFinanceUser = $user ?? auth()->user();
    $bandaraFinanceLandingRoute = \App\Support\FinanceAccess::landingRouteName($bandaraFinanceUser);
?>

<?php if($bandaraFinanceLandingRoute && \Illuminate\Support\Facades\Route::has($bandaraFinanceLandingRoute)): ?>
    <div class="mb-4">
        <p class="mb-1 text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Finance</p>
        <a href="<?php echo e(route($bandaraFinanceLandingRoute)); ?>"
           class="block rounded-md px-2 py-1.5 <?php echo e(request()->routeIs('admin.finance.*') ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50' : 'hover:bg-gray-100 dark:hover:bg-gray-800'); ?>">
            Salary &amp; expenses
        </a>
    </div>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/finance/partials/admin-nav-link.blade.php ENDPATH**/ ?>