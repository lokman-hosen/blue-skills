<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Crm\Actions\OpenWorkerLead;
use App\Domain\Crm\Support\LeadAttribution;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Worker\Enums\ProfileVisibility;
use Illuminate\Support\Facades\DB;

/**
 * Creates a worker account and an empty Skill Passport after the phone was verified.
 * New profiles are visible only to jobs the worker applies to (D-039). The worker also becomes
 * a CRM lead (D-096).
 */
class RegisterWorker
{
    public function __construct(private OpenWorkerLead $openLead) {}

    public function handle(string $phone, string $name, string $countryCode, string $locale, ?LeadAttribution $attribution = null): User
    {
        return DB::transaction(function () use ($phone, $name, $countryCode, $locale, $attribution): User {
            $user = User::query()->create([
                'type' => UserType::Worker,
                'name' => $name,
                'phone' => $phone,
                'locale' => $locale,
                'data_region' => config('blueskills.default_data_region'),
            ]);
            $user->forceFill(['phone_verified_at' => now()])->save();

            $user->workerProfile()->create([
                'current_country_code' => $countryCode,
                'visibility' => ProfileVisibility::ApplicationsOnly,
            ]);

            activity('identity')->performedOn($user)->causedBy($user)->log('worker.registered');

            // Every new worker is a CRM lead tied to where they came from (spec §9).
            $this->openLead->handle($user, $attribution ?? LeadAttribution::direct());

            return $user;
        });
    }
}
