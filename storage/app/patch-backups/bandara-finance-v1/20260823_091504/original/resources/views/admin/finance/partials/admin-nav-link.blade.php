@php
    $bandaraFinanceReady = false;
    $bandaraFinanceLandingRoute = null;

    try {
        $bandaraFinanceReady = \Illuminate\Support\Facades\Schema::hasTable('business_expenses')
            && \Illuminate\Support\Facades\Schema::hasTable('salary_entries');

        if ($bandaraFinanceReady) {
            $bandaraFinanceLandingRoute = \App\Support\FinanceAccess::landingRouteName(auth()->user());
        }
    } catch (\Throwable $exception) {
        report($exception);
    }
@endphp
@if ($bandaraFinanceLandingRoute)
    <a href="{{ route($bandaraFinanceLandingRoute) }}"
       class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white {{ request()->routeIs('admin.finance.*') ? 'bg-slate-100 text-slate-950 dark:bg-slate-800 dark:text-white' : '' }}">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="h-4 w-4 shrink-0">
            <path d="M4 19V9m5 10V5m5 14v-7m5 7V3" stroke-linecap="round" />
        </svg>
        <span>Finance</span>
    </a>
@endif
