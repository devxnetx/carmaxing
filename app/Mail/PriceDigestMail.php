<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class PriceDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param Collection<int, \App\Models\ListingPriceChange> $changes */
    public function __construct(
        public User $user,
        public Collection $changes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.price_digest_subject', ['count' => $this->changes->count()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.price-digest',
        );
    }
}