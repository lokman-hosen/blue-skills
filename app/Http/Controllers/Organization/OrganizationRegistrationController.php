<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Organization\Actions\RegisterOrganization;
use App\Domain\Organization\Actions\SwitchCurrentOrganization;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\RegisterOrganizationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationRegistrationController extends Controller
{
    public function create(): Response
    {
        Gate::authorize('create', Organization::class);

        return Inertia::render('onboarding/organization', [
            'countries' => Country::query()->orderBy('name')->get()
                ->map(fn (Country $country) => CountryOptionData::fromCountry($country))
                ->values()
                ->all(),
        ]);
    }

    public function store(RegisterOrganizationRequest $request, RegisterOrganization $register, SwitchCurrentOrganization $switch): RedirectResponse
    {
        $organization = $register->handle($request->user(), $request->toData());
        $switch->handle($request->session(), $organization);

        return to_route('organizations.verification.edit');
    }
}
