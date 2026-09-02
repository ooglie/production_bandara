<?php
    $bandaraFlashMessages = [];

    $flashMap = [
        'success' => ['type' => 'success', 'title' => 'Success'],
        'status' => ['type' => 'success', 'title' => 'Updated'],
        'message' => ['type' => 'info', 'title' => 'Notice'],
        'info' => ['type' => 'info', 'title' => 'Notice'],
        'warning' => ['type' => 'warning', 'title' => 'Please note'],
        'error' => ['type' => 'error', 'title' => 'Please check'],
        'newsletter_status' => ['type' => 'success', 'title' => 'Newsletter'],
    ];

    foreach ($flashMap as $key => $meta) {
        if (session()->has($key)) {
            $message = session($key);
            if (is_array($message)) {
                $message = implode(' ', array_filter($message));
            }

            if ($key === 'status' && $message === 'verification-link-sent') {
                $message = 'A new verification link has been sent to your email address.';
            }

            if (filled($message)) {
                $bandaraFlashMessages[] = [
                    'type' => $meta['type'],
                    'title' => $meta['title'],
                    'message' => (string) $message,
                ];
            }
        }
    }

    if (isset($errors) && $errors->any()) {
        $bandaraFlashMessages[] = [
            'type' => 'error',
            'title' => 'Please correct the highlighted fields',
            'message' => $errors->count() === 1
                ? $errors->first()
                : $errors->count() . ' fields need your attention.',
        ];
    }
?>

<div id="bandara-toast-root"
     class="bandara-message-root"
     aria-live="polite"
     aria-atomic="false"></div>

