<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterCampaignMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterCampaignController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterCampaign::with('createdBy')
            ->withCount('recipients')
            ->orderByDesc('created_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(20);

        return view('admin.newsletter_campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $statuses = [
            NewsletterCampaign::STATUS_DRAFT,
            NewsletterCampaign::STATUS_SCHEDULED,
        ];

        return view('admin.newsletter_campaigns.create', compact('statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'subject'      => ['required', 'string', 'max:255'],
            'content_html' => ['required', 'string'],
            'content_text' => ['nullable', 'string'],
            'status'       => ['nullable', 'in:draft,scheduled'],
            'scheduled_for'=> ['nullable', 'date'],
        ]);

        $campaign = new NewsletterCampaign();
        $campaign->name         = $data['name'];
        $campaign->subject      = $data['subject'];
        $campaign->content_html = $data['content_html'];
        $campaign->content_text = $data['content_text'] ?? null;
        $campaign->status       = $data['status'] ?? NewsletterCampaign::STATUS_DRAFT;
        $campaign->scheduled_for= $data['scheduled_for'] ?? null;
        $campaign->created_by_id= Auth::id();
        $campaign->save();

        return redirect()
            ->route('admin.newsletter-campaigns.edit', $campaign)
            ->with('status', 'Campaign created.');
    }

    public function edit(NewsletterCampaign $campaign)
    {
        $statuses = [
            NewsletterCampaign::STATUS_DRAFT,
            NewsletterCampaign::STATUS_SCHEDULED,
        ];

        return view('admin.newsletter_campaigns.edit', compact('campaign', 'statuses'));
    }

    public function update(Request $request, NewsletterCampaign $campaign)
    {
        if (in_array($campaign->status, [
            NewsletterCampaign::STATUS_SENDING,
            NewsletterCampaign::STATUS_SENT,
            NewsletterCampaign::STATUS_CANCELLED,
        ], true)) {
            return back()->with('status', 'This campaign can no longer be edited.');
        }

        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'subject'      => ['required', 'string', 'max:255'],
            'content_html' => ['required', 'string'],
            'content_text' => ['nullable', 'string'],
            'status'       => ['nullable', 'in:draft,scheduled'],
            'scheduled_for'=> ['nullable', 'date'],
        ]);

        $campaign->name         = $data['name'];
        $campaign->subject      = $data['subject'];
        $campaign->content_html = $data['content_html'];
        $campaign->content_text = $data['content_text'] ?? null;
        $campaign->status       = $data['status'] ?? $campaign->status;
        $campaign->scheduled_for= $data['scheduled_for'] ?? null;
        $campaign->save();

        return redirect()
            ->route('admin.newsletter-campaigns.edit', $campaign)
            ->with('status', 'Campaign updated.');
    }

    public function destroy(NewsletterCampaign $campaign)
    {
        if (in_array($campaign->status, [
            NewsletterCampaign::STATUS_SENDING,
            NewsletterCampaign::STATUS_SENT,
        ], true)) {
            return back()->with('status', 'Sent/sending campaigns cannot be deleted.');
        }

        $campaign->delete();

        return redirect()
            ->route('admin.newsletter-campaigns.index')
            ->with('status', 'Campaign deleted.');
    }

    /**
     * Send this campaign immediately to all ACTIVE subscribers.
     */
    public function sendNow(Request $request, NewsletterCampaign $campaign)
    {
        if (!config('features.newsletter', true)) {
            abort(404);
        }

        if (in_array($campaign->status, [
            NewsletterCampaign::STATUS_SENDING,
            NewsletterCampaign::STATUS_SENT,
        ], true)) {
            return back()->with('status', 'This campaign has already been sent or is sending.');
        }

        $totalSubscribers = NewsletterSubscriber::active()->count();

        if ($totalSubscribers === 0) {
            return back()->with('status', 'No active subscribers to send this campaign to.');
        }

        $campaign->status = NewsletterCampaign::STATUS_SENDING;
        $campaign->save();

        $sentCount = 0;

        NewsletterSubscriber::active()->chunk(100, function ($subscribers) use ($campaign, &$sentCount) {
            foreach ($subscribers as $subscriber) {
                // Skip if the email field is missing
                if (!$subscriber->email) {
                    continue;
                }

                $recipient = NewsletterCampaignRecipient::create([
                    'campaign_id'      => $campaign->id,
                    'subscriber_id'    => $subscriber->id,
                    'sent_at'          => null,
                    'open_count'       => 0,
                    'last_opened_at'   => null,
                    'bounce_status'    => null,
                    'unsubscribe_token'=> Str::random(32),
                ]);

                Mail::to($subscriber->email)->send(
                    new NewsletterCampaignMail($campaign, $subscriber, $recipient)
                );

                $recipient->sent_at = now();
                $recipient->save();

                $sentCount++;
            }
        });

        $campaign->status = NewsletterCampaign::STATUS_SENT;
        $campaign->sent_at = now();
        $campaign->save();

        return redirect()
            ->route('admin.newsletter-campaigns.index')
            ->with('status', "Campaign sent to {$sentCount} subscriber(s).");
    }
}
