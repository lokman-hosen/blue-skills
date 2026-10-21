<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Jobs\Data\JobListItemData;
use App\Domain\Jobs\Data\MandateCoverageData;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Mobility\Data\CaseCountsData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The agency's compliance overview (D-080): licence validity, each mandate's cover, jobs that
 * lost their mandate, and cases needing attention (product rule 10).
 */
class ComplianceController extends Controller
{
    use ResolvesCurrentOrganization;

    public const LICENCE_WARNING_DAYS = 60;

    public function show(Request $request): Response
    {
        $agency = $this->organization($request);
        Gate::authorize('reviewCompliance', $agency);

        $mandates = JobMandate::query()
            ->where('agency_organization_id', $agency->id)
            ->whereIn('status', [MandateStatus::Verified, MandateStatus::Expired, MandateStatus::Revoked])
            ->with(['agency', 'employer', 'jobs', 'verificationRequests'])
            ->orderByDesc('valid_until')
            ->get();

        // Live or paused jobs whose mandate can't back them any more.
        $uncovered = $agency->postedJobs()
            ->whereState('status', [Published::class, Paused::class])
            ->with(['mandate.agency', 'employer', 'occupation', 'destinationCountry', 'verificationRequests'])
            ->withCount('applications')
            ->get()
            ->filter(fn (JobPosting $job) => array_key_exists('job_mandate_id', JobCompliance::mandateProblems($job)));

        $expires = $agency->licence_expires_on;

        return Inertia::render('org/compliance', [
            'licence' => [
                'number' => $agency->licence_number,
                'expiresOn' => $expires?->toDateString(),
                'valid' => $agency->hasValidLicence(),
                'expiringSoon' => $agency->hasValidLicence() && $expires !== null && $expires->lte(now()->addDays(self::LICENCE_WARNING_DAYS)),
                'countries' => $agency->licence_countries ?? [],
            ],
            'mandates' => $mandates->map(fn (JobMandate $mandate) => MandateCoverageData::fromMandate($mandate))->values()->all(),
            'uncoveredJobs' => $uncovered->map(fn (JobPosting $job) => JobListItemData::fromJob($job))->values()->all(),
            'cases' => CaseCountsData::forOrganization($agency),
        ]);
    }
}
