<x-layouts.admin :title="$isEdit ? 'Edit monthly salary' : 'New monthly salary'" :heading="$isEdit ? 'Edit monthly salary' : 'New monthly salary'">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    @php
        $salaryMonth = old('salary_month', $entry->salary_month?->format('Y-m') ?? today()->format('Y-m'));
        $paymentDate = old('payment_date', $entry->payment_date?->format('Y-m-d'));
        $selectedStatus = old('payment_status', $entry->payment_status ?: \App\Models\SalaryEntry::STATUS_PENDING);
    @endphp

    <section class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
        The first release uses fixed monthly salary with optional additions and deductions. Attendance, leave, PF, ESIC, professional tax, and TDS calculations are intentionally outside this release. A salary change during a month is not prorated automatically.
    </section>

    <form method="POST" action="{{ $isEdit ? route('admin.finance.salary-entries.update', $entry) : route('admin.finance.salary-entries.store') }}" class="mt-6 space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="grid gap-4 md:grid-cols-2">
                @if ($isEdit)
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Staff member</p>
                        <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:bg-slate-800 dark:text-white">{{ $entry->staff_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Salary month</p>
                        <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-950 dark:bg-slate-800 dark:text-white">{{ $entry->salary_month?->format('F Y') }}</p>
                    </div>
                @else
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Staff member</span>
                        <select name="user_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="">Select staff member</option>
                            @foreach ($staffMembers as $staff)
                                <option value="{{ $staff->id }}" @selected((string) old('user_id', $entry->user_id) === (string) $staff->id)>{{ $staff->name }} · {{ $staff->email }}{{ $staff->is_active ? '' : ' · inactive staff' }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Salary month</span>
                        <input type="month" name="salary_month" value="{{ $salaryMonth }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                @endif
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Salary calculation</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Basic / monthly salary</span>
                    <input id="basic_salary" type="number" name="basic_salary" value="{{ old('basic_salary', $entry->basic_salary ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Additions</span>
                    <input id="additions" type="number" name="additions" value="{{ old('additions', $entry->additions ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Deductions</span>
                    <input id="deductions" type="number" name="deductions" value="{{ old('deductions', $entry->deductions ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Net payable</span>
                    <input id="net_payable_preview" readonly value="0.00" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-950 dark:border-slate-800 dark:bg-slate-800 dark:text-white">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Payment and notes</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment status</span>
                    <select name="payment_status" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment date</span>
                    <input type="date" name="payment_date" value="{{ $paymentDate }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment method</span>
                    <select name="payment_method" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">Select when paid</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', $entry->payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment reference</span>
                    <input name="payment_reference" value="{{ old('payment_reference', $entry->payment_reference) }}" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block md:col-span-2 xl:col-span-4">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Notes</span>
                    <textarea name="notes" rows="5" maxlength="10000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('notes', $entry->notes) }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ $isEdit ? route('admin.finance.salary-entries.show', $entry) : route('admin.finance.salary-entries.index', ['month' => $salaryMonth]) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancel</a>
            <button type="submit" class="rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">{{ $isEdit ? 'Save salary record' : 'Create salary record' }}</button>
        </div>
    </form>

    <script>
        (() => {
            const basic = document.getElementById('basic_salary');
            const additions = document.getElementById('additions');
            const deductions = document.getElementById('deductions');
            const total = document.getElementById('net_payable_preview');
            const update = () => {
                const b = Number.parseFloat(basic?.value || '0') || 0;
                const a = Number.parseFloat(additions?.value || '0') || 0;
                const d = Number.parseFloat(deductions?.value || '0') || 0;
                if (total) total.value = Math.max(0, b + a - d).toFixed(2);
            };
            basic?.addEventListener('input', update);
            additions?.addEventListener('input', update);
            deductions?.addEventListener('input', update);
            update();
        })();
    </script>
</x-layouts.admin>
