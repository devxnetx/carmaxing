<?php

namespace App\Mail;

use App\Models\SiteNewsPost;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SiteNewsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public SiteNewsPost $post,
        public User $user,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('messages.site_news_subject', ['title' => $this->post->title]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.site-news',
        );
    }
}