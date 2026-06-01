<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        // Counts
        $unassigned = Ticket::query()
            ->whereNull('assigned_to_id')
            ->count();

        $mine = Ticket::query()
            ->where('assigned_to_id', $user->id)
            ->count();

        // "Awaiting customer reply" per your Support controller usage
        $awaitingCustomer = Ticket::query()
            ->where('status', 'awaiting_customer')
            ->count();

        // Lists for dashboard
        $myTickets = Ticket::query()
            ->with(['customer', 'category', 'assignee'])
            ->where('assigned_to_id', $user->id)
            ->orderByRaw("
                CASE
                    WHEN status = 'awaiting_customer' THEN 0
                    WHEN status = 'awaiting_agent' THEN 1
                    WHEN status = 'new' THEN 2
                    WHEN status = 'open' THEN 3
                    WHEN status = 'resolved' THEN 9
                    WHEN status = 'closed' THEN 10
                    ELSE 5
                END
            ")
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $unassignedTickets = Ticket::query()
            ->with(['customer', 'category'])
            ->whereNull('assigned_to_id')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        return view('dashboard.support', compact(
            'unassigned',
            'mine',
            'awaitingCustomer',
            'myTickets',
            'unassignedTickets'
        ));
    }
}