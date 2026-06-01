<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with('category')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('customer.tickets.index', compact('tickets'));
    }

    public function create()
    {
        $categories = TicketCategory::where('is_active', true)->orderBy('name')->get();

        // Some UIs show recent tickets on the create page (you already do this)
        $tickets = Ticket::with('category')
            ->where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('customer.tickets.create', compact('categories', 'tickets'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'category_id'    => ['required', 'exists:ticket_categories,id'],
            'subject'        => ['required', 'string', 'max:255'],

            // Your UI / older code sometimes has message+description mismatch.
            // Accept either, require at least one.
            'message'        => ['nullable', 'string', 'max:5000', 'required_without:description'],
            'description'    => ['nullable', 'string', 'max:5000', 'required_without:message'],

            'attachments.*'  => ['sometimes', 'file', 'max:5120'], // 5 MB each
        ]);

        // Use both fields if present: description for ticket summary, message for first message body.
        $ticketDescription = $data['description'] ?? $data['message'] ?? '';
        $firstMessageBody  = $data['message'] ?? $data['description'] ?? '';

        DB::beginTransaction();

        try {
            // Create ticket (explicit assignments so it works even if fillable is not set)
            $ticket = new Ticket();
            $ticket->ticket_number  = $this->generateTicketNumber();
            $ticket->user_id        = $user->id;
            $ticket->category_id    = (int) $data['category_id'];
            $ticket->subject        = $data['subject'];

            // If your tickets table has description, set it.
            // If not, we avoid breaking by checking the column.
            if (Schema::hasColumn('tickets', 'description')) {
                $ticket->description = $ticketDescription;
            }

            // Use an enum value that your DB accepts (you already used 'new' successfully)
            $ticket->status         = 'new';
            $ticket->priority       = 'normal';
            $ticket->assigned_to_id = null;

            if (Schema::hasColumn('tickets', 'last_reply_at')) {
                $ticket->last_reply_at = now();
            }

            $ticket->save();

            // First ticket message
            $message = new TicketMessage();
            $message->ticket_id   = $ticket->id;
            $message->user_id     = $user->id;
            $message->message     = $firstMessageBody;
            $message->is_internal = false;
            $message->save();

            // Attachments (optional)
            $files = $request->file('attachments', []);
            if ($files instanceof \Illuminate\Http\UploadedFile) {
                $files = [$files];
            }

             if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('tickets/attachments', 'public');

                    $att = new TicketAttachment();
                    $att->ticket_message_id = $message->id;
                    $att->file_path              = $path;
                    $att->original_name     = $file->getClientOriginalName();
                    $att->mime_type         = $file->getClientMimeType();
                    $att->size              = $file->getSize();
                    $att->save();
                }
             }

            DB::commit();

            return redirect()
                ->route('tickets.index', $ticket)
                ->with('status', 'Your ticket has been created. Our support team will get back to you soon.');
            } catch (\Throwable $e) {
                DB::rollBack();
                report($e);

                return back()
                    ->withErrors(['ticket' => 'Unable to create ticket. Please try again.'])
                    ->withInput();
            }
    }

    public function show(Ticket $ticket)
    {
        $this->authorizeCustomer($ticket);

        $ticket->load([
            'category',
            'messages.author',
            'messages.attachments',
            // 'description',
        ]);

        return view('customer.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $this->authorizeCustomer($ticket);
        $ticket->status         = 'new';

        $data = $request->validate([
            'message'       => ['required', 'string', 'max:4000'],
            'attachments.*' => ['sometimes', 'file', 'max:5120'],
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            $msg = new TicketMessage();
            $msg->ticket_id   = $ticket->id;
            $msg->user_id     = $user->id;
            $msg->message     = $data['message'];
            $msg->is_internal = false;
            $msg->save();

            // Attachments (optional)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('tickets/attachments', 'public');

                    $att = new TicketAttachment();
                    // $att->ticket_message_id         = $ticket->id;
                    $att->ticket_message_id = $msg->id;
                    $att->file_path              = $path;
                    $att->original_name     = $file->getClientOriginalName();
                    $att->mime_type         = $file->getClientMimeType();
                    $att->size              = $file->getSize();
                    $att->save();
                }
            }

            // Update ticket status safely (avoid enum values that may not exist)
            // We set it back to "new" on any customer reply (reopen behavior).
            $updates = [];

            if (Schema::hasColumn('tickets', 'status')) {
                $updates['status'] = 'awaiting_support';
            }

            if (Schema::hasColumn('tickets', 'last_reply_at')) {
                $updates['last_reply_at'] = now();
            }

            // If your table has closed_at and it was closed, replying reopens it.
            if (Schema::hasColumn('tickets', 'closed_at')) {
                $updates['closed_at'] = null;
            }

            if (!empty($updates)) {
                $ticket->update($updates);
            }

            DB::commit();

            return back()->with('status', 'Reply sent to support.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['ticket' => 'Unable to send reply. Please try again.'])
                ->withInput();
        }
    }

    public function close(Ticket $ticket)
    {
        $this->authorizeCustomer($ticket);

        $updates = [
            'status' => 'closed',
        ];

        if (Schema::hasColumn('tickets', 'closed_at')) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        return back()->with('status', 'Ticket closed.');
    }

    protected function authorizeCustomer(Ticket $ticket): void
    {
        if ((int) $ticket->user_id !== (int) Auth::id()) {
            abort(403);
        }
    }

    /**
     * Generate a unique ticket_number.
     * Format: TKT-YYYYMMDD-XXXXXX
     */
    private function generateTicketNumber(): string
    {
        $prefix = 'TKT-' . now()->format('Ymd') . '-';

        do {
            $num = $prefix . Str::upper(Str::random(6));
        } while (Ticket::where('ticket_number', $num)->exists());

        return $num;
    }

    public function reopen(Ticket $ticket)
    {
        $this->authorizeCustomer($ticket);

        if ((string)($ticket->status ?? '') !== 'closed') {
            return back()->with('status', 'Ticket is already open.');
        }

        $updates = [
            // ✅ use a status your DB already supports
            'status' => 'new',
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('tickets', 'closed_at')) {
            $updates['closed_at'] = null;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('tickets', 'resolved_at')) {
            $updates['resolved_at'] = null;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('tickets', 'last_reply_at')) {
            $updates['last_reply_at'] = now();
        }

        $ticket->update($updates);

        return back()->with('status', 'Ticket reopened.');
    }
}
