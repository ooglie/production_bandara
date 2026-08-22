<?php echo csrf_field(); ?>

<div class="space-y-3 text-xs">
    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Name (internal)
        </label>
        <input
            type="text"
            name="name"
            value="<?php echo e(old('name', $campaign->name ?? '')); ?>"
            required
            class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Subject (shown in email)
        </label>
        <input
            type="text"
            name="subject"
            value="<?php echo e(old('subject', $campaign->subject ?? '')); ?>"
            required
            class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        >
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            HTML content
        </label>
        <textarea
            id="content_html"
            name="content_html"
            rows="10"
            class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        ><?php echo e(old('content_html', $campaign->content_html ?? '')); ?></textarea>
        <p class="mt-1 text-[10px] text-gray-400">
            This is the main email body. A WYSIWYG editor is applied here.
        </p>
    </div>

    <div>
        <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
            Plain text content (optional)
        </label>
        <textarea
            name="content_text"
            rows="4"
            class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
        ><?php echo e(old('content_text', $campaign->content_text ?? '')); ?></textarea>
        <p class="mt-1 text-[10px] text-gray-400">
            If left empty, we will generate a plain text version automatically by stripping HTML tags.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Status
            </label>
            <select
                name="status"
                class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($status); ?>"
                        <?php if(old('status', $campaign->status ?? 'draft') === $status): echo 'selected'; endif; ?>>
                        <?php echo e(ucfirst($status)); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-medium text-gray-700 dark:text-gray-300">
                Scheduled for (optional)
            </label>
            <input
                type="datetime-local"
                name="scheduled_for"
                value="<?php echo e(old('scheduled_for', optional($campaign->scheduled_for ?? null)->format('Y-m-d\TH:i'))); ?>"
                class="mt-1 w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-950 px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 dark:focus:ring-gray-500"
            >
            <p class="mt-1 text-[10px] text-gray-400">
                Scheduling is not yet automated. For now, use "Send now" to actually send.
            </p>
        </div>
    </div>
</div>

<div class="pt-3 flex items-center justify-between gap-3">
    <button
        type="submit"
        class="inline-flex items-center justify-center rounded-full border border-gray-900 dark:border-gray-100 bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 px-4 py-1.5 text-[11px] font-medium hover:bg-gray-800 dark:hover:bg-gray-200"
    >
        Save campaign
    </button>

    <?php if(isset($campaign)): ?>
        <?php if(isset($campaign->id) && in_array($campaign->status, ['draft','scheduled'], true)): ?>
            <form method="POST"
                  action="<?php echo e(route('admin.newsletter-campaigns.send', $campaign)); ?>"
                  onsubmit="return confirm('Send this campaign to all active subscribers now?');">
                <?php echo csrf_field(); ?>
                <button
                    type="submit"
                    class="text-[11px] text-gray-700 dark:text-gray-200 underline">
                    Send now
                </button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>


<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        tinymce.init({
            selector: '#content_html',
            menubar: false,
            plugins: 'link lists',
            toolbar: 'undo redo | bold italic underline | bullist numlist | link removeformat',
            height: 320,
            branding: false,
        });
    });
</script>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/admin/newsletter_campaigns/_form.blade.php ENDPATH**/ ?>