<?php

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\Models\ProfileSnapshot;
use App\Domain\Applications\Models\ShortlistEntry;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Applications\States\Application\Offer as OfferStage;
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

    $application->status->transitionTo(OfferStage::class);
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

it('keeps stage history, interviews, offers and revocable consents with the application', function () {
    $application = Application::factory()->create();
    ApplicationStageEvent::factory()->for($application)->create(['from_status' => 'applied', 'to_status' => 'shortlisted']);
    Interview::factory()->for($application)->create();
    Offer::factory()->for($application)->create();
    $consent = ApplicationConsent::factory()->for($application)->create(['item' => ApplicationConsentItem::SkillShow]);

    expect($application->stageEvents)->toHaveCount(1)
        ->and($application->interviews)->toHaveCount(1)
        ->and($application->openOffer()?->status)->toBe(OfferStatus::Sent)
        ->and($application->consents()->active()->count())->toBe(1);

    $consent->update(['revoked_at' => now()]);

    expect($application->consents()->active()->count())->toBe(0)
        ->and($consent->fresh()?->isActive())->toBeFalse();
});

it('never edits a stage event', function () {
    ApplicationStageEvent::factory()->create()->update(['note' => 'Changed']);
})->throws(LogicException::class, 'Application stage events are immutable.');

it('notices job changes made after the worker applied until the worker has seen them', function () {
    $application = Application::factory()->create(['applied_at' => now()->subDays(5)]);

    expect($application->hasUnseenJobChange())->toBeFalse();

    $application->job->update(['material_change_at' => now()->subDay()]);
    expect($application->refresh()->hasUnseenJobChange())->toBeTrue();

    $application->update(['job_change_seen_at' => now()]);
    expect($application->refresh()->hasUnseenJobChange())->toBeFalse();
});

it('keeps one entry per worker on a shortlist', function () {
    $entry = ShortlistEntry::factory()->create();

    expect($entry->shortlist->organization->shortlists)->toHaveCount(1);

    ShortlistEntry::factory()->create(['shortlist_id' => $entry->shortlist_id, 'worker_profile_id' => $entry->worker_profile_id]);
})->throws(UniqueConstraintViolationException::class);
