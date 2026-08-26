<?php
    $currentCustomer = auth()->user();
    $businessHref = route('business-account.index');
    $businessLabel = 'For Business';

    if ($currentCustomer) {
        $isBusinessCustomer = \App\Support\B2BApplicationAccess::isB2B($currentCustomer);
        $businessApplication = \App\Models\B2BApplication::query()
            ->where('user_id', $currentCustomer->getKey())
            ->first();

        if ($isBusinessCustomer) {
            $businessHref = route('account.business-application.show');
            $businessLabel = 'Business Account';
        } elseif (! $businessApplication) {
            $businessHref = route('account.business-application.step-one');
            $businessLabel = 'Apply for Business Account';
        } elseif ($businessApplication->status === \App\Enums\B2BApplicationStatus::Draft) {
            $businessHref = route('business-account.continue');
            $businessLabel = 'Continue Business Application';
        } elseif ($businessApplication->status === \App\Enums\B2BApplicationStatus::MoreInformationRequired) {
            $businessHref = route('business-account.continue');
            $businessLabel = 'Update Business Application';
        } else {
            $businessHref = route('account.business-application.show');
            $businessLabel = 'Business Application Status';
        }
    }
?>
<a href="<?php echo e($businessHref); ?>" class="<?php echo e(config('b2b_application_corrective.ui.nav_link', '')); ?>">
    <?php echo e($businessLabel); ?>

</a>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/b2b-application/customer-nav-link.blade.php ENDPATH**/ ?>