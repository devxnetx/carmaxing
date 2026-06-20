<?php

namespace App\Mail;

use App\Models\SavedSearch;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SavedSearchAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SavedSearch $savedSearch,
        public int $newMatches,
        public int $totalMatches,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.saved_search_alert_subject', ['name' => $this->savedSearch->name ?: config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.saved-search-alert',
        );
    }
}