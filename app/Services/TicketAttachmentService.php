<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

class TicketAttachmentService
{
    public const MAX_FILES = 5;
    public const MAX_KILOBYTES = 5120;

    /** @return array<string, array<int, string>> */
    public function validationRules(): array
    {
        return [
            'attachments' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'attachments.*' => [
                'file',
                'max:'.self::MAX_KILOBYTES,
                'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
            ],
        ];
    }

    public function store(UploadedFile $file, TicketMessage $message, Ticket $ticket): TicketAttachment
    {
        $path = $file->store('tickets/attachments', 'local');

        $attachment = new TicketAttachment();

        if (Schema::hasColumn('ticket_attachments', 'ticket_id')) {
            $attachment->ticket_id = $ticket->id;
        }

        if (Schema::hasColumn('ticket_attachments', 'ticket_message_id')) {
            $attachment->ticket_message_id = $message->id;
        }

        if (Schema::hasColumn('ticket_attachments', 'file_path')) {
            $attachment->file_path = $path;
        } elseif (Schema::hasColumn('ticket_attachments', 'path')) {
            $attachment->path = $path;
        }

        if (Schema::hasColumn('ticket_attachments', 'original_name')) {
            $attachment->original_name = mb_substr(basename($file->getClientOriginalName()), 0, 255);
        }

        if (Schema::hasColumn('ticket_attachments', 'mime_type')) {
            $attachment->mime_type = $file->getMimeType();
        }

        if (Schema::hasColumn('ticket_attachments', 'size')) {
            $attachment->size = (int) $file->getSize();
        }

        $attachment->save();

        return $attachment;
    }
}
