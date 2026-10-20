<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use Illuminate\Contracts\Session\Session;

class SwitchCurrentOrganization
{
    public function handle(Session $session, Organization $organization): void
    {
        $session->put(CurrentOrganization::SESSION_KEY, $organization->id);
    }
}
