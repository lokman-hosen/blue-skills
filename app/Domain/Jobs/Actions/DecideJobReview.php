<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Mail\JobReviewDecidedMail;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\MoreInformationRequested;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Rejected;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Jobs\States\Job\UnderReview;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Organization\Support\OrganizationRoles;
use App\Domain\Verification\Actions\RecordReviewDecision;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Staff publish a job, ask for changes or reject it (spec §5, §10). Approval re-checks the
 * employer and the corridor rules, so nothing unlawful is published even if rules changed.
 */
class DecideJobReview
{
    public function __construct(
        private readonly RecordReviewDecision $record,
        private readonly OrganizationRoles $roles,
    ) {}

    public function handle(VerificationRequest $review, User $reviewer, VerificationDecision $decision, ?ReviewReason $reason, ?string $note): JobPosting
    {
        $job = $review->subject;
        abort_unless($job instanceof JobPosting, 404);

        if ($decision === VerificationDecision::Approved) {
            $problems = JobCompliance::problems($job);

            if (! $job->organization->isVerified()) {
                $problems['job'] = __('jobs.submit.organization_not_verified');
            }

            if ($problems !== []) {
                throw ValidationException::withMessages($problems);
            }
        }

        DB::transaction(function () use ($review, $job, $reviewer, $decision, $reason, $note): void {
            $this->record->handle($review, $reviewer, $decision, $reason, $note);

            if ($job->status->equals(Submitted::class)) {
                $job->status->transitionTo(UnderReview::class);
            }

            $job->status->transitionTo(match ($decision) {
                VerificationDecision::Approved => Published::class,
                VerificationDecision::NeedsInfo => MoreInformationRequested::class,
                VerificationDecision::Rejected => Rejected::class,
            });

            if ($decision === VerificationDecision::Approved) {
                $job->forceFill(['published_at' => $job->published_at ?? now()])->save();
            }
        });

        $owners = $job->organization->users->filter(fn (User $member) => $this->roles->roleOf($member, $job->organization)?->isOwner() === true);

        foreach ($owners as $owner) {
            Mail::to($owner)->send(new JobReviewDecidedMail($job, $review->refresh()));
        }

        return $job;
    }
}
