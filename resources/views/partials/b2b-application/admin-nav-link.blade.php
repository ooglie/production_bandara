@php
    $currentAdmin = auth()->user();
    $showB2BApplications = $currentAdmin instanceof \App\Models\User
        && \App\Support\B2BApplicationAccess::adminCan($currentAdmin, 'view');
@endphp
@if ($showB2BApplications)
    <a href="{{ route('admin.b2b-applications.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5V6a3 3 0 0 0-3-3h-6a3 3 0 0 0-3 3v1.5m12 0h.75A2.25 2.25 0 0 1 21 9.75v8.5a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18.25v-8.5A2.25 2.25 0 0 1 5.25 7.5H6m12 0H6m3 4.5h6" /></svg>
        <span>B2B applications</span>
    </a>
@endif
