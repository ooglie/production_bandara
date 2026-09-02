<?php

namespace App\Http\Controllers;

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
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $email = Str::lower(trim((string) $data['email']));
        $name = trim((string) ($data['name'] ?? ''));
        $user = $request->user();

        // The compact newsletter form may submit only an email address.
        // For the logged-in customer's own email, use the account name when available.
        if (
            $name === ''
            && $user !== null
            && strcasecmp(trim((string) ($user->email ?? '')), $email) === 0
        ) {
            $name = $this->subscriberNameFromUser($user);
        }

        $source = $request->input('source')
            ?: ($user ? 'frontend_user' : 'frontend_guest');

        $subscriber = NewsletterSubscriber::query()
            ->where('email', $email)
            ->first();

        if ($subscriber) {
            if (
                $subscriber->status === NewsletterSubscriber::STATUS_ACTIVE
                && $subscriber->unsubscribed_at === null
            ) {
                return back()->with(
                    'newsletter_status',
                    'You are already subscribed to our newsletter.'
                );
            }

            if ($subscriber->status === NewsletterSubscriber::STATUS_BOUNCED) {
                return back()->with(
                    'newsletter_status',
                    'We could not send email to this address earlier. Please contact support.'
                );
            }

            // Pending or unsubscribed: refresh the record and send a new confirmation.
            if ($name !== '') {
                $subscriber->name = $name;
            }

            $subscriber->email = $email;
            $subscriber->status = NewsletterSubscriber::STATUS_PENDING;
            $subscriber->confirmation_token = Str::random(40);
            $subscriber->unsubscribed_at = null;
            $subscriber->source = $source;
            $subscriber->save();

            $this->sendConfirmationEmail($subscriber);

            return back()->with(
                'newsletter_status',
                'Please check your email to confirm your subscription.'
            );
        }

        $subscriber = NewsletterSubscriber::create([
            'email' => $email,
            'name' => $name !== '' ? $name : null,
            'status' => NewsletterSubscriber::STATUS_PENDING,
            'confirmation_token' => Str::random(40),
            'source' => $source,
        ]);

        $this->sendConfirmationEmail($subscriber);

        return back()->with(
            'newsletter_status',
            'Please check your email to confirm your subscription.'
        );
    }

    /**
     * Confirm a subscription via token (double opt-in).
     */
    public function confirm(
        Request $request,
        NewsletterSubscriber $subscriber,
        string $token
    ) {
        if (!config('features.newsletter', true)) {
            abort(404);
        }

        if (
            !$subscriber->confirmation_token
            || !hash_equals($subscriber->confirmation_token, $token)
        ) {
            return redirect()
                ->route('home')
                ->with(
                    'newsletter_status',
                    'This confirmation link is invalid or has expired.'
                );
        }

        if ($subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            return redirect()
                ->route('home')
                ->with(
                    'newsletter_status',
                    'This email has been unsubscribed from the newsletter.'
                );
        }

        $subscriber->status = NewsletterSubscriber::STATUS_ACTIVE;
        $subscriber->confirmed_at = now();
        $subscriber->unsubscribed_at = null;
        $subscriber->confirmation_token = null;
        $subscriber->save();

        return redirect()
            ->route('home')
            ->with(
                'newsletter_status',
                'Thank you! Your newsletter subscription is now confirmed.'
            );
    }

    /**
     * Unsubscribe using a signed URL.
     */
    public function unsubscribe(
        Request $request,
        NewsletterSubscriber $subscriber
    ) {
        if (!config('features.newsletter', true)) {
            abort(404);
        }

        $subscriber->status = NewsletterSubscriber::STATUS_UNSUBSCRIBED;
        $subscriber->unsubscribed_at = now();
        $subscriber->confirmation_token = null;
        $subscriber->save();

        return redirect()
            ->route('home')
            ->with(
                'newsletter_status',
                'You have been unsubscribed from the newsletter.'
            );
    }

    /**
     * Resolve a usable newsletter name from the logged-in customer account.
     */
    protected function subscriberNameFromUser(object $user): string
    {
        $name = trim((string) ($user->name ?? ''));

        if ($name !== '') {
            return $name;
        }

        $firstName = trim((string) ($user->first_name ?? ''));
        $lastName = trim((string) ($user->last_name ?? ''));

        return trim($firstName.' '.$lastName);
    }

    protected function sendConfirmationEmail(
        NewsletterSubscriber $subscriber
    ): void {
        if (!$subscriber->email) {
            return;
        }

        Mail::to($subscriber->email)
            ->send(new NewsletterConfirmMail($subscriber));
    }
}