<div id="bandara-confirm-modal"
     class="bandara-confirm hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="bandara-confirm-title"
     aria-describedby="bandara-confirm-message">
    <div class="bandara-confirm__overlay" data-bandara-confirm-cancel></div>

    <div class="bandara-confirm__panel">
        <div class="bandara-confirm__body">
            <div id="bandara-confirm-icon" class="bandara-confirm__icon">?</div>
            <div class="bandara-confirm__content">
                <h2 id="bandara-confirm-title" class="bandara-confirm__title">Confirm action</h2>
                <p id="bandara-confirm-message" class="bandara-confirm__message">Are you sure you want to continue?</p>
            </div>
        </div>

        <div class="bandara-confirm__actions">
            <button type="button"
                    id="bandara-confirm-cancel"
                    data-bandara-confirm-cancel
                    class="bandara-confirm__button bandara-confirm__button--cancel">
                No
            </button>
            <button type="button"
                    id="bandara-confirm-ok"
                    class="bandara-confirm__button bandara-confirm__button--confirm">
                Yes
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.BandaraToast && window.BandaraConfirm) {
        return;
    }

    var initialMessages = <?php echo json_encode($bandaraFlashMessages, 15, 512) ?>;
    var toastRoot = null;
    var maxToasts = 3;

    var toastStyles = {
        success: { icon: '✓', title: 'Success' },
        error: { icon: '!', title: 'Please check' },
        warning: { icon: '!', title: 'Please note' },
        info: { icon: 'i', title: 'Notice' }
    };

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    function asText(value) {
        if (value === null || value === undefined) return '';
        return String(value);
    }

    function removeToast(toast) {
        if (!toast || toast.dataset.removing === 'true') return;
        toast.dataset.removing = 'true';
        toast.classList.add('is-removing');
        setTimeout(function () {
            if (toast && toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 180);
    }

    function normalizeToast(input, fallbackType, title) {
        if (typeof input === 'string') {
            return {
                type: fallbackType || 'info',
                title: title || null,
                message: input
            };
        }

        var options = input || {};
        return {
            type: options.type || fallbackType || 'info',
            title: options.title || title || null,
            message: options.message || options.text || '',
            timeout: options.timeout,
            persistent: options.persistent === true
        };
    }

    function showToast(input, fallbackType, title) {
        var options = normalizeToast(input, fallbackType, title);
        var type = toastStyles[options.type] ? options.type : 'info';
        var style = toastStyles[type];
        var message = asText(options.message).trim();

        if (!message) return null;

        toastRoot = toastRoot || document.getElementById('bandara-toast-root');
        if (!toastRoot) return null;

        while (toastRoot.children.length >= maxToasts) {
            toastRoot.removeChild(toastRoot.firstElementChild);
        }

        var toast = document.createElement('div');
        toast.className = 'bandara-message bandara-message--' + type;
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        var row = document.createElement('div');
        row.className = 'bandara-message__row';

        var icon = document.createElement('div');
        icon.className = 'bandara-message__icon';
        icon.textContent = style.icon;

        var content = document.createElement('div');
        content.className = 'bandara-message__content';

        var heading = document.createElement('div');
        heading.className = 'bandara-message__title';
        heading.textContent = options.title || style.title;

        var body = document.createElement('div');
        body.className = 'bandara-message__body';
        body.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'bandara-message__close';
        close.setAttribute('aria-label', 'Close message');
        close.textContent = '×';
        close.addEventListener('click', function () { removeToast(toast); });

        content.appendChild(heading);
        content.appendChild(body);
        row.appendChild(icon);
        row.appendChild(content);
        row.appendChild(close);
        toast.appendChild(row);
        toastRoot.appendChild(toast);

        if (!options.persistent) {
            setTimeout(function () { removeToast(toast); }, Number(options.timeout || 4200));
        }

        return toast;
    }

    var confirmModal = null;
    var confirmTitle = null;
    var confirmMessage = null;
    var confirmOk = null;
    var confirmCancel = null;
    var confirmIcon = null;
    var activeResolver = null;
    var lastFocusedElement = null;

    function confirmButtonClass(variant) {
        var base = 'bandara-confirm__button bandara-confirm__button--confirm';

        if (variant === 'danger') {
            return base + ' bandara-confirm__button--danger';
        }

        if (variant === 'warning') {
            return base + ' bandara-confirm__button--warning';
        }

        return base;
    }

    function initConfirm() {
        confirmModal = confirmModal || document.getElementById('bandara-confirm-modal');
        if (!confirmModal) return false;

        confirmTitle = confirmTitle || document.getElementById('bandara-confirm-title');
        confirmMessage = confirmMessage || document.getElementById('bandara-confirm-message');
        confirmOk = confirmOk || document.getElementById('bandara-confirm-ok');
        confirmCancel = confirmCancel || document.getElementById('bandara-confirm-cancel');
        confirmIcon = confirmIcon || document.getElementById('bandara-confirm-icon');

        return !!(confirmTitle && confirmMessage && confirmOk && confirmCancel && confirmIcon);
    }

    function closeConfirm(result) {
        if (!initConfirm()) return;

        confirmModal.classList.remove('is-open');
        confirmModal.classList.add('hidden');
        document.documentElement.classList.remove('bandara-confirm-open');

        if (activeResolver) {
            var resolver = activeResolver;
            activeResolver = null;
            resolver(result === true);
        }

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    function openConfirm(options) {
        options = options || {};

        if (!initConfirm()) {
            return Promise.resolve(false);
        }

        if (activeResolver) {
            closeConfirm(false);
        }

        lastFocusedElement = document.activeElement;

        confirmTitle.textContent = options.title || 'Confirm action';
        confirmMessage.textContent = options.message || 'Are you sure you want to continue?';
        confirmOk.textContent = options.confirmText || 'Continue';
        confirmCancel.textContent = options.cancelText || 'Cancel';
        confirmIcon.textContent = options.icon || (options.variant === 'danger' ? '!' : '?');
        confirmOk.className = confirmButtonClass(options.variant || 'default');

        confirmModal.classList.remove('hidden');
        confirmModal.classList.add('is-open');
        document.documentElement.classList.add('bandara-confirm-open');

        setTimeout(function () {
            confirmOk.focus();
        }, 20);

        return new Promise(function (resolve) {
            activeResolver = resolve;
        });
    }

    function readConfirmOptions(el) {
        return {
            title: el.getAttribute('data-bandara-confirm-title') || 'Please confirm',
            message: el.getAttribute('data-bandara-confirm') || el.getAttribute('data-bandara-confirm-message') || 'Are you sure you want to continue?',
            confirmText: el.getAttribute('data-bandara-confirm-text') || 'Yes',
            cancelText: el.getAttribute('data-bandara-cancel-text') || 'No',
            variant: el.getAttribute('data-bandara-confirm-variant') || 'danger'
        };
    }

    function bindConfirmables() {
        document.addEventListener('click', function (event) {
            var cancel = event.target.closest('[data-bandara-confirm-cancel]');
            if (cancel) {
                event.preventDefault();
                closeConfirm(false);
                return;
            }

            if (event.target === confirmOk || event.target.closest('#bandara-confirm-ok')) {
                event.preventDefault();
                closeConfirm(true);
                return;
            }

            var link = event.target.closest('a[data-bandara-confirm], button[data-bandara-confirm]:not([type="submit"])');
            if (!link || link.dataset.bandaraConfirmed === '1') return;

            var form = link.closest('form');
            if (form && link.type === 'submit') return;

            event.preventDefault();
            openConfirm(readConfirmOptions(link)).then(function (confirmed) {
                if (!confirmed) return;

                link.dataset.bandaraConfirmed = '1';

                if (link.tagName === 'A' && link.href) {
                    window.location.href = link.href;
                    return;
                }

                link.click();
                setTimeout(function () { delete link.dataset.bandaraConfirmed; }, 0);
            });
        }, true);

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('form[data-bandara-confirm]');
            if (!form || form.dataset.bandaraConfirmed === '1') return;

            event.preventDefault();
            var submitter = event.submitter || document.activeElement;

            openConfirm(readConfirmOptions(form)).then(function (confirmed) {
                if (!confirmed) return;

                form.dataset.bandaraConfirmed = '1';

                if (typeof form.requestSubmit === 'function') {
                    try {
                        form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
                    } catch (e) {
                        form.submit();
                    }
                } else {
                    form.submit();
                }
            });
        }, true);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && confirmModal && confirmModal.classList.contains('is-open')) {
                event.preventDefault();
                closeConfirm(false);
            }
        });
    }

    window.BandaraToast = {
        show: function (options) { return showToast(options); },
        success: function (message, title) { return showToast(message, 'success', title); },
        error: function (message, title) { return showToast(message, 'error', title); },
        warning: function (message, title) { return showToast(message, 'warning', title); },
        info: function (message, title) { return showToast(message, 'info', title); }
    };

    window.BandaraConfirm = {
        open: openConfirm,
        close: closeConfirm
    };

    ready(function () {
        toastRoot = document.getElementById('bandara-toast-root');
        initConfirm();
        bindConfirmables();

        initialMessages.forEach(function (message, index) {
            setTimeout(function () { showToast(message); }, index * 120);
        });
    });
})();
</script>
<?php /**PATH /Users/ooglie/Website/ChatGPT/PRODUCTIONFrozen/BandaraFrozen/resources/views/partials/frontend/messages.blade.php ENDPATH**/ ?>