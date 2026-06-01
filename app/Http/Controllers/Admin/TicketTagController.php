<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketTagController extends Controller
{
    public function index()
    {
        $tags = TicketTag::orderBy('name')->paginate(20);
        return view('admin.ticket-tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.ticket-tags.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'slug'  => ['nullable', 'string', 'max:120', 'unique:ticket_tags,slug'],
            'color' => ['nullable', 'string', 'max:32'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        TicketTag::create($data);

        return redirect()->route('admin.ticket-tags.index')
            ->with('status', 'Tag created.');
    }

    public function edit(TicketTag $ticketTag)
    {
        return view('admin.ticket-tags.edit', ['tag' => $ticketTag]);
    }

    public function update(Request $request, TicketTag $ticketTag)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'slug'  => ['nullable', 'string', 'max:120', 'unique:ticket_tags,slug,' . $ticketTag->id],
            'color' => ['nullable', 'string', 'max:32'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $ticketTag->update($data);

        return redirect()->route('admin.ticket-tags.index')
            ->with('status', 'Tag updated.');
    }

    public function destroy(TicketTag $ticketTag)
    {
        $ticketTag->delete();

        return back()->with('status', 'Tag deleted.');
    }
}
