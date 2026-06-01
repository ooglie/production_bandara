<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterConfirmMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    /**
     * Subscribe a guest or logged-in user to the newsletter (double opt-in).
     */
    public function subscribe(Request $request)
    {
        if (!config('features.newsletter', true)) {
            abort(404);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'name'  => ['nullable', 'string', 'max:255'],
        ]);

        $user   = $request->user();
        $source = $request->input('source')
            ?: ($user ? 'frontend_user' : 'frontend_guest');

        $subscriber = NewsletterSubscriber::where('email', $data['email'])->first();

        if ($subscriber) {
            // Already exists -> handle by status
            // if ($subscriber->status === NewsletterSubscriber::STATUS_ACTIVE
            if ($subscriber->status === 'NewsletterSubscriber::STATUS_ACTIVE'
                && !$subscriber->unsubscribed_at) {

                return back()->with('newsletter_status', 'You are already subscribed to our newsletter.');
            }

            if ($subscriber->status === NewsletterSubscriber::STATUS_BOUNCED) {
                return back()->with('newsletter_status', 'We could not send email to this address earlier. Please contact support.');
            }

            // pending OR unsubscribed => re-send confirmation
            $subscriber->name               = $data['name'] ?: $subscriber->name;
            $subscriber->status             = NewsletterSubscriber::STATUS_PENDING;
            $subscriber->confirmation_token = Str::random(40);
            $subscriber->unsubscribed_at    = null;
            $subscriber->source             = $source;
            $subscriber->save();

            $this->sendConfirmationEmail($subscriber);

            return back()->with('newsletter_status', 'Please check your email to confirm your subscription.');
        }

        // New subscriber
        $subscriber = NewsletterSubscriber::create([
            'email'              => $data['email'],
            'name'               => $data['name'] ?? null,
            'status'             => NewsletterSubscriber::STATUS_PENDING,
            'confirmation_token' => Str::random(40),
            'source'             => $source,
        ]);

        $this->sendConfirmationEmail($subscriber);

        return back()->with('newsletter_status', 'Please check your email to confirm your subscription.');
    }

    /**
     * Confirm a subscription via token (double opt-in).
     */
    public function confirm(Request $request, NewsletterSubscriber $subscriber, string $token)
    {
        if (!config('features.newsletter', true)) {
            abort(404);
        }

        if (!$subscriber->confirmation_token ||
            !hash_equals($subscriber->confirmation_token, $token)) {

            return redirect()
                ->route('home')
                ->with('newsletter_status', 'This confirmation link is invalid or has expired.');
        }

        if ($subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            return redirect()
                ->route('home')
                ->with('newsletter_status', 'This email has been unsubscribed from the newsletter.');
        }

        // Activate subscription
        $subscriber->status             = NewsletterSubscriber::STATUS_ACTIVE;
        $subscriber->confirmed_at       = now();
        $subscriber->unsubscribed_at    = null;
        $subscriber->confirmation_token = null;
        $subscriber->save();

        return redirect()
            ->route('home')
            ->with('newsletter_status', 'Thank you! Your newsletter subscription is now confirmed.');
    }

    /**
     * Unsubscribe using a signed URL.
     */
    public function unsubscribe(Request $request, NewsletterSubscriber $subscriber)
    {
        if (!config('features.newsletter', true)) {
            abort(404);
        }

        // Set unsubscribed status
        $subscriber->status             = NewsletterSubscriber::STATUS_UNSUBSCRIBED;
        $subscriber->unsubscribed_at    = now();
        $subscriber->confirmation_token = null;
        $subscriber->save();

        return redirect()
            ->route('home')
            ->with('newsletter_status', 'You have been unsubscribed from the newsletter.');
    }

    protected function sendConfirmationEmail(NewsletterSubscriber $subscriber): void
    {
        if (!$subscriber->email) {
            return;
        }

        Mail::to($subscriber->email)->send(new NewsletterConfirmMail($subscriber));
    }
}
