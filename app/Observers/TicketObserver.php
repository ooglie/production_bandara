<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\TicketStatusHistory;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        // Log initial status when ticket is created
        $status = (string)($ticket->status ?? '');
        if ($status === '') return;

        TicketStatusHistory::create([
            'ticket_id'      => $ticket->id,
            'from_status'    => null,
            'to_status'      => $status,
            'changed_by_id'  => auth()->id(), // may be null (system)
            'created_at'     => now(),
        ]);
    }

    public function updating(Ticket $ticket): void
    {
        // Only log if status is actually changing
        if (!$ticket->isDirty('status')) return;

        $from = (string)($ticket->getOriginal('status') ?? '');
        $to   = (string)($ticket->status ?? '');

        if ($to === '' || $from === $to) return;

        TicketStatusHistory::create([
            'ticket_id'      => $ticket->id,
            'from_status'    => $from !== '' ? $from : null,
            'to_status'      => $to,
            'changed_by_id'  => auth()->id(),
            'created_at'     => now(),
        ]);
    }
}