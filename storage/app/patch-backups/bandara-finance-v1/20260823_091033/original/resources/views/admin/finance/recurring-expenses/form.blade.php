<x-layouts.admin :title="$isEdit ? 'Edit recurring expense' : 'New recurring expense'" :heading="$isEdit ? 'Edit recurring expense' : 'New recurring expense'">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    @php
        $startDate = old('start_date', $template->start_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
        $endDate = old('end_date', $template->end_date?->format('Y-m-d'));
        $nextDueDate = old('next_due_date', $template->next_due_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
    @endphp

    <form method="POST" action="{{ $isEdit ? route('admin.finance.recurring-expenses.update', $template) : route('admin.finance.recurring-expenses.store') }}" class="space-y-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Template details</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Generation creates a reviewable draft. It never posts an expense automatically.</p>
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="block md:col-span-2">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Description</span>
                    <input name="description" value="{{ old('description', $template->description) }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payee</span>
                    <input name="payee" value="{{ old('payee', $template->payee) }}" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Category</span>
                    <select name="expense_category_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">Select</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $template->expense_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Frequency</span>
                    <select name="frequency" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach ($frequencies as $value => $label)
                            <option value="{{ $value }}" @selected(old('frequency', $template->frequency) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Default payment method</span>
                    <select name="default_payment_method" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">None</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('default_payment_method', $template->default_payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Expected amounts and dates</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Expected taxable amount</span>
                    <input id="expected_taxable_amount" type="number" name="expected_taxable_amount" value="{{ old('expected_taxable_amount', $template->expected_taxable_amount ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Expected GST</span>
                    <input id="expected_gst_amount" type="number" name="expected_gst_amount" value="{{ old('expected_gst_amount', $template->expected_gst_amount ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Expected total</span>
                    <input id="expected_total_preview" readonly value="0.00" class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-950 dark:border-slate-800 dark:bg-slate-800 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Start date</span>
                    <input type="date" name="start_date" value="{{ $startDate }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">End date</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Next due date</span>
                    <input type="date" name="next_due_date" value="{{ $nextDueDate }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Notes copied to generated drafts</span>
                <textarea name="notes" rows="5" maxlength="10000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('notes', $template->notes) }}</textarea>
            </label>
            <label class="mt-4 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active ?? true)) class="rounded border-slate-300">
                Active
            </label>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.finance.recurring-expenses.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 dark:border-slate-700 dark:text-slate-200">Cancel</a>
            <button type="submit" class="rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white dark:bg-slate-100 dark:text-slate-950">{{ $isEdit ? 'Save template' : 'Create template' }}</button>
        </div>
    </form>

    <script>
        (() => {
            const taxable = document.getElementById('expected_taxable_amount');
            const gst = document.getElementById('expected_gst_amount');
            const total = document.getElementById('expected_total_preview');
            const update = () => {
                const a = Number.parseFloat(taxable?.value || '0') || 0;
                const b = Number.parseFloat(gst?.value || '0') || 0;
                if (total) total.value = (a + b).toFixed(2);
            };
            taxable?.addEventListener('input', update);
            gst?.addEventListener('input', update);
            update();
        })();
    </script>
</x-layouts.admin>
