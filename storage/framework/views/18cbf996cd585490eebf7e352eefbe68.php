<?php if(config('features.newsletter', true)): ?>
    <div class="max-w-md">

        <form method="POST" action="<?php echo e(route('newsletter.subscribe')); ?>" class="flex flex-col sm:flex-row gap-2 text-xs">
            <?php echo csrf_field(); ?>
            
            <input
                type="email"
                name="email"
                value="<?php echo e(old('email', auth()->user()->email ?? '')); ?>"
                placeholder="Your email"
                required
                class="flex-1 rounded-sm border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            

            <input type="hidden" name="source" value="footer">

            <button
                type="submit"
                class="sm:w-auto rounded-sm border border-gray-500 dark:border-gray-400 bg-gray-500 text-white dark:bg-gray-600 dark:text-gray-100 px-4 py-1.5 font-medium hover:bg-gray-800 dark:hover:bg-gray-800"
            >
                Subscribe
            </button>
        </form>

        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <p class="mt-1 text-[10px] text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
<?php endif; ?>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/newsletter_form.blade.php ENDPATH**/ ?>