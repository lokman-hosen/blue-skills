<?php

namespace App\Domain\Messaging\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * An alert by email for organization members (D-068). Sent from DeliverOutboundMessage, which is
 * already queued.
 */
class NotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $body,
        public string $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.notification',
            with: ['title' => $this->title, 'body' => $this->body, 'url' => $this->link],
        );
    }
}
