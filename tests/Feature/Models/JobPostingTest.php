<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Skill;
use Spatie\Activitylog\Models\Activity;
use Spatie\ModelStates\Exceptions\TransitionNotFound;

it('lives in the jobs table and routes by public code', function () {
    $job = JobPosting::factory()->create(['public_code' => 'UAE-EL-0099']);

    expect($job->getTable())->toBe('jobs')
        ->and($job->getRouteKey())->toBe('UAE-EL-0099')
        ->and($job->getMorphClass())->toBe('job');
});

it('links poster, employer, agency and mandate', function () {
    $mandate = JobMandate::factory()->create();
    $job = JobPosting::factory()->create([
        'organization_id' => $mandate->agency_organization_id,
        'employer_organization_id' => $mandate->employer_organization_id,
        'agency_organization_id' => $mandate->agency_organization_id,
        'job_mandate_id' => $mandate->id,
    ]);

    expect($job->organization->is($mandate->agency))->toBeTrue()
        ->and($job->employer->is($mandate->employer))->toBeTrue()
        ->and($job->agency?->is($mandate->agency))->toBeTrue()
        ->and($job->mandate?->is($mandate))->toBeTrue()
        ->and($mandate->jobs->pluck('id')->all())->toBe([$job->id])
        ->and($mandate->agency->postedJobs->pluck('id')->all())->toBe([$job->id]);
});

it('itemises worker-paid costs', function () {
    $job = JobPosting::factory()->create();
    JobCost::factory()->for($job, 'job')->create(['payer' => CostPayer::Employer]);
    $workerCost = JobCost::factory()->for($job, 'job')->create(['payer' => CostPayer::Worker]);

    expect($job->workerCosts()->pluck('id')->all())->toBe([$workerCost->id]);
});

it('keeps undisclosed conditions null rather than guessing', function () {
    $job = JobPosting::factory()->create(['food' => null, 'airfare' => null, 'salary_amount_minor' => null]);

    expect($job->fresh())
        ->food->toBeNull()
        ->airfare->toBeNull()
        ->salary_amount_minor->toBeNull();
});

it('attaches required skills', function () {
    $job = JobPosting::factory()->create();
    $skill = Skill::factory()->create();
    $job->skills()->attach($skill->id, ['id' => (string) str()->ulid(), 'required' => true]);

    expect($job->skills->first()?->is($skill))->toBeTrue()
        ->and($job->skillRequirements->first()?->required)->toBeTrue();
});

it('starts as a draft and follows the review lifecycle', function () {
    $job = JobPosting::factory()->create(['status' => null]);

    expect($job->status)->toBeInstanceOf(Draft::class)
        ->and($job->status->canTransitionTo(Published::class))->toBeFalse();

    $job->status->transitionTo(Submitted::class);

    expect($job->fresh()?->status)->toBeInstanceOf(Submitted::class);
});

it('cannot skip review to publish', function () {
    JobPosting::factory()->create()->status->transitionTo(Published::class);
})->throws(TransitionNotFound::class);

it('writes status changes to the activity log', function () {
    $job = JobPosting::factory()->create();
    $job->status->transitionTo(Submitted::class);

    $activity = Activity::query()->where('subject_type', 'job')->where('subject_id', $job->id)->latest('id')->firstOrFail();

    expect($activity->attribute_changes?->get('old'))->toBe(['status' => 'draft'])
        ->and($activity->attribute_changes?->get('attributes'))->toBe(['status' => 'submitted']);
});

it('has many applications', function () {
    $job = JobPosting::factory()->create();
    Application::factory()->count(2)->for($job, 'job')->create();

    expect($job->applications)->toHaveCount(2);
});

it('verifies organizations independently of their jobs', function () {
    $org = Organization::factory()->verified()->create();

    expect($org->isVerified())->toBeTrue()
        ->and(Organization::factory()->create()->isVerified())->toBeFalse();
});
