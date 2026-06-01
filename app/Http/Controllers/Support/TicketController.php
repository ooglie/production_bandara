<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\TicketTag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        // For managers / admins: show all; for support: show unassigned + mine
        $user = Auth::user();

        $query = Ticket::with(['customer', 'category', 'assignee'])
            ->orderByDesc('created_at');

        if ($user->hasRole('Support')) {
            $query->where(function ($q) use ($user) {
                $q->whereNull('assigned_to_id')
                  ->orWhere('assigned_to_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $q = trim((string)$request->get('q'));
            $query->where(function ($w) use ($q) {
                $w->where('id', (int)$q)
                ->orWhere('subject', 'like', "%{$q}%")
                ->orWhere('ticket_number', 'like', "%{$q}%");
            });
        }

        $tickets = $query->paginate(20)->withQueryString();

        // $tickets = $query->paginate(20);

        return view('support.tickets.index', compact('tickets'));
    }

    public function unassigned()
    {
        $tickets = Ticket::with(['customer', 'category'])
            ->whereNull('assigned_to_id')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('support.tickets.index', [
            'tickets' => $tickets,
            'viewTitle' => 'Unassigned tickets',
        ]);
    }

    public function mine()
    {
        $tickets = Ticket::with(['customer', 'category'])
            ->where('assigned_to_id', Auth::id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('support.tickets.index', [
            'tickets' => $tickets,
            'viewTitle' => 'My tickets',
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load([
            'customer',
            'assignee',
            'category',
            'tags',
            'messages.author',
            'messages.attachments',
        ]);

        $agents = User::role(['Support', 'Manager'])->orderBy('name')->get();
        $allTags = TicketTag::orderBy('name')->get();

        return view('support.tickets.show', compact('ticket', 'agents', 'allTags'));
    }

    public function assignToMe(Ticket $ticket)
    {
        if ($ticket->assigned_to_id && $ticket->assigned_to_id !== Auth::id()) {
            // already assigned to someone else
            return back()->with('error', 'Ticket is already assigned to another agent.');
        }

        $ticket->update([
            'assigned_to_id' => Auth::id(),
        ]);

        return back()->with('status', 'Ticket assigned to you.');
    }

    public function reassign(Request $request, Ticket $ticket)
    {
        $this->authorizeManagerOrAdmin();

        $data = $request->validate([
            'assigned_to_id' => ['nullable', 'exists:users,id'],
        ]);

        $ticket->update([
            'assigned_to_id' => $data['assigned_to_id'] ?? null,
        ]);

        return back()->with('status', 'Ticket assignment updated.');
    }

    /**
     * Public reply visible to the customer.
     * Shows as "Support Team" from customer point of view.
     */
   public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'message'       => ['required', 'string', 'max:5000'],
            'attachments.*' => ['sometimes', 'file', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $ticket, $data) {

            $msg = new TicketMessage();
            $msg->ticket_id   = $ticket->id;
            $msg->user_id     = Auth::id();
            $msg->message     = $data['message'];
            $msg->is_internal = false;

            // ✅ write into the correct column name
            // if (Schema::hasColumn('ticket_messages', 'message')) {
            //     $msg->message = $data['message'];
            // } elseif (Schema::hasColumn('ticket_messages', 'body')) {
            //     $msg->body = $data['message'];
            // } elseif (Schema::hasColumn('ticket_messages', 'description')) {
            //     $msg->description = $data['message'];
            // }

            $msg->save();

            // Attachments (optional) — supports both "path" and "file_path" column styles
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('tickets/attachments', 'public');

                    $att = new TicketAttachment();

                    // Some schemas store ticket_id; keep it if present
                    if (Schema::hasColumn('ticket_attachments', 'ticket_id')) {
                        $att->ticket_id = $ticket->id;
                    }

                    // Some schemas store ticket_message_id; keep it if present
                    if (Schema::hasColumn('ticket_attachments', 'ticket_message_id')) {
                        $att->ticket_message_id = $msg->id;
                    }

                    if (Schema::hasColumn('ticket_attachments', 'file_path')) {
                        $att->file_path = $path;
                    } elseif (Schema::hasColumn('ticket_attachments', 'path')) {
                        $att->path = $path;
                    }

                    if (Schema::hasColumn('ticket_attachments', 'original_name')) {
                        $att->original_name = $file->getClientOriginalName();
                    }

                    if (Schema::hasColumn('ticket_attachments', 'mime_type')) {
                        $att->mime_type = $file->getClientMimeType();
                    }

                    if (Schema::hasColumn('ticket_attachments', 'size')) {
                        $att->size = $file->getSize();
                    }

                    $att->save();
                }
            }

            // ✅ update ticket status + last reply
            $updates = [];

            // Use whatever statuses your DB supports. Your code uses awaiting_customer already.
            if (Schema::hasColumn('tickets', 'status')) {
                $updates['status'] = 'awaiting_customer';
            }

            if (Schema::hasColumn('tickets', 'last_reply_at')) {
                $updates['last_reply_at'] = now();
            }

            if (!empty($updates)) {
                $ticket->update($updates);
            }
        });

        return back()->with('status', 'Reply sent to customer.');
    }

    /**
     * Internal note (only visible to staff / manager / admin).
     */
    public function addInternalNote(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($ticket, $data) {
            $msg = new TicketMessage();
            $msg->ticket_id   = $ticket->id;
            $msg->user_id     = Auth::id();
            $msg->is_internal = true;

            // ✅ write into the correct column name
            if (Schema::hasColumn('ticket_messages', 'message')) {
                $msg->message = $data['message'];
            } elseif (Schema::hasColumn('ticket_messages', 'body')) {
                $msg->body = $data['message'];
            } elseif (Schema::hasColumn('ticket_messages', 'description')) {
                $msg->description = $data['message'];
            }

            $msg->save();

            // Optional: bump last activity
            if (Schema::hasColumn('tickets', 'last_reply_at')) {
                $ticket->update(['last_reply_at' => now()]);
            }
        });

        return back()->with('status', 'Internal note added.');
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,awaiting_customer,awaiting_agent,resolved,closed'],
        ]);

        $update = ['status' => $data['status']];

        if ($data['status'] === 'resolved') {
            $update['resolved_at'] = now();
        } elseif ($data['status'] === 'closed') {
            $update['closed_at'] = now();
        }

        $ticket->update($update);

        return back()->with('status', 'Ticket status updated.');
    }

    public function updateTags(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'tags'   => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:ticket_tags,id'],
        ]);

        $ticket->tags()->sync($data['tags'] ?? []);

        return back()->with('status', 'Ticket tags updated.');
    }

    protected function authorizeManagerOrAdmin(): void
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['Manager', 'Admin'])) {
            abort(403);
        }
    }
}
