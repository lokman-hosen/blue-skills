<?php

namespace App\Domain\Organization\Mail;

use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells organization owners the outcome of their registration review and how to act on it.
 */
class OrganizationReviewDecidedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public VerificationRequest $review,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("organization.review_mail.subject.{$this->review->decision?->value}", ['organization' => $this->organization->display_name]),
        );
    }

    public function content(): Content
    {
        $reason = ReviewReason::tryFrom((string) $this->review->reason_code);

        return new Content(
            markdown: 'mail.organization-review-decided',
            with: [
                'organization' => $this->organization->display_name,
                'decision' => $this->review->decision?->value,
                'reason' => $reason !== null ? __("review_reason.{$reason->value}") : null,
                'note' => $this->review->decision_note,
                'url' => route('organizations.verification.edit'),
            ],
        );
    }
}
