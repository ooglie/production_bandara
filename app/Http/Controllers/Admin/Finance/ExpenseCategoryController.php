<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Support\FinanceAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): View
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        $categories = ExpenseCategory::query()
            ->withCount(['expenses', 'recurringTemplates'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.finance.expense-categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        ExpenseCategory::query()->create([
            'name' => trim($validated['name']),
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_system' => false,
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => (int) ($validated['sort_order'] ?? 1000),
            'created_by_id' => $request->user()?->id,
            'updated_by_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'Expense category created.');
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $expenseCategory->update([
            'name' => trim($validated['name']),
            // Slugs are deliberately stable because reports and integrations may
            // refer to a category after its display name changes.
            'description' => $validated['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? $expenseCategory->sort_order),
            'updated_by_id' => $request->user()?->id,
        ]);

        return back()->with('status', 'Expense category updated.');
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        FinanceAccess::authorize($request->user(), FinanceAccess::EXPENSE_SETTINGS_MANAGE);

        if ($expenseCategory->is_system
            || $expenseCategory->expenses()->exists()
            || $expenseCategory->recurringTemplates()->exists()) {
            $expenseCategory->update([
                'is_active' => false,
                'updated_by_id' => $request->user()?->id,
            ]);

            return back()->with('status', 'The category is in use and was made inactive instead of being deleted.');
        }

        $expenseCategory->delete();

        return back()->with('status', 'Expense category deleted.');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'expense-category';
        $slug = $base;
        $suffix = 2;

        while (ExpenseCategory::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
