<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        try {
            $message = ContactMessage::query()->create($request->safe()->except('company'));
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'contact' => 'Your message could not be sent. Please try again.',
            ]);
        }

        try {
            $recipient = SiteSetting::query()->value('contact_recipient_email');

            if (filled($recipient)) {
                Notification::route('mail', $recipient)
                    ->notify(new ContactMessageReceived($message));
            }
        } catch (Throwable $exception) {
            // The inbox already contains the message. Do not encourage a duplicate submission.
            report($exception);
        }

        return back()->with('contact.status', 'sent');
    }
}
