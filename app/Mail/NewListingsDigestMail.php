<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class NewListingsDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, \App\Models\Listing> $listings */
    public function __construct(
        public User $user,
        public Collection $listings,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.new_listings_digest_subject', ['count' => $this->listings->count()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-listings-digest',
        );
    }
}