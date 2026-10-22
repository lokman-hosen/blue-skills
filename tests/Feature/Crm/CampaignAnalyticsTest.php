<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Models\CampaignVisit;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\States\Campaign\Ended;
use App\Domain\Crm\States\Campaign\Paused;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, CountrySeeder::class]);
    $this->agent = staffUser(StaffRole::CrmAgent);
    $this->campaign = Campaign::factory()->create(['code' => 'DHAKA-FAIR']);
});

function campaignWorker(?Campaign $campaign, array $profile = []): WorkerProfile
{
    $worker = WorkerProfile::factory()->create($profile);
    Lead::factory()->forWorker($worker->user)->create(['campaign_id' => $campaign?->id]);

    return $worker;
}

it('builds each campaign funnel from what its people actually did', function () {
    CampaignVisit::query()->create(['campaign_id' => $this->campaign->id, 'day' => now()->subDays(3)->toDateString(), 'visits' => 40]);
    CampaignVisit::query()->create(['campaign_id' => $this->campaign->id, 'day' => now()->toDateString(), 'visits' => 10]);
    CampaignVisit::query()->create(['campaign_id' => $this->campaign->id, 'day' => now()->subYear()->toDateString(), 'visits' => 999]);

    campaignWorker($this->campaign);
    $complete = campaignWorker($this->campaign, ['profile_completed_at' => now()]);
    $qualified = campaignWorker($this->campaign, ['profile_completed_at' => now()]);
    SkillShow::factory()->approved()->create(['worker_profile_id' => $qualified->id]);
    Application::factory()->create(['worker_profile_id' => $complete->id]);
    $deployed = Application::factory()->create(['worker_profile_id' => $qualified->id, 'status' => Accepted::class]);
    MobilityCase::factory()->create(['application_id' => $deployed->id, 'status' => Completed::class]);
    Lead::factory()->create(['campaign_id' => $this->campaign->id]);
    campaignWorker(null);

    $this->actingAs($this->agent)->get(route('admin.marketing.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/marketing/index')
            ->where('campaigns.0.funnel', [
                'visitors' => 50, 'leads' => 4, 'registrations' => 3, 'profilesComplete' => 2,
                'qualified' => 1, 'applied' => 2, 'selected' => 1, 'deployed' => 1,
            ])
            ->where('total.visitors', 50)
            ->where('withoutCampaign.registrations', 1));
});

it('creates a campaign with a fixed code, edits it and moves it through its states', function () {
    $this->actingAs($this->agent)->post(route('admin.marketing.store'), [
        'name' => 'Facebook Bangladesh', 'channel' => 'social', 'source_country_code' => 'BD', 'landing' => 'jobs',
        'starts_on' => now()->toDateString(),
    ])->assertRedirect();

    $campaign = Campaign::query()->where('code', 'FACEBOOK-BANGLADESH')->sole();

    $this->actingAs($this->agent)->put(route('admin.marketing.update', $campaign), [
        'name' => 'Facebook BD (renamed)', 'channel' => 'social', 'landing' => 'join', 'starts_on' => now()->toDateString(),
    ])->assertRedirect();
    expect($campaign->fresh()?->code)->toBe('FACEBOOK-BANGLADESH');

    $this->actingAs($this->agent)->post(route('admin.marketing.status', [$campaign, 'resume']))->assertSessionHasErrors('status');
    $this->actingAs($this->agent)->post(route('admin.marketing.status', [$campaign, 'pause']))->assertRedirect();
    expect($campaign->fresh()?->status)->toBeInstanceOf(Paused::class);
    $this->actingAs($this->agent)->post(route('admin.marketing.status', [$campaign, 'end']));
    expect($campaign->fresh()?->status)->toBeInstanceOf(Ended::class)
        ->and($campaign->fresh()?->ends_on?->toDateString())->toBe(now()->toDateString());

    $this->actingAs($this->agent)->post(route('admin.marketing.store'), [
        'name' => '', 'channel' => 'billboard', 'landing' => 'join', 'starts_on' => '2026-05-01', 'ends_on' => '2026-04-01',
    ])->assertSessionHasErrors(['name', 'channel', 'ends_on']);
});

it('shows a campaign with its link and QR code, and keeps marketing to CRM staff', function () {
    $this->actingAs($this->agent)->get(route('admin.marketing.show', $this->campaign))
        ->assertInertia(fn (Assert $page) => $page->component('admin/marketing/show')
            ->where('campaign.link', route('campaign-link.show', 'DHAKA-FAIR'))
            ->where('qrSvg', fn (string $svg) => str_starts_with($svg, '<svg')));

    $this->actingAs($this->agent)->get(route('admin.marketing.qr', $this->campaign))
        ->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

    $this->actingAs(staffUser(StaffRole::FinanceOfficer))->get(route('admin.marketing.index'))->assertForbidden();
    $this->actingAs(staffUser(StaffRole::VerificationOfficer))->post(route('admin.marketing.status', [$this->campaign, 'pause']))->assertForbidden();
});

it('puts due lead follow-ups on a CRM agent\'s overview', function () {
    Lead::factory()->count(2)->create(['next_follow_up_at' => now()->subHour()]);

    $this->actingAs($this->agent)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('overview.queues', [['key' => 'leads_due', 'count' => 2]]));
});
