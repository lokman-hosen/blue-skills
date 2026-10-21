<?php

namespace App\Domain\Trust\Data;

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Mobility\States\MobilityCase\Open;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\States\Report\Open as OpenReport;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Verification\States\Evidence\VerificationPending;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Spatie\LaravelData\Data;

/**
 * The staff overview (prototype x-queue, spec §14 "Overview"): what waits in each queue the
 * viewer can open, and where the marketplace stands.
 */
class AdminOverviewData extends Data
{
    /**
     * @param  list<array{key: string, count: int}>  $queues  Only queues the viewer may open.
     * @param  array<string, int>  $marketplace
     */
    public function __construct(
        public array $queues,
        public array $marketplace,
        public int $corridorsPendingLegalReview,
    ) {}

    public static function for(User $staff): self
    {
        $openReviews = fn (VerificationType $type) => VerificationRequest::query()->where('type', $type)->whereIn('status', ['open', 'in_review'])->count();
        $can = fn (Permission $permission) => $staff->hasStaffPermission($permission->value);

        $queues = array_values(array_filter([
            $can(Permission::VerificationsReview) ? ['key' => 'organizations', 'count' => $openReviews(VerificationType::Organization)] : null,
            $can(Permission::VerificationsReview) ? ['key' => 'agencies', 'count' => $openReviews(VerificationType::AgencyLicence)] : null,
            $can(Permission::VerificationsReview) ? ['key' => 'institutes', 'count' => $openReviews(VerificationType::InstituteAccreditation)] : null,
            $can(Permission::VerificationsReview) ? ['key' => 'mandates', 'count' => $openReviews(VerificationType::JobMandate)] : null,
            $can(Permission::JobsModerate) ? ['key' => 'jobs', 'count' => $openReviews(VerificationType::Job)] : null,
            $can(Permission::VerificationsReview) ? ['key' => 'evidence', 'count' => EvidenceItem::query()
                ->whereState('status', [UploadedUnreviewed::class, VerificationPending::class])
                ->where('documentable_type', (new Document)->getMorphClass())->count()] : null,
            $can(Permission::SkillShowsModerate) ? ['key' => 'skillshows', 'count' => SkillShow::query()->where('moderation_status', ModerationStatus::Pending)->count()] : null,
            $can(Permission::TrustManage) ? ['key' => 'reports', 'count' => Report::query()->whereState('status', OpenReport::class)->count()] : null,
        ]));

        $verified = fn (OrganizationType $type) => Organization::query()->where('type', $type)->whereState('verification_status', Verified::class)->count();

        return new self(
            queues: $queues,
            marketplace: [
                'workers' => WorkerProfile::query()->count(),
                'profiles_complete' => WorkerProfile::query()->whereNotNull('profile_completed_at')->count(),
                'discoverable' => WorkerProfile::query()->discoverable()->count(),
                'employers' => $verified(OrganizationType::Employer),
                'agencies' => $verified(OrganizationType::Agency),
                'institutes' => $verified(OrganizationType::Institute),
                'live_jobs' => JobPosting::query()->whereState('status', Published::class)->count(),
                'applications_30d' => Application::query()->where('applied_at', '>=', now()->subDays(30))->count(),
                'offers_accepted' => Application::query()->whereState('status', Accepted::class)->count(),
                'cases_open' => MobilityCase::query()->whereState('status', Open::class)->count(),
                'joined' => MobilityCase::query()->whereState('status', Completed::class)->count(),
            ],
            corridorsPendingLegalReview: Country::query()->where('enabled_as_destination', true)->whereNull('legal_reviewed_at')->count(),
        );
    }
}
