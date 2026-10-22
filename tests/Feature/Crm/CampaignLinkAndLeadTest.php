<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Models\CampaignVisit;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\States\Campaign\Paused;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class]);
    $this->campaign = Campaign::factory()->create(['code' => 'DHAKA-FAIR']);
});

function registerWorkerFromSession(array $session = []): void
{
    test()->withSession([
        'worker_auth.verified_phone' => '+8801712345678',
        'worker_auth.verified_at' => now()->timestamp,
        ...$session,
    ])->post(route('worker.details.store'), ['name' => 'Jamal Hossain', 'country' => 'BD'])->assertRedirect();
}

it('counts a campaign visit once per session and sends people to sign up', function () {
    $this->get('/c/dhaka-fair')->assertRedirect(route('worker.join'));
    $this->get('/c/DHAKA-FAIR')->assertRedirect(route('worker.join'));

    expect(CampaignVisit::query()->where('campaign_id', $this->campaign->id)->value('visits'))->toBe(1)
        ->and(session('crm_campaign'))->toBe($this->campaign->id);
});

it('ignores paused campaigns and unknown codes', function () {
    $this->campaign->update(['status' => Paused::class]);

    $this->get('/c/DHAKA-FAIR')->assertRedirect(route('worker.join'));
    $this->get('/c/NOPE')->assertRedirect(route('home'));

    expect(CampaignVisit::query()->count())->toBe(0)
        ->and(session('crm_campaign'))->toBeNull();
});

it('makes every new worker a lead tied to the campaign that brought them', function () {
    $this->get('/c/DHAKA-FAIR');
    registerWorkerFromSession(['crm_campaign' => $this->campaign->id]);

    $lead = User::query()->where('phone', '+8801712345678')->firstOrFail()->lead;

    expect($lead?->source)->toBe(LeadSource::Campaign)
        ->and($lead?->campaign_id)->toBe($this->campaign->id)
        ->and($lead?->conversion_stage)->toBe(ConversionStage::Registered)
        ->and($lead?->follow_up_status)->toBe(FollowUpStatus::New)
        ->and(session('crm_campaign'))->toBeNull();
});

it('credits the institute when a worker signs up from a cohort invite', function () {
    $institute = Organization::factory()->institute()->verified()->create();
    $cohort = Cohort::factory()->create(['organization_id' => $institute->id]);
    $cohort->course->update(['organization_id' => $institute->id]);

    registerWorkerFromSession(['cohort_invite' => $cohort->invite_code]);

    $lead = Lead::query()->sole();
    expect($lead->source)->toBe(LeadSource::Institute)
        ->and($lead->partner_organization_id)->toBe($institute->id);
});

it('links a hand-entered lead with the same phone instead of duplicating it', function () {
    $handEntered = Lead::factory()->create(['phone' => '+8801712345678']);

    registerWorkerFromSession();

    expect(Lead::query()->count())->toBe(1)
        ->and($handEntered->fresh()?->user_id)->not->toBeNull()
        ->and($handEntered->fresh()?->source)->toBe(LeadSource::WalkIn);
});

it('moves the lead stage forward from what the worker does, never back', function () {
    $profile = WorkerProfile::factory()->create();
    $lead = Lead::factory()->forWorker($profile->user)->create();

    $profile->update(['profile_completed_at' => now()]);
    expect($lead->fresh()?->conversion_stage)->toBe(ConversionStage::ProfileComplete);

    $application = Application::factory()->create(['worker_profile_id' => $profile->id]);
    expect($lead->fresh()?->conversion_stage)->toBe(ConversionStage::Applied);

    $application->delete();
    $profile->update(['headline' => 'Electrician']);
    expect($lead->fresh()?->conversion_stage)->toBe(ConversionStage::Applied);
});

it('lets a worker turn off calls from BLUESKILLS, which marks the lead do-not-contact', function () {
    $profile = WorkerProfile::factory()->create();
    $lead = Lead::factory()->forWorker($profile->user)->create(['next_follow_up_at' => now()->addDay()]);

    $this->actingAs($profile->user)->put(route('worker.privacy.staff-contact.update'), ['allowed' => false])
        ->assertRedirect(route('worker.privacy.show'));

    expect($profile->fresh()?->staff_contact_opted_out_at)->not->toBeNull()
        ->and($lead->fresh()?->follow_up_status)->toBe(FollowUpStatus::DoNotContact)
        ->and($lead->fresh()?->next_follow_up_at)->toBeNull();

    $this->actingAs($profile->user)->put(route('worker.privacy.staff-contact.update'), ['allowed' => true]);
    expect($lead->fresh()?->follow_up_status)->toBe(FollowUpStatus::New);

    $this->actingAs($profile->user)->put(route('worker.privacy.staff-contact.update'), [])->assertSessionHasErrors('allowed');
});

it('keeps organization users off the worker privacy setting', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('worker.privacy.staff-contact.update'), ['allowed' => false])
        ->assertForbidden();
});
