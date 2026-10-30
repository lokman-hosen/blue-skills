<?php

namespace App\Domain\Identity\Mail;

use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Support\RoleDirectory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaffInvitationMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public StaffInvitation $invitation,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('staff.mail.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.staff-invitation',
            with: [
                'inviter' => $this->invitation->inviter->name ?? 'BLUESKILLS',
                'role' => app(RoleDirectory::class)->label($this->invitation->role),
                'url' => route('staff-invitations.show', $this->token),
                'expires' => $this->invitation->expires_at->toFormattedDayDateString(),
            ],
        );
    }
}
