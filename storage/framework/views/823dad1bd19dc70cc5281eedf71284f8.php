<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['context' => 'account']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['context' => 'account']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $copy = match ($context) {
        'checkout-b2b' => 'By confirming this order, you agree to Bandara’s Terms & Policies, including the B2B commercial terms.',
        'checkout-b2c' => 'By placing this order, you agree to Bandara’s Terms & Policies, including the delivery, cancellation and refund conditions.',
        default => 'By continuing, you agree to the Terms & Policies and acknowledge the Privacy Policy.',
    };
?>

<p <?php echo e($attributes->class(['text-xs font-light leading-5 text-slate-500 dark:text-slate-400'])); ?>>
    <?php echo e($copy); ?>

    <a href="<?php echo e(route('content.terms')); ?>" class="underline underline-offset-2 hover:text-slate-900 dark:hover:text-white">Terms & Policies</a>
    <span aria-hidden="true">·</span>
    <a href="<?php echo e(route('content.privacy')); ?>" class="underline underline-offset-2 hover:text-slate-900 dark:hover:text-white">Privacy Policy</a>
</p>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/components/content/legal-consent.blade.php ENDPATH**/ ?>