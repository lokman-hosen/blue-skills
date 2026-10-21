<?php

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\Declined;
use App\Domain\Applications\States\Application\Interview as InterviewStage;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Applications\States\Application\Withdrawn;
use App\Domain\Jobs\Models\JobPosting;
use Database\Seeders\CountrySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(CountrySeeder::class);
    $this->worker = makeWorker();
    $this->application = Application::factory()->create([
        'job_id' => JobPosting::factory()->published()->create(),
        'worker_profile_id' => $this->worker->workerProfile->id,
        'applied_at' => now()->subWeek(),
    ]);
    $this->application->stageEvents()->create(['to_status' => 'applied']);
    foreach (ApplicationConsentItem::cases() as $item) {
        $this->application->consents()->create(['item' => $item, 'granted' => $item !== ApplicationConsentItem::PhoneNumber, 'granted_at' => now()]);
    }
});

it('lists and shows the worker\'s own applications', function () {
    Interview::factory()->create(['application_id' => $this->application->id]);
    $this->application->status->transitionTo(InterviewStage::class);

    $this->actingAs($this->worker)->get(route('worker.applications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('worker/applications/index')
            ->has('applications', 1)
            ->where('applications.0.nextStep', 'attend_interview')
            ->whereNot('applications.0.upcomingInterviewAt', null));

    $this->get(route('worker.applications.show', $this->application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('worker/applications/show')
            ->where('application.status.value', 'interview')
            ->where('application.stages.0.status', 'done')
            ->where('application.stages.1.status', 'skipped')
            ->where('application.stages.4.status', 'current')
            ->where('application.consents.0.revocable', false)
            ->has('application.interviews', 1));
});

it('keeps other workers and organizations out', function () {
    $this->actingAs(makeWorker())->get(route('worker.applications.show', $this->application))->assertForbidden();
    $this->actingAs(makeWorker())->post(route('worker.applications.withdraw', $this->application))->assertForbidden();

    expect($this->application->fresh()?->status)->not->toBeInstanceOf(Withdrawn::class);
});

it('withdraws an application and any open offer', function () {
    $this->application->status->transitionTo(OfferStage::class);
    $offer = Offer::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs($this->worker)->post(route('worker.applications.withdraw', $this->application))->assertRedirect();

    expect($this->application->fresh()?->status)->toBeInstanceOf(Withdrawn::class)
        ->and($offer->fresh()?->status)->toBe(OfferStatus::Withdrawn)
        ->and($this->application->stageEvents()->get()->last()?->to_status)->toBe('withdrawn');

    $this->post(route('worker.applications.withdraw', $this->application))->assertSessionHasErrors('status');
});

it('accepts or declines the open offer', function (string $decision, string $state, OfferStatus $status, bool $opensCase) {
    $this->application->status->transitionTo(OfferStage::class);
    $offer = Offer::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs($this->worker)->post(route('worker.applications.offer', [$this->application, $decision]))->assertRedirect();

    expect($this->application->fresh()?->status)->toBeInstanceOf($state)
        ->and($offer->fresh()?->status)->toBe($status)
        ->and($offer->fresh()?->responded_at)->not->toBeNull()
        // Product rule 8: only an accepted offer opens a mobility case.
        ->and($this->application->mobilityCase()->exists())->toBe($opensCase);
})->with([
    'accept' => ['accept', Accepted::class, OfferStatus::Accepted, true],
    'decline' => ['decline', Declined::class, OfferStatus::Declined, false],
]);

it('refuses to answer when no offer is open', function () {
    $this->actingAs($this->worker)->post(route('worker.applications.offer', [$this->application, 'accept']))
        ->assertSessionHasErrors('offer');
});

it('revokes one shared item and keeps the record', function () {
    $this->application->workerProfile->consents()->create([
        'purpose' => ConsentPurpose::DocumentShare, 'subject_type' => 'application', 'subject_id' => $this->application->id,
        'scope' => ['item' => 'passport_scan'], 'granted_at' => now(),
    ]);

    $this->actingAs($this->worker)->delete(route('worker.applications.items.destroy', [$this->application, 'passport_scan']))->assertRedirect();

    $consent = ApplicationConsent::query()->where('application_id', $this->application->id)->where('item', 'passport_scan')->sole();
    expect($consent->isActive())->toBeFalse()
        ->and($consent->revoked_at)->not->toBeNull()
        ->and($this->application->workerProfile->consents()->active()->count())->toBe(0);

    $this->delete(route('worker.applications.items.destroy', [$this->application, 'skill_passport']))->assertSessionHasErrors('item');
    $this->delete(route('worker.applications.items.destroy', [$this->application, 'phone_number']))->assertSessionHasErrors('item');
});

it('shows a job change made after applying until the worker has read it', function () {
    $this->application->job->update(['material_change_at' => now()->subDay()]);

    $this->actingAs($this->worker)->get(route('worker.applications.show', $this->application))
        ->assertInertia(fn (Assert $page) => $page->whereNot('application.jobChangedAt', null));

    $this->post(route('worker.applications.job-change-seen', $this->application))->assertRedirect();

    $this->get(route('worker.applications.show', $this->application))
        ->assertInertia(fn (Assert $page) => $page->where('application.jobChangedAt', null));
});
