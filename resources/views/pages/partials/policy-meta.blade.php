@php
    $policy = config('bandara_content.policies');
@endphp

<div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
    <span>Version {{ $policy['version'] }}</span>
    <span>Effective {{ $policy['effective_date'] }}</span>
    <span>Last updated {{ $policy['last_updated'] }}</span>
</div>
