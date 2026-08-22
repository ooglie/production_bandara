<?php $__env->startSection('title', 'Edit B2B Customer'); ?>

<?php $__env->startSection('content'); ?>
<?php
    /** @var \App\Models\User $user */

    $has = fn($r) => \Illuminate\Support\Facades\Route::has($r);

    $updateUrl =
        $has('admin.b2b.customers.update') ? route('admin.b2b.customers.update', $user)
        : ($has('admin.users.update') ? route('admin.users.update', $user) : null);

    $backUrl =
        $has('admin.b2b.customers.index') ? route('admin.b2b.customers.index')
        : ($has('admin.users.index') ? route('admin.users.index', ['customer_type' => 'b2b']) : url()->previous());

    $destroyUrl =
        $has('admin.b2b.customers.destroy') ? route('admin.b2b.customers.destroy', $user)
        : ($has('admin.users.destroy') ? route('admin.users.destroy', $user) : null);

    $canDelete = $destroyUrl && ($user->id !== auth()->id());
?>

<div class="max-w-2xl mx-auto px-4 py-6 space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-50">Edit B2B Customer</h1>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
                Update B2B customer account details.
            </p>
        </div>
        <a href="<?php echo e($backUrl); ?>"
           class="rounded-full border border-gray-300 dark:border-gray-700 px-3 py-1.5 text-xs hover:bg-gray-50 dark:hover:bg-gray-800">
            Back
        </a>
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <?php if($has('admin.b2b.moq.index')): ?>
            <a href="<?php echo e(route('admin.b2b.moq.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                MOQ
            </a>
        <?php endif; ?>

        <?php if($has('admin.b2b.prices.index')): ?>
            <a href="<?php echo e(route('admin.b2b.prices.index', $user)); ?>"
               class="text-[11px] px-3 py-1 rounded-full border border-gray-300 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800">
                Prices
            </a>
        <?php endif; ?>
    </div>

    <?php if(!$updateUrl): ?>
        <div class="rounded border border-yellow-300 bg-yellow-50 px-3 py-2 text-[11px] text-yellow-800">
            Update route not found. Expected <code>admin.b2b.customers.update</code> or <code>admin.users.update</code>.
        </div>
    <?php endif; ?>

    <?php echo $__env->make('admin.b2b.customers._form', [
        'action' => $updateUrl ?? '#',
        'mode' => 'edit',
        'user' => $user,
        'terms' => $user->b2bTerms,
        'payLaterSummary' => $payLaterSummary ?? [],
        'backUrl' => $backUrl,
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    
    <?php if($canDelete): ?>
        <div class="pt-1 flex justify-end">
            <form method="POST" action="<?php echo e($destroyUrl); ?>"
                  onsubmit="return confirm('Delete this customer?');">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit" class="text-[11px] text-red-600 dark:text-red-400 underline">
                    Delete
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.company', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/b2b/customers/edit.blade.php ENDPATH**/ ?>