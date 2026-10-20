<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ProfileSnapshot;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Applications\States\Application\Offer;
use App\Domain\Applications\States\Application\Shortlisted;
use App\Domain\Applications\States\Application\Withdrawn;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseStage;
use Illuminate\Database\UniqueConstraintViolationException;

it('blocks duplicate applications to the same job', function () {
    $application = Application::factory()->create();

    Application::factory()->create([
        'job_id' => $application->job_id,
        'worker_profile_id' => $application->worker_profile_id,
    ]);
})->throws(UniqueConstraintViolationException::class);

it('keeps the profile snapshot immutable', function () {
    $snapshot = ProfileSnapshot::factory()->create(['data' => ['headline' => 'Electrician']]);

    $snapshot->update(['data' => ['headline' => 'Changed']]);
})->throws(LogicException::class, 'Profile snapshots are immutable.');

it('moves forward through stages and allows withdrawal', function () {
    $application = Application::factory()->create();

    expect($application->status)->toBeInstanceOf(Applied::class)
        ->and($application->status->canTransitionTo(Shortlisted::class))->toBeTrue()
        ->and($application->status->canTransitionTo(Accepted::class))->toBeFalse()
        ->and($application->status->canTransitionTo(Withdrawn::class))->toBeTrue();

    $application->status->transitionTo(Offer::class);
    $application->status->transitionTo(Accepted::class);

    expect($application->fresh()?->status)->toBeInstanceOf(Accepted::class)
        ->and($application->fresh()?->status->canTransitionTo(Withdrawn::class))->toBeFalse();
});

it('keeps the mobility case separate from the application', function () {
    $case = MobilityCase::factory()->create();
    MobilityCaseStage::factory()->for($case)->create(['key' => 'visa', 'position' => 2]);
    MobilityCaseStage::factory()->for($case)->create(['key' => 'medical', 'position' => 1]);

    expect($case->application->mobilityCase?->is($case))->toBeTrue()
        ->and($case->id)->not->toBe($case->application_id)
        ->and($case->stages->pluck('key')->all())->toBe(['medical', 'visa']);
});

it('records per-item consent and snapshot', function () {
    $application = Application::factory()->create();
    $application->snapshot()->create(['data' => ['name' => 'Test'], 'taken_at' => now()]);
    $application->consents()->create(['item' => 'passport_scan', 'granted' => false]);

    expect($application->snapshot?->data)->toBe(['name' => 'Test'])
        ->and($application->consents->first()?->granted)->toBeFalse();
});
