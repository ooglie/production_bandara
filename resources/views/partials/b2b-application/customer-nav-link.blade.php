@php
    $businessApplicationHref = auth()->check()
        ? route('account.business-application.show')
        : route('business-account.index');
@endphp
<a href="{{ $businessApplicationHref }}" class="inline-flex items-center rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
    Business account
</a>
