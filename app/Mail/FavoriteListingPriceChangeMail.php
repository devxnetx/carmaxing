<?php

namespace App\Mail;

use App\Models\Listing;
use App\Models\ListingPriceChange;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FavoriteListingPriceChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Listing $listing,
        public ListingPriceChange $change,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.favorite_price_change_subject', [
                'vehicle' => $this->listing->composeDisplayTitle(),
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.favorite-listing-price-change',
        );
    }
}