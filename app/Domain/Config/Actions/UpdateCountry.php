<?php

namespace App\Domain\Config\Actions;

use App\Domain\Config\Models\Country;
use App\Domain\Identity\Models\User;

/**
 * Currency, whether workers can come from / go to the country, and the corridor legal review
 * (D-088, D-093).
 */
class UpdateCountry
{
    /**
     * @param  array{currency: string, enabled_as_source: bool, enabled_as_destination: bool, legal_reviewed: bool}  $data
     */
    public function handle(Country $country, array $data, User $actor): void
    {
        $old = [
            'currency' => $country->currency,
            'enabled_as_source' => $country->enabled_as_source,
            'enabled_as_destination' => $country->enabled_as_destination,
            'legal_reviewed' => $country->legal_reviewed_at !== null,
        ];

        $reviewChanged = $old['legal_reviewed'] !== $data['legal_reviewed'];

        $country->update([
            'currency' => strtoupper($data['currency']),
            'enabled_as_source' => $data['enabled_as_source'],
            'enabled_as_destination' => $data['enabled_as_destination'],
            ...($reviewChanged ? [
                'legal_reviewed_at' => $data['legal_reviewed'] ? now() : null,
                'legal_reviewed_by' => $data['legal_reviewed'] ? $actor->id : null,
            ] : []),
        ]);

        activity('config')->performedOn($country)->causedBy($actor)
            ->withProperties(['old' => $old, 'attributes' => $data])
            ->log('country.updated');
    }
}
