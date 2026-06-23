<?php

namespace App\Mail;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FavoriteListingArchivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Listing $listing,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.favorite_archived_subject', [
                'vehicle' => $this->listing->composeDisplayTitle(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.favorite-listing-archived',
        );
    }
}