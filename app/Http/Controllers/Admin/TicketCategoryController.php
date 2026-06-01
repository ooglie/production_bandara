<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TicketCategoryController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $categories = TicketCategory::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%");
            })
            ->orderBy('position')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.ticket-categories.index', compact('categories', 'q'));
    }

    public function create()
    {
        return view('admin.ticket-categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => ['nullable', 'string', 'max:191', 'unique:ticket_categories,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'position'    => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['slug'] ?? null, $data['name']);
        $data['position']  = (int) ($data['position'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        TicketCategory::create($data);

        return redirect()
            ->route('admin.ticket-categories.index')
            ->with('status', 'Ticket category created.');
    }

    public function edit(TicketCategory $ticketCategory)
    {
        return view('admin.ticket-categories.edit', [
            'category' => $ticketCategory,
        ]);
    }

    public function update(Request $request, TicketCategory $ticketCategory)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => [
                'nullable', 'string', 'max:191',
                Rule::unique('ticket_categories', 'slug')->ignore($ticketCategory->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'position'    => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        $data['slug'] = $this->makeUniqueSlug($data['slug'] ?? null, $data['name'], $ticketCategory->id);
        $data['position']  = (int) ($data['position'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $ticketCategory->update($data);

        return redirect()
            ->route('admin.ticket-categories.index')
            ->with('status', 'Ticket category updated.');
    }

    public function destroy(TicketCategory $ticketCategory)
    {
        try {
            $ticketCategory->delete();
            return back()->with('status', 'Ticket category deleted.');
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors([
                'delete' => 'Unable to delete this category. It may be in use by tickets.',
            ]);
        }
    }

    private function makeUniqueSlug(?string $slug, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        $final = $base ?: Str::random(8);

        $i = 2;
        while (
            TicketCategory::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $final)
                ->exists()
        ) {
            $final = $base . '-' . $i;
            $i++;
        }

        return $final;
    }
}
