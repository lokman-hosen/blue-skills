<?php

namespace App\Domain\Jobs\Mail;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the employer's owners whether a job was published, sent back or rejected.
 */
class JobReviewDecidedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public JobPosting $job,
        public VerificationRequest $review,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("jobs.review_mail.subject.{$this->review->decision?->value}", ['title' => $this->job->title, 'code' => $this->job->public_code]),
        );
    }

    public function content(): Content
    {
        $reason = ReviewReason::tryFrom((string) $this->review->reason_code);

        return new Content(
            markdown: 'mail.job-review-decided',
            with: [
                'title' => $this->job->title,
                'code' => $this->job->public_code,
                'decision' => $this->review->decision?->value,
                'reason' => $reason !== null ? __("review_reason.{$reason->value}") : null,
                'note' => $this->review->decision_note,
                'url' => route('org.jobs.show', $this->job),
            ],
        );
    }
}
