<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Jobs\Actions\RequestMandate;
use App\Domain\Jobs\Actions\RespondToMandate;
use App\Domain\Jobs\Data\MandateData;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Organization\Actions\AttachVerificationEvidence;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Occupation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreMandateRequest;
use App\Http\Requests\Organization\StoreVerificationEvidenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mandates (D-074, product rule 10): agencies request and follow them; employers confirm or
 * decline the ones naming them.
 */
class MandateController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [JobMandate::class, $organization]);

        $mandates = $organization->type === OrganizationType::Agency ? $organization->agencyMandates() : $organization->employerMandates();

        return Inertia::render('org/mandates/index', [
            'mandates' => $mandates->with(['agency', 'employer'])->latest()->get()
                ->map(fn (JobMandate $mandate) => MandateData::fromMandate($mandate))->values()->all(),
            'side' => $organization->type === OrganizationType::Agency ? 'agency' : 'employer',
            'canRequest' => Gate::allows('create', [JobMandate::class, $organization]),
            'licenceValid' => $organization->hasValidLicence(),
        ]);
    }

    public function create(Request $request): Response
    {
        $agency = $this->organization($request);
        Gate::authorize('create', [JobMandate::class, $agency]);

        return Inertia::render('org/mandates/create', [
            'employers' => Organization::query()->where('type', OrganizationType::Employer)->whereState('verification_status', Verified::class)
                ->orderBy('display_name')->get(['id', 'display_name', 'country_code'])
                ->map(fn (Organization $employer) => ['id' => $employer->id, 'name' => $employer->display_name, 'countryCode' => $employer->country_code])->values()->all(),
            'occupations' => Occupation::query()->orderBy('slug')->get()->map(fn (Occupation $occupation) => TaxonomyOptionData::fromModel($occupation))->values()->all(),
            'countries' => Country::query()->whereIn('code', $agency->licence_countries ?? [])->orderBy('name')->get()
                ->map(fn (Country $country) => CountryOptionData::fromCountry($country))->values()->all(),
            'licenceValid' => $agency->hasValidLicence(),
        ]);
    }

    public function store(StoreMandateRequest $request, RequestMandate $requestMandate): RedirectResponse
    {
        $agency = $this->organization($request);
        Gate::authorize('create', [JobMandate::class, $agency]);

        $letter = $request->file('letter');
        abort_unless($letter instanceof UploadedFile, 422);

        $mandate = $requestMandate->handle(
            $agency,
            Organization::query()->findOrFail($request->string('employer_id')->toString()),
            $request->scope(),
            $letter,
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mandates.requested')]);

        return to_route('org.mandates.show', $mandate);
    }

    public function show(Request $request, JobMandate $mandate): Response
    {
        $organization = $this->ownMandate($request, $mandate);
        Gate::authorize('viewAny', [JobMandate::class, $organization]);

        return Inertia::render('org/mandates/show', [
            'mandate' => MandateData::fromMandate($mandate, withEvidence: true),
            'side' => $organization->id === $mandate->agency_organization_id ? 'agency' : 'employer',
            'abilities' => [
                'confirm' => Gate::allows('confirm', $mandate),
                'addEvidence' => Gate::allows('update', $mandate),
            ],
        ]);
    }

    public function respond(Request $request, JobMandate $mandate, string $answer, RespondToMandate $respond): RedirectResponse
    {
        $this->ownMandate($request, $mandate);
        Gate::authorize('confirm', $mandate);

        $validated = $request->validate(['note' => [$answer === 'decline' ? 'required' : 'nullable', 'string', 'max:500']]);
        $respond->handle($mandate, $answer === 'confirm', $validated['note'] ?? null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("mandates.{$answer}ed")]);

        return to_route('org.mandates.show', $mandate);
    }

    public function evidence(StoreVerificationEvidenceRequest $request, JobMandate $mandate, AttachVerificationEvidence $attach): RedirectResponse
    {
        $this->ownMandate($request, $mandate);
        Gate::authorize('update', $mandate);

        $file = $request->file('document');
        $review = $mandate->verificationRequests()->latest()->first();
        abort_unless($file instanceof UploadedFile && $review !== null, 422);

        $attach->handle($review, $file, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('organization.evidence_added')]);

        return to_route('org.mandates.show', $mandate);
    }

    /**
     * Mandates are reached only by their agency or their employer.
     */
    private function ownMandate(Request $request, JobMandate $mandate): Organization
    {
        $organization = $this->organization($request);
        if ($organization->id !== $mandate->agency_organization_id && $organization->id !== $mandate->employer_organization_id) {
            abort(404);
        }

        return $organization;
    }
}
