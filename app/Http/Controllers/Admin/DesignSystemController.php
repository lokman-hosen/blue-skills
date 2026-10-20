<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Jobs\Data\JobTruthCardData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Data\OrganizationSummaryData;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff-only visual review of every BLUESKILLS component and layout (/_design).
 * Uses the seeded sample world when it exists.
 */
class DesignSystemController extends Controller
{
    public const LAYOUTS = ['public', 'auth', 'worker', 'org', 'admin'];

    public function index(): Response
    {
        $jobs = JobPosting::query()
            ->whereIn('public_code', ['UAE-EL-0001', 'PL-WH-0014', 'JP-IT-0007'])
            ->orderBy('public_code', 'desc')
            ->get();

        return Inertia::render('admin/design-system', [
            'truthCards' => $jobs->map(fn (JobPosting $job) => JobTruthCardData::fromJob($job))->values()->all(),
            'layouts' => self::LAYOUTS,
        ]);
    }

    public function layout(string $layout): Response
    {
        $sampleOrganization = Organization::query()->where('display_name', 'ABC Contracting')->first()
            ?? Organization::query()->first();

        $props = ['layout' => $layout];

        if ($layout === 'org' && $sampleOrganization !== null) {
            $props['currentOrganization'] = OrganizationSummaryData::fromOrganization($sampleOrganization);
            $props['organizations'] = Organization::query()->limit(3)->get()
                ->map(fn (Organization $organization) => OrganizationSummaryData::fromOrganization($organization))
                ->values()
                ->all();
        }

        return Inertia::render("{$layout}/design-preview", $props);
    }
}
