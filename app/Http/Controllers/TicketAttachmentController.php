<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    public function __invoke(Request $request, TicketAttachment $attachment): BinaryFileResponse|StreamedResponse
    {
        $attachment->loadMissing(['message.ticket', 'ticket']);

        $ticket = $attachment->message?->ticket ?? $attachment->ticket;
        abort_unless($ticket, 404);

        $user = $request->user();
        abort_unless($user, 403);

        if ($user->hasRole('Customer')) {
            abort_unless((int) $ticket->user_id === (int) $user->id, 404);
            abort_if((bool) $attachment->message?->is_internal, 404);
        } else {
            abort_unless($user->can('view tickets'), 403);

            if ($user->hasRole('Support')) {
                abort_if(
                    $ticket->assigned_to_id !== null
                    && (int) $ticket->assigned_to_id !== (int) $user->id,
                    403
                );
            }
        }

        $path = (string) ($attachment->file_path ?? $attachment->path ?? '');
        abort_if($path === '', 404);

        // New files live on the private local disk. Keep a read-only fallback
        // for legacy public attachments until they are migrated.
        $disk = Storage::disk('local')->exists($path) ? 'local' : 'public';
        abort_unless(Storage::disk($disk)->exists($path), 404);

        $downloadName = basename((string) ($attachment->original_name ?: 'attachment'));

        return Storage::disk($disk)->download($path, $downloadName, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
