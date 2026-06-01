<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index(Request $request)
    {
        $query = Page::query()->orderBy('sort_order')->orderBy('key');

        if ($search = trim((string) $request->get('q', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('key', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->get('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->get('status') === 'inactive') {
            $query->where('is_active', false);
        }

        $pages = $query->paginate(25)->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        $page = new Page($this->localizedData($data));
        $page->created_by_id = $request->user()?->id;
        $page->updated_by_id = $request->user()?->id;
        $page->save();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page created.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $data = $this->validatedData($request, $page->id);

        $page->fill($this->localizedData($data));
        $page->updated_by_id = $request->user()?->id;
        $page->save();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page updated.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('status', 'Page deleted.');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $keyRule = Rule::unique('pages', 'key')->whereNull('deleted_at');

        if ($ignoreId) {
            $keyRule->ignore($ignoreId);
        }

        return $request->validate([
            'key' => ['required', 'string', 'max:255', $keyRule],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function localizedData(array $data): array
    {
        $slug = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['title']);

        return [
            'key' => trim($data['key']),
            'title' => ['en' => trim($data['title'])],
            'slug' => ['en' => $slug],
            'excerpt' => ['en' => $this->nullableString($data['excerpt'] ?? null)],
            'content' => ['en' => $this->nullableString($data['content'] ?? null)],
            'meta_title' => ['en' => $this->nullableString($data['meta_title'] ?? null)],
            'meta_description' => ['en' => $this->nullableString($data['meta_description'] ?? null)],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
