<?php

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\TrustAction;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Worker\Actions\UpdateProfileVisibility;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
});

function staffWith(StaffRole $role): User
{
    $user = User::factory()->staff()->create();
    $user->assignStaffRole($role);

    return $user;
}

it('numbers cases and keeps actions and notes immutable', function () {
    $first = TrustCase::factory()->create();
    $second = TrustCase::factory()->create();

    expect($first->reference)->toBe('TC-'.now()->year.'-0001')
        ->and($second->reference)->toBe('TC-'.now()->year.'-0002');

    $action = TrustAction::factory()->create();
    expect(fn () => $action->update(['note' => 'changed']))->toThrow(LogicException::class);
});

it('lets trust officers run cases and keeps appeals away from the officer who suspended', function () {
    $officer = staffWith(StaffRole::TrustOfficer);
    $other = staffWith(StaffRole::TrustOfficer);
    $verifier = staffWith(StaffRole::VerificationOfficer);
    $appeal = Appeal::factory()->create(['trust_action_id' => TrustAction::factory()->create(['actor_id' => $officer->id])]);

    expect(Gate::forUser($officer)->allows('viewAny', TrustCase::class))->toBeTrue()
        ->and(Gate::forUser($verifier)->allows('viewAny', TrustCase::class))->toBeFalse()
        ->and(Gate::forUser($officer)->allows('decide', $appeal))->toBeFalse()
        ->and(Gate::forUser($other)->allows('decide', $appeal))->toBeTrue();
});

it('hides suspended workers from talent search', function () {
    $worker = makeWorker(['occupation_id' => Occupation::factory()->create()->id]);
    app(UpdateProfileVisibility::class)->handle($worker->workerProfile, ProfileVisibility::EmployersAndAgencies);

    expect(WorkerProfile::query()->discoverable()->count())->toBe(1);
    $worker->workerProfile->update(['suspended_at' => now()]);
    expect(WorkerProfile::query()->discoverable()->count())->toBe(0);
});
