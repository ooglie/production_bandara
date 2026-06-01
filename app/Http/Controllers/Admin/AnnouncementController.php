<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnnouncementRequest;
use App\Models\Announcement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::query()
            ->orderByDesc('is_active')
            ->orderByDesc('priority')
            ->latest()
            ->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $announcement = new Announcement([
            'type' => 'info',
            'is_active' => true,
            'show_on_home' => true,
            'is_dismissible' => true,
            'priority' => 0,
        ]);

        return view('admin.announcements.create', compact('announcement'));
    }

    public function store(AnnouncementRequest $request)
    {
        $announcement = new Announcement($this->payload($request));
        $this->syncBackgroundImage($request, $announcement);
        $announcement->save();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', compact('announcement'));
    }

    public function update(AnnouncementRequest $request, Announcement $announcement)
    {
        $announcement->fill($this->payload($request));
        $this->syncBackgroundImage($request, $announcement);
        $announcement->save();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->deleteBackgroundImage($announcement->background_image_path);
        $announcement->delete();

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    protected function payload(AnnouncementRequest $request): array
    {
        $data = $request->validated();

        unset($data['background_image'], $data['remove_background_image']);

        $data['title'] = trim($data['title']);
        $data['label'] = filled($data['label'] ?? null) ? trim($data['label']) : null;
        $data['message'] = filled($data['message'] ?? null) ? trim($data['message']) : null;
        $data['icon'] = filled($data['icon'] ?? null) ? trim($data['icon']) : null;
        $data['cta_text'] = filled($data['cta_text'] ?? null) ? trim($data['cta_text']) : null;
        $data['cta_url'] = filled($data['cta_url'] ?? null) ? trim($data['cta_url']) : null;
        $data['secondary_text'] = filled($data['secondary_text'] ?? null) ? trim($data['secondary_text']) : null;
        $data['secondary_url'] = filled($data['secondary_url'] ?? null) ? trim($data['secondary_url']) : null;

        $data['is_active'] = $request->boolean('is_active');
        $data['show_on_home'] = $request->boolean('show_on_home');
        $data['is_dismissible'] = $request->boolean('is_dismissible');

        $data['priority'] = $data['priority'] ?? 0;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;

        return $data;
    }

    protected function syncBackgroundImage(AnnouncementRequest $request, Announcement $announcement): void
    {
        if ($request->boolean('remove_background_image') && $announcement->background_image_path) {
            $this->deleteBackgroundImage($announcement->background_image_path);
            $announcement->background_image_path = null;
        }

        if ($request->hasFile('background_image')) {
            if ($announcement->background_image_path) {
                $this->deleteBackgroundImage($announcement->background_image_path);
            }

            $announcement->background_image_path = $this->storeBackgroundImage(
                $request->file('background_image')
            );
        }
    }

    protected function storeBackgroundImage(UploadedFile $file): string
    {
        return $file->store('announcements', 'public');
    }

    protected function deleteBackgroundImage(?string $path): void
    {
        if (!filled($path)) {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        $normalized = Str::startsWith($path, '/storage/')
            ? ltrim(Str::after($path, '/storage/'), '/')
            : ltrim($path, '/');

        Storage::disk('public')->delete($normalized);
    }
}