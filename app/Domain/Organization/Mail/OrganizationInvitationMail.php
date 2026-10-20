<?php

namespace App\Domain\Organization\Mail;

use App\Domain\Organization\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Carries the raw invitation token, so the queued payload is encrypted.
 */
class OrganizationInvitationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public OrganizationInvitation $invitation,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('team.mail.subject', ['organization' => $this->invitation->organization->display_name]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.organization-invitation',
            with: [
                'organization' => $this->invitation->organization->display_name,
                'inviter' => $this->invitation->inviter?->name,
                'role' => __("role.{$this->invitation->role->value}"),
                'url' => route('invitations.show', $this->token),
                'expires' => $this->invitation->expires_at->toFormattedDayDateString(),
            ],
        );
    }
}
