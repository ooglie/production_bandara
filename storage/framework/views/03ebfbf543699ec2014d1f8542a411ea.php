<?php
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $variant = $announcement->type ?? 'info';

    $themes = [
        'special' => [
            'wrap'   => 'border-amber-200/70 bg-gradient-to-r from-amber-50 via-orange-50 to-white dark:border-amber-900/60 dark:from-amber-950/60 dark:via-orange-950/40 dark:to-gray-900',
            'badge'  => 'bg-amber-600/10 text-amber-700 dark:text-amber-300',
            'title'  => 'text-gray-900 dark:text-gray-50',
            'text'   => 'text-gray-700 dark:text-gray-300',
            'button' => 'bg-gray-900 text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white',
            'icon'   => '✨',
        ],
        'festive' => [
            'wrap'   => 'border-fuchsia-200/70 bg-gradient-to-r from-rose-50 via-fuchsia-50 to-white dark:border-fuchsia-900/50 dark:from-rose-950/50 dark:via-fuchsia-950/40 dark:to-gray-900',
            'badge'  => 'bg-fuchsia-600/10 text-fuchsia-700 dark:text-fuchsia-300',
            'title'  => 'text-gray-900 dark:text-gray-50',
            'text'   => 'text-gray-700 dark:text-gray-300',
            'button' => 'bg-gray-900 text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white',
            'icon'   => '🎉',
        ],
        'info' => [
            'wrap'   => 'border-sky-200/70 bg-gradient-to-r from-sky-50 via-cyan-50 to-white dark:border-sky-900/50 dark:from-sky-950/50 dark:via-cyan-950/30 dark:to-gray-900',
            'badge'  => 'bg-sky-600/10 text-sky-700 dark:text-sky-300',
            'title'  => 'text-gray-900 dark:text-gray-50',
            'text'   => 'text-gray-700 dark:text-gray-300',
            'button' => 'bg-gray-900 text-white hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white',
            'icon'   => '📢',
        ],
    ];

    $theme = $themes[$variant] ?? $themes['info'];
    $dismissible = (bool) ($announcement->is_dismissible ?? true);
    $dismissKey = 'home-announcement-dismissed-' . $announcement->id . '-' . optional($announcement->updated_at)->timestamp;

    $backgroundImagePath = $announcement->background_image_path ?? null;
    $backgroundImageUrl = null;

    if (!empty($backgroundImagePath)) {
        if (Str::startsWith($backgroundImagePath, ['http://', 'https://', '/storage/', '/'])) {
            $backgroundImageUrl = $backgroundImagePath;
        } else {
            $backgroundImageUrl = Storage::disk('public')->url($backgroundImagePath);
        }
    }

    $hasBackgroundImage = filled($backgroundImageUrl);
?>

<section
    id="home-announcement-<?php echo e($announcement->id); ?>"
    data-dismiss-key="<?php echo e($dismissKey); ?>"
    class="relative overflow-hidden rounded-lg border <?php echo e($theme['wrap']); ?>"
>
    <div class="absolute -right-10 -top-10 h-28 w-28 rounded-lg bg-white/40 blur-3xl dark:bg-white/5"></div>
    <div class="absolute left-16 -bottom-12 h-24 w-24 rounded-lg bg-white/30 blur-3xl dark:bg-white/5"></div>

    <?php if($hasBackgroundImage): ?>
        <div class="absolute inset-y-0 right-0 hidden md:block w-[38%] lg:w-[34%] pointer-events-none">
            <img
                src="<?php echo e($backgroundImageUrl); ?>"
                alt=""
                class="h-full w-full object-cover opacity-95"
                style="
                    -webkit-mask-image: linear-gradient(to left, rgba(0,0,0,1) 42%, rgba(0,0,0,0.72) 62%, rgba(0,0,0,0) 100%);
                    mask-image: linear-gradient(to left, rgba(0,0,0,1) 42%, rgba(0,0,0,0.72) 62%, rgba(0,0,0,0) 100%);
                "
            >
            <div class="absolute inset-0 bg-gradient-to-l from-transparent via-transparent to-white/18 dark:to-gray-900/10"></div>
        </div>
    <?php endif; ?>

    <div class="relative z-10 px-4 py-4 sm:px-6 sm:py-5 flex items-start gap-4 <?php echo e($hasBackgroundImage ? 'md:pr-[28%] lg:pr-[24%]' : ''); ?>">
        <div class="hidden sm:flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white/70 text-xl shadow-sm dark:bg-gray-950/40">
            <?php echo e($announcement->icon ?: $theme['icon']); ?>

        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-sm px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide <?php echo e($theme['badge']); ?>">
                    <?php echo e($announcement->label ?: ucfirst($variant)); ?>

                </span>

                <?php if(!empty($announcement->ends_at)): ?>
                    <span class="text-[11px] <?php echo e($theme['text']); ?>">
                        Valid till <?php echo e(\Illuminate\Support\Carbon::parse($announcement->ends_at)->format('d M Y')); ?>

                    </span>
                <?php endif; ?>
            </div>

            <h2 class="mt-2 text-base sm:text-lg font-semibold <?php echo e($theme['title']); ?>">
                <?php echo e($announcement->title); ?>

            </h2>

            <?php if(!empty($announcement->message)): ?>
                <p class="mt-1 text-sm <?php echo e($theme['text']); ?> max-w-3xl">
                    <?php echo e($announcement->message); ?>

                </p>
            <?php endif; ?>

            <?php if(
                (!empty($announcement->cta_text) && !empty($announcement->cta_url)) ||
                (!empty($announcement->secondary_text) && !empty($announcement->secondary_url))
            ): ?>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <?php if(!empty($announcement->cta_text) && !empty($announcement->cta_url)): ?>
                        <a
                            href="<?php echo e($announcement->cta_url); ?>"
                            class="inline-flex items-center justify-center rounded-sm px-4 py-2 text-xs font-medium <?php echo e($theme['button']); ?>"
                        >
                            <?php echo e($announcement->cta_text); ?>

                        </a>
                    <?php endif; ?>

                    <?php if(!empty($announcement->secondary_text) && !empty($announcement->secondary_url)): ?>
                        <a
                            href="<?php echo e($announcement->secondary_url); ?>"
                            class="text-xs font-medium underline underline-offset-4 <?php echo e($theme['text']); ?>"
                        >
                            <?php echo e($announcement->secondary_text); ?>

                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if($dismissible): ?>
            <button
                type="button"
                aria-label="Dismiss announcement"
                class="shrink-0 rounded-lg p-2 text-gray-500 hover:bg-white/60 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800/60 dark:hover:text-gray-100"
                onclick="(function(btn){
                    const card = btn.closest('section[data-dismiss-key]');
                    const key = card.dataset.dismissKey;
                    localStorage.setItem(key, '1');
                    card.remove();
                })(this)"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
</section>

<?php if (! $__env->hasRenderedOnce('8f0820b1-ff0c-4b34-a6d0-03e1d8392793')): $__env->markAsRenderedOnce('8f0820b1-ff0c-4b34-a6d0-03e1d8392793'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('section[data-dismiss-key]').forEach(function (el) {
                const key = el.dataset.dismissKey;
                if (localStorage.getItem(key) === '1') {
                    el.remove();
                }
            });
        });
    </script>
<?php endif; ?><?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/home_cards/announcement_banner.blade.php ENDPATH**/ ?>