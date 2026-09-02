@php
    $bandaraFinanceUser = $user ?? auth()->user();
    $bandaraFinanceLandingRoute = \App\Support\FinanceAccess::landingRouteName($bandaraFinanceUser);
@endphp

@if ($bandaraFinanceLandingRoute && \Illuminate\Support\Facades\Route::has($bandaraFinanceLandingRoute))
    <div class="mb-4">
        <p class="mb-1 text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">Finance</p>
        <a href="{{ route($bandaraFinanceLandingRoute) }}"
           class="block rounded-md px-2 py-1.5 {{ request()->routeIs('admin.finance.*') ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-50' : 'hover:bg-gray-100 dark:hover:bg-gray-800' }}">
            Salary &amp; expenses
        </a>
    </div>
@endif
