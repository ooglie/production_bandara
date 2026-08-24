<x-layouts.admin :title="$isEdit ? 'Edit business expense' : 'New business expense'" :heading="$isEdit ? 'Edit business expense' : 'New business expense'">
    @include('admin.finance.partials.flash')
    @include('admin.finance.partials.nav')

    @php
        $expenseDate = old('expense_date', $expense->expense_date?->format('Y-m-d') ?? today()->format('Y-m-d'));
        $dueDate = old('due_date', $expense->due_date?->format('Y-m-d'));
        $paidDate = old('paid_date', $expense->paid_date?->format('Y-m-d'));
        $selectedPaymentStatus = old('payment_status', $expense->payment_status ?: \App\Models\BusinessExpense::PAYMENT_UNPAID);
    @endphp

    <form method="POST" action="{{ $isEdit ? route('admin.finance.expenses.update', $expense) : route('admin.finance.expenses.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Expense details</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">The total is calculated as taxable amount plus GST.</p>
                </div>
                @if ($expense->recurring_expense_template_id)
                    <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-200">Generated from recurring template</span>
                @endif
            </div>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Expense date</span>
                    <input type="date" name="expense_date" value="{{ $expenseDate }}" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block md:col-span-1 xl:col-span-2">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Category</span>
                    <select name="expense_category_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('expense_category_id', $expense->expense_category_id) === (string) $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block md:col-span-2">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Description</span>
                    <input name="description" value="{{ old('description', $expense->description) }}" required maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payee</span>
                    <input name="payee" value="{{ old('payee', $expense->payee) }}" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Amounts and payment</h2>
            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Taxable amount</span>
                    <input id="taxable_amount" type="number" name="taxable_amount" value="{{ old('taxable_amount', $expense->taxable_amount ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">GST amount</span>
                    <input id="gst_amount" type="number" name="gst_amount" value="{{ old('gst_amount', $expense->gst_amount ?? 0) }}" min="0" step="0.01" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Total amount</span>
                    <input id="total_amount_preview" value="0.00" readonly class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-950 dark:border-slate-800 dark:bg-slate-800 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment status</span>
                    <select id="payment_status" name="payment_status" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach (\App\Models\BusinessExpense::paymentStatuses() as $value => $label)
                            <option value="{{ $value }}" @selected($selectedPaymentStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment method</span>
                    <select name="payment_method" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <option value="">Select when paid</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('payment_method', $expense->payment_method) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Payment reference</span>
                    <input name="payment_reference" value="{{ old('payment_reference', $expense->payment_reference) }}" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Due date</span>
                    <input type="date" name="due_date" value="{{ $dueDate }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Paid date</span>
                    <input type="date" name="paid_date" value="{{ $paidDate }}" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-950 dark:text-white">Receipt and notes</h2>
            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Invoice or receipt attachment</span>
                    <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png,.webp" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">
                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">PDF or image, maximum 10 MB. Stored privately and served only after finance authorisation.</span>
                    @if ($expense->receipt_path)
                        <span class="mt-2 block text-xs text-slate-500 dark:text-slate-400">Current file: {{ $expense->receipt_original_name }}</span>
                    @endif
                </label>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Notes</span>
                    <textarea name="notes" rows="5" maxlength="10000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('notes', $expense->notes) }}</textarea>
                </label>
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ $isEdit ? route('admin.finance.expenses.show', $expense) : route('admin.finance.expenses.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-lg bg-slate-950 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-950">{{ $isEdit ? 'Save draft' : 'Create draft expense' }}</button>
        </div>
    </form>

    <script>
        (() => {
            const taxable = document.getElementById('taxable_amount');
            const gst = document.getElementById('gst_amount');
            const total = document.getElementById('total_amount_preview');
            const updateTotal = () => {
                const taxableValue = Number.parseFloat(taxable?.value || '0') || 0;
                const gstValue = Number.parseFloat(gst?.value || '0') || 0;
                if (total) total.value = (taxableValue + gstValue).toFixed(2);
            };
            taxable?.addEventListener('input', updateTotal);
            gst?.addEventListener('input', updateTotal);
            updateTotal();
        })();
    </script>
</x-layouts.admin>
