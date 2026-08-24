<x-layouts.admin :title="$isEdit ? 'Edit salary profile' : 'New salary profile'" :heading="$isEdit ? 'Edit salary profile' : 'New salary profile'">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    @php
        $effectiveFrom = old('effective_from', $profile->effective_from?->format('Y-m-d') ?? today()->startOfMonth()->format('Y-m-d'));
        $effectiveTo = old('effective_to', $profile->effective_to?->format('Y-m-d'));
    @endphp

    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/20 dark:text-amber-200">
        Salary profile periods cannot overlap for the same staff member. When a salary changes, close the earlier profile with an effective-to date and create a new profile. Existing monthly salary records are snapshots and will not be recalculated.
    </section>

    <form method="POST" action="{{ $isEdit ? route('admin.finance.salary-profiles.update', $profile) : route('admin.finance.salary-profiles.store') }}" class="mt-6 space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Staff member</span>
                    <select name="user_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">Select staff member</option>
                        @foreach ($staffMembers as $staff)
                            <option value="{{ $staff->id }}" @selected((string) old('user_id', $profile->user_id) === (string) $staff->id)>{{ $staff->name }} · {{ $staff->email }}{{ isset($staff->is_active) && ! $staff->is_active ? ' (inactive account)' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Monthly salary</span>
                    <input type="number" name="monthly_salary" value="{{ old('monthly_salary', $profile->monthly_salary) }}" min="0.01" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment day</span>
                    <input type="number" name="payment_day" value="{{ old('payment_day', $profile->payment_day ?? 7) }}" min="1" max="31" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">Days 29–31 are treated operationally as month-end in shorter months.</span>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Effective from</span>
                    <input type="date" name="effective_from" value="{{ $effectiveFrom }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Effective to</span>
                    <input type="date" name="effective_to" value="{{ $effectiveTo }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Notes</span>
                    <textarea name="notes" rows="5" maxlength="10000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('notes', $profile->notes) }}</textarea>
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200 md:col-span-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $profile->is_active ?? true)) class="rounded border-slate-300">
                    Active salary profile
                </label>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.finance.salary-profiles.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancel</a>
            <button type="submit" class="rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">{{ $isEdit ? 'Save profile' : 'Create profile' }}</button>
        </div>
    </form>
</x-layouts.admin>
