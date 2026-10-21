<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Organization\Actions\UpdateLicenceCountries;
use App\Domain\Organization\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateLicenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The agency's recruiting licence: number, expiry, verification and destinations covered.
 */
class LicenceController extends Controller
{
    use ResolvesCurrentOrganization;

    public function edit(Request $request): Response
    {
        $agency = $this->organization($request);
        abort_unless($agency->type === OrganizationType::Agency, 404);
        Gate::authorize('viewAny', [JobMandate::class, $agency]);

        return Inertia::render('org/licence', [
            'licence' => [
                'number' => $agency->licence_number,
                'expiresOn' => $agency->licence_expires_on?->toDateString(),
                'verified' => $agency->isVerified(),
                'valid' => $agency->hasValidLicence(),
                'countries' => $agency->licence_countries ?? [],
            ],
            'countries' => Country::query()->where('enabled_as_destination', true)->orderBy('name')->get()
                ->map(fn (Country $country) => CountryOptionData::fromCountry($country))->values()->all(),
            'canEdit' => Gate::allows('create', [JobMandate::class, $agency]),
        ]);
    }

    public function update(UpdateLicenceRequest $request, UpdateLicenceCountries $update): RedirectResponse
    {
        $agency = $this->organization($request);
        abort_unless($agency->type === OrganizationType::Agency, 404);
        Gate::authorize('create', [JobMandate::class, $agency]);

        /** @var list<string> $countries */
        $countries = $request->array('countries');
        $update->handle($agency, $countries, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('licence.saved')]);

        return to_route('org.licence.edit');
    }
}
