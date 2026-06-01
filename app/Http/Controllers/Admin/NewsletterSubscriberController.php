<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterConfirmMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterSubscriberController extends Controller
{
    public function index(Request $request)
    {
        $query = NewsletterSubscriber::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        // CSV export
        if ($request->get('export') === 'csv') {
            $rows = $query->orderBy('email')->get();

            $filename = 'newsletter_subscribers_' . now()->format('Ymd_His') . '.csv';

            $callback = function () use ($rows) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Email', 'Name', 'Status', 'Confirmed At', 'Unsubscribed At', 'Source', 'Created At']);

                foreach ($rows as $s) {
                    fputcsv($out, [
                        $s->email,
                        $s->name,
                        $s->status,
                        optional($s->confirmed_at)->toDateTimeString(),
                        optional($s->unsubscribed_at)->toDateTimeString(),
                        $s->source,
                        $s->created_at->toDateTimeString(),
                    ]);
                }

                fclose($out);
            };

            return new StreamedResponse($callback, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        }

        $subscribers = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.newsletter_subscribers.index', compact('subscribers'));
    }

    public function create()
    {
        $statuses = [
            NewsletterSubscriber::STATUS_PENDING,
            NewsletterSubscriber::STATUS_ACTIVE,
            NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            NewsletterSubscriber::STATUS_BOUNCED,
        ];

        return view('admin.newsletter_subscribers.create', compact('statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email'  => ['required', 'email', 'max:255', 'unique:newsletter_subscribers,email'],
            'name'   => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,active,unsubscribed,bounced'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $subscriber = new NewsletterSubscriber();
        $subscriber->email  = $data['email'];
        $subscriber->name   = $data['name'] ?? null;
        $subscriber->status = $data['status'];
        $subscriber->source = $data['source'] ?? 'admin';

        if ($subscriber->status === NewsletterSubscriber::STATUS_ACTIVE) {
            $subscriber->confirmed_at = now();
        }

        if ($subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            $subscriber->unsubscribed_at = now();
        }

        $subscriber->save();

        return redirect()
            ->route('admin.newsletter-subscribers.index')
            ->with('status', 'Subscriber created.');
    }

    public function edit(NewsletterSubscriber $subscriber)
    {
        $statuses = [
            NewsletterSubscriber::STATUS_PENDING,
            NewsletterSubscriber::STATUS_ACTIVE,
            NewsletterSubscriber::STATUS_UNSUBSCRIBED,
            NewsletterSubscriber::STATUS_BOUNCED,
        ];

        return view('admin.newsletter_subscribers.edit', compact('subscriber', 'statuses'));
    }

    public function update(Request $request, NewsletterSubscriber $subscriber)
    {
        $data = $request->validate([
            'email'  => ['required', 'email', 'max:255', 'unique:newsletter_subscribers,email,' . $subscriber->id],
            'name'   => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:pending,active,unsubscribed,bounced'],
            'source' => ['nullable', 'string', 'max:255'],
        ]);

        $subscriber->email  = $data['email'];
        $subscriber->name   = $data['name'] ?? null;
        $subscriber->source = $data['source'] ?? $subscriber->source;

        $oldStatus = $subscriber->status;
        $newStatus = $data['status'];

        $subscriber->status = $newStatus;

        if ($newStatus === NewsletterSubscriber::STATUS_ACTIVE && !$subscriber->confirmed_at) {
            $subscriber->confirmed_at    = now();
            $subscriber->unsubscribed_at = null;
            $subscriber->confirmation_token = null;
        }

        if ($newStatus === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            $subscriber->unsubscribed_at = now();
            $subscriber->confirmation_token = null;
        }

        if ($newStatus === NewsletterSubscriber::STATUS_PENDING) {
            $subscriber->confirmed_at    = null;
            $subscriber->unsubscribed_at = null;
            if (!$subscriber->confirmation_token) {
                $subscriber->confirmation_token = \Illuminate\Support\Str::random(40);
            }
        }

        $subscriber->save();

        return redirect()
            ->route('admin.newsletter-subscribers.index')
            ->with('status', 'Subscriber updated.');
    }

    public function destroy(NewsletterSubscriber $subscriber)
    {
        $subscriber->delete();

        return redirect()
            ->route('admin.newsletter-subscribers.index')
            ->with('status', 'Subscriber deleted.');
    }

    public function resendConfirmation(NewsletterSubscriber $subscriber)
    {
        if ($subscriber->status !== NewsletterSubscriber::STATUS_PENDING) {
            return back()->with('status', 'Only pending subscribers can receive a confirmation email.');
        }

        if (!$subscriber->confirmation_token) {
            $subscriber->confirmation_token = \Illuminate\Support\Str::random(40);
            $subscriber->save();
        }

        Mail::to($subscriber->email)->send(new NewsletterConfirmMail($subscriber));

        return back()->with('status', 'Confirmation email re-sent.');
    }
}
