@php
    $kitchenStaff = \Illuminate\Support\Facades\Auth::guard('staff')->user();
    $showKitchenNav = false;

    if ($kitchenStaff) {
        try {
            $roleNames = method_exists($kitchenStaff, 'roles')
                ? $kitchenStaff->roles->pluck('name')->map(fn ($name) => mb_strtolower(trim((string) $name)))
                : collect();
            $showKitchenNav = $roleNames->intersect(['admin', 'manager'])->isNotEmpty();
        } catch (\Throwable) {
            $showKitchenNav = false;
        }

        if (! $showKitchenNav && method_exists($kitchenStaff, 'can')) {
            foreach (['manage kitchen', 'manage chefs', 'manage content'] as $kitchenPermission) {
                try {
                    if ($kitchenStaff->can($kitchenPermission)) {
                        $showKitchenNav = true;
                        break;
                    }
                } catch (\Throwable) {
                    // Try the next compatible permission name.
                }
            }
        }
    }

    $kitchenActive = request()->routeIs('admin.kitchen.*');
@endphp

@if ($showKitchenNav)
    <a href="{{ route('admin.kitchen.chefs.index') }}"
       @class([
           'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
           'bg-slate-200 text-slate-950 dark:bg-slate-800 dark:text-white' => $kitchenActive,
           'text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-white' => ! $kitchenActive,
       ])
       @if ($kitchenActive) aria-current="page" @endif>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-4 w-4 shrink-0" stroke="currentColor" stroke-width="1.5">
            <path d="M5 4.5h14M7.5 4.5v4.25a4.5 4.5 0 0 0 9 0V4.5M6.5 19.5h11M9 13.5v6M15 13.5v6" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span>Bandara Kitchen</span>
    </a>
@endif
