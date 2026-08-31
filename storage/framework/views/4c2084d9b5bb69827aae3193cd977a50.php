<?php
    $financeUser = auth()->user();
    $financeLinks = [];

    if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::SUMMARY) && \Illuminate\Support\Facades\Route::has('admin.finance.index')) {
        $financeLinks[] = ['label' => 'Operating summary', 'route' => 'admin.finance.index', 'pattern' => 'admin.finance.index'];
    }

    if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::EXPENSES_VIEW)) {
        if (\Illuminate\Support\Facades\Route::has('admin.finance.expenses.index')) {
            $financeLinks[] = ['label' => 'Business expenses', 'route' => 'admin.finance.expenses.index', 'pattern' => 'admin.finance.expenses.*'];
        }
        if (\Illuminate\Support\Facades\Route::has('admin.finance.recurring-expenses.index')) {
            $financeLinks[] = ['label' => 'Recurring expenses', 'route' => 'admin.finance.recurring-expenses.index', 'pattern' => 'admin.finance.recurring-expenses.*'];
        }
    }

    if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::EXPENSE_SETTINGS_MANAGE)
        && \Illuminate\Support\Facades\Route::has('admin.finance.expense-categories.index')) {
        $financeLinks[] = ['label' => 'Expense categories', 'route' => 'admin.finance.expense-categories.index', 'pattern' => 'admin.finance.expense-categories.*'];
    }

    if (\App\Support\FinanceAccess::allows($financeUser, \App\Support\FinanceAccess::SALARY_VIEW)) {
        if (\Illuminate\Support\Facades\Route::has('admin.finance.salary-entries.index')) {
            $financeLinks[] = ['label' => 'Monthly salaries', 'route' => 'admin.finance.salary-entries.index', 'pattern' => 'admin.finance.salary-entries.*'];
        }
        if (\Illuminate\Support\Facades\Route::has('admin.finance.salary-profiles.index')) {
            $financeLinks[] = ['label' => 'Salary profiles', 'route' => 'admin.finance.salary-profiles.index', 'pattern' => 'admin.finance.salary-profiles.*'];
        }
    }
?>

<?php if($financeLinks !== []): ?>
    <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-3 text-xs dark:border-gray-800" aria-label="Finance navigation">
        <?php $__currentLoopData = $financeLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $link): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($financeLinkActive = request()->routeIs($link['pattern'])); ?>
            <a href="<?php echo e(route($link['route'])); ?>"
               class="rounded border px-3 py-1.5 <?php echo e($financeLinkActive ? 'border-gray-900 bg-gray-900 text-white dark:border-gray-100 dark:bg-gray-100 dark:text-gray-900' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-900'); ?>">
                <?php echo e($link['label']); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/finance/partials/nav.blade.php ENDPATH**/ ?>