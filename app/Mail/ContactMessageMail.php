<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->contactMessage->subject
            ?: __('pages.contact.email_subject_default', ['name' => $this->contactMessage->name]);

        return new Envelope(
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.contact-message-html',
            text: 'emails.contact-message',
        );
    }
}