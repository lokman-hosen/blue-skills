<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;

/**
 * The destinations an agency says its licence covers. Staff check them against the licence
 * when they review each mandate (reason `licence_out_of_scope`).
 */
class UpdateLicenceCountries
{
    /**
     * @param  list<string>  $countries
     */
    public function handle(Organization $agency, array $countries, User $actor): void
    {
        $before = $agency->licence_countries ?? [];
        $agency->update(['licence_countries' => array_values(array_unique($countries))]);

        activity('organization')->performedOn($agency)->causedBy($actor)
            ->withProperties(['old' => ['licence_countries' => $before], 'attributes' => ['licence_countries' => $agency->licence_countries]])
            ->log('licence.countries_updated');
    }
}
