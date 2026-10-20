<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Organization\Actions\SwitchCurrentOrganization;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SwitchOrganizationController extends Controller
{
    public function __invoke(Request $request, Organization $organization, SwitchCurrentOrganization $switch): RedirectResponse
    {
        Gate::authorize('switchTo', $organization);

        $switch->handle($request->session(), $organization);

        return back();
    }
}
