<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Mail\JobReviewDecidedMail;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\MoreInformationRequested;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Rejected;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\VerificationType;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
    Mail::fake();

    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->owner = User::factory()->create();
    $this->employer->users()->attach($this->owner->id, ['joined_at' => now()]);
    TeamContext::run($this->employer->id, fn () => $this->owner->assignRole(OrganizationRole::Owner->value));

    $this->job = JobPosting::factory()->create([
        'employer_organization_id' => $this->employer->id,
        'destination_country_code' => 'AE',
        'status' => Submitted::class,
        'submitted_at' => now(),
    ]);
    $this->review = $this->job->verificationRequests()->create(['type' => VerificationType::Job]);
    $this->moderator = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::TrustOfficer);
});

it('shows the queue and the Truth Card to job moderators', function () {
    $this->actingAs($this->moderator)->get(route('admin.jobs.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/jobs/index')->has('reviews', 1)->where('reviews.0.problemCount', 0));

    $this->get(route('admin.jobs.show', $this->review))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/jobs/show')
            ->where('job.publicCode', $this->job->public_code)
            ->where('decidable', true)
            ->has('reasons', 6));
});

it('publishes a job and emails the employer', function () {
    $this->actingAs($this->moderator)->patch(route('admin.jobs.decide', $this->review), ['decision' => 'approved'])
        ->assertRedirect(route('admin.jobs.index'));

    expect($this->job->refresh()->status)->toBeInstanceOf(Published::class)
        ->and($this->job->published_at)->not->toBeNull();
    Mail::assertQueued(JobReviewDecidedMail::class, fn ($mail) => $mail->hasTo($this->owner->email));
});

it('asks for changes or rejects with a reason the employer sees', function () {
    $this->actingAs($this->moderator)->patch(route('admin.jobs.decide', $this->review), [
        'decision' => 'needs_info', 'reason' => 'conditions_unclear', 'note' => 'State the shift pattern.',
    ]);

    expect($this->job->refresh()->status)->toBeInstanceOf(MoreInformationRequested::class);

    $this->actingAs($this->owner)->withSession(['current_organization_id' => $this->employer->id])
        ->get(route('org.jobs.show', $this->job))
        ->assertInertia(fn (Assert $page) => $page->where('lastDecision.reasonCode', 'conditions_unclear')->where('lastDecision.note', 'State the shift pattern.'));

    $second = $this->job->verificationRequests()->create(['type' => VerificationType::Job]);
    $this->job->forceFill(['status' => Submitted::class])->save();

    $this->actingAs($this->moderator)->patch(route('admin.jobs.decide', $second), ['decision' => 'rejected', 'reason' => 'duplicate_job']);
    expect($this->job->refresh()->status)->toBeInstanceOf(Rejected::class);
});

it('refuses to publish a job that breaks the corridor rules', function () {
    $this->job->costs()->create(['category' => JobCostCategory::RecruitmentFee, 'amount_minor' => 500000, 'currency' => 'AED', 'payer' => CostPayer::Worker]);

    $this->actingAs($this->moderator)->patch(route('admin.jobs.decide', $this->review), ['decision' => 'approved'])
        ->assertSessionHasErrors('costs');

    expect($this->job->refresh()->status)->toBeInstanceOf(Submitted::class)
        ->and($this->review->refresh()->decision)->toBeNull();
});

it('keeps job review to staff with the moderation permission', function () {
    $crm = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::CrmAgent);

    $this->actingAs($crm)->get(route('admin.jobs.index'))->assertForbidden();
    $this->actingAs($crm)->patch(route('admin.jobs.decide', $this->review), ['decision' => 'approved'])->assertForbidden();
    $this->actingAs($this->owner)->patch(route('admin.jobs.decide', $this->review), ['decision' => 'approved'])->assertForbidden();

    expect($this->job->refresh()->status)->toBeInstanceOf(Submitted::class);
});
