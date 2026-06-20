<?php

namespace App\Mail;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ListingInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Listing $listing,
        public User $buyer,
        public string $inquiryMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->buyer->email, $this->buyer->name)],
            subject: __('messages.inquiry_email_subject', ['title' => $this->listing->composeDisplayTitle()]),
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.listing-inquiry-html',
            text: 'emails.listing-inquiry',
        );
    }
}