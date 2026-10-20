<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Mail\OrganizationReviewDecidedMail;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Rejected;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Organization\Support\OrganizationRoles;
use App\Domain\Verification\Actions\RecordReviewDecision;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Staff approve, reject or ask for more on an organization registration (spec §1, D-036).
 * Approval unlocks publishing jobs; the owners are emailed every decision.
 */
class DecideOrganizationReview
{
    public function __construct(
        private readonly RecordReviewDecision $record,
        private readonly OrganizationRoles $roles,
    ) {}

    public function handle(VerificationRequest $request, User $reviewer, VerificationDecision $decision, ?ReviewReason $reason, ?string $notes): Organization
    {
        $organization = $request->subject;
        abort_unless($organization instanceof Organization, 404);

        DB::transaction(function () use ($request, $organization, $reviewer, $decision, $reason, $notes): void {
            $this->record->handle($request, $reviewer, $decision, $reason, $notes);

            match ($decision) {
                VerificationDecision::Approved => $this->verify($organization),
                VerificationDecision::Rejected => $organization->verification_status->transitionTo(Rejected::class),
                // Stays pending while the organization adds what was asked for.
                VerificationDecision::NeedsInfo => null,
            };
        });

        $owners = $organization->users->filter(fn (User $member) => $this->roles->roleOf($member, $organization)?->isOwner() === true);

        foreach ($owners as $owner) {
            Mail::to($owner)->send(new OrganizationReviewDecidedMail($organization, $request->refresh()));
        }

        return $organization;
    }

    private function verify(Organization $organization): void
    {
        $organization->verification_status->transitionTo(Verified::class);
        $organization->forceFill(['verified_at' => now()])->save();
    }
}
