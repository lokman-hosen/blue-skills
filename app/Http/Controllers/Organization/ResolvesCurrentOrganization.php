<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use Illuminate\Http\Request;

trait ResolvesCurrentOrganization
{
    /**
     * The workspace the member is working in. Records are only reached through it.
     */
    protected function organization(Request $request): Organization
    {
        $organization = app(CurrentOrganization::class)->resolve($request->user(), $request->session());
        abort_if($organization === null, 403);

        return $organization;
    }
}
