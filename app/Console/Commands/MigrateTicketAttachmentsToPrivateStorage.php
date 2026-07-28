<?php

namespace App\Console\Commands;

use App\Models\TicketAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateTicketAttachmentsToPrivateStorage extends Command
{
    protected $signature = 'security:migrate-ticket-attachments {--keep-public : Copy files but do not remove legacy public copies}';
    protected $description = 'Move legacy ticket attachments from public storage to private storage';

    public function handle(): int
    {
        $moved = 0;
        $missing = 0;

        TicketAttachment::query()->orderBy('id')->chunkById(200, function ($attachments) use (&$moved, &$missing): void {
            foreach ($attachments as $attachment) {
                $path = (string) ($attachment->file_path ?? $attachment->path ?? '');
                if ($path === '' || Storage::disk('local')->exists($path)) {
                    continue;
                }

                if (! Storage::disk('public')->exists($path)) {
                    $missing++;
                    $this->warn("Attachment #{$attachment->id} is missing: {$path}");
                    continue;
                }

                $stream = Storage::disk('public')->readStream($path);
                if ($stream === false) {
                    $missing++;
                    continue;
                }

                Storage::disk('local')->writeStream($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (! $this->option('keep-public')) {
                    Storage::disk('public')->delete($path);
                }

                $moved++;
            }
        });

        $this->info("Ticket attachments moved to private storage: {$moved}");
        if ($missing > 0) {
            $this->warn("Missing attachment files: {$missing}");
        }

        return $missing > 0 ? self::FAILURE : self::SUCCESS;
    }
}
