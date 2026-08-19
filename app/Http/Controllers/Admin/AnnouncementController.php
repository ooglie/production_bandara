<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AnnouncementRequest;
use App\Models\Announcement;
use App\Services\MediaPathService;
use App\Services\MediaReferenceService;

class AnnouncementController extends Controller
{
    public function __construct(
        protected MediaPathService $media,
        protected MediaReferenceService $mediaReferences,
    ) {
    }

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
        $newPath = null;
        $announcement = null;

        try {
            $announcement = Announcement::create($this->payload($request));

            if ($request->hasFile('background_image')) {
                $newPath = $this->media->storePublic(
                    $request->file('background_image'),
                    $this->media->announcementImagesDirectory($announcement),
                    'background'
                );
                $announcement->update(['background_image_path' => $newPath]);
            }
        } catch (\Throwable $e) {
            if ($newPath) {
                $this->media->deleteFromDisks($newPath, [$this->media->publicDisk()]);
            }

            if ($announcement?->exists) {
                try {
                    $announcement->delete();
                } catch (\Throwable) {
                    // Preserve the original upload/create exception.
                }
            }

            throw $e;
        }

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
        $oldPath = $announcement->background_image_path;
        $newPath = null;
        $announcement->fill($this->payload($request));

        if ($request->boolean('remove_background_image')) {
            $announcement->background_image_path = null;
        }

        if ($request->hasFile('background_image')) {
            $newPath = $this->media->storePublic(
                $request->file('background_image'),
                $this->media->announcementImagesDirectory($announcement),
                'background'
            );
            $announcement->background_image_path = $newPath;
        }

        try {
            $announcement->save();
        } catch (\Throwable $e) {
            if ($newPath) {
                $this->media->deleteFromDisks($newPath, [$this->media->publicDisk()]);
            }

            throw $e;
        }

        if ($oldPath && $oldPath !== $announcement->background_image_path) {
            $this->deleteBackgroundImage($oldPath);
        }

        return redirect()
            ->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $oldPath = $announcement->background_image_path;
        $announcement->delete();
        $this->deleteBackgroundImage($oldPath);

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

    protected function deleteBackgroundImage(?string $path): void
    {
        $this->mediaReferences->deletePublicFileIfUnreferenced($path);
    }
}
