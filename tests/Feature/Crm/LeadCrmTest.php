<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Crm\Enums\FollowUpStatus;
use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Lead;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->agent = staffUser(StaffRole::CrmAgent);
});

it('lists leads without phone numbers and filters them', function () {
    Lead::factory()->create(['name' => 'Karim Ahmed', 'next_follow_up_at' => now()->subDay()]);
    Lead::factory()->create(['name' => 'Sunil Das', 'follow_up_status' => FollowUpStatus::NotInterested]);

    $this->actingAs($this->agent)->get(route('admin.crm.index', ['view' => 'due']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/crm/index')
            ->has('leads.data', 1)
            ->where('leads.data.0.name', 'Karim Ahmed')
            ->where('leads.data.0.overdue', true)
            ->missing('leads.data.0.phone'));

    $this->actingAs($this->agent)->get(route('admin.crm.index', ['status' => 'not_interested']))
        ->assertInertia(fn (Assert $page) => $page->has('leads.data', 1)->where('leads.data.0.name', 'Sunil Das'));
});

it('keeps the CRM to CRM agents and superusers', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(staffUser(StaffRole::FinanceOfficer))->get(route('admin.crm.index'))->assertForbidden();
    $this->actingAs(staffUser(StaffRole::TrustOfficer))->get(route('admin.crm.leads.show', $lead))->assertForbidden();
    $this->actingAs(staffUser(StaffRole::Superuser))->get(route('admin.crm.leads.show', $lead))->assertOk();
});

it('adds a lead met at a job fair and refuses a number that is already a lead', function () {
    $this->actingAs($this->agent)->post(route('admin.crm.leads.store'), [
        'name' => 'Abdul Karim', 'country' => 'BD', 'phone' => '01712 000999', 'source' => 'walk_in', 'assign_to_me' => true,
    ])->assertRedirect();

    $lead = Lead::query()->sole();
    expect($lead->phone)->toBe('+8801712000999')
        ->and($lead->source)->toBe(LeadSource::WalkIn)
        ->and($lead->assigned_to)->toBe($this->agent->id);

    $this->actingAs($this->agent)->post(route('admin.crm.leads.store'), [
        'name' => 'Someone Else', 'country' => 'BD', 'phone' => '+8801712000999', 'source' => 'call_centre',
    ])->assertSessionHasErrors('phone');

    $this->actingAs($this->agent)->post(route('admin.crm.leads.store'), [
        'name' => '', 'country' => 'BD', 'phone' => '123', 'source' => 'campaign',
    ])->assertSessionHasErrors(['name', 'phone', 'campaign_id']);
});

it('shows the phone on the lead page and logs every view, but not for do-not-contact leads', function () {
    $lead = Lead::factory()->create(['phone' => '+8801712000111']);

    $this->actingAs($this->agent)->get(route('admin.crm.leads.show', $lead))
        ->assertInertia(fn (Assert $page) => $page->component('admin/crm/lead')->where('lead.phone', '+8801712000111'));

    expect(Activity::query()->where('description', 'lead.phone_viewed')->where('causer_id', $this->agent->id)->count())->toBe(1);

    $lead->update(['follow_up_status' => FollowUpStatus::DoNotContact]);
    $this->actingAs($this->agent)->get(route('admin.crm.leads.show', $lead))
        ->assertInertia(fn (Assert $page) => $page->where('lead.phone', null));

    expect(Activity::query()->where('description', 'lead.phone_viewed')->count())->toBe(1);
});

it('logs a contact attempt and moves the follow-up to match', function () {
    $lead = Lead::factory()->create();

    $this->actingAs($this->agent)->post(route('admin.crm.leads.attempts.store', $lead), [
        'channel' => 'phone_call', 'outcome' => ContactOutcome::CallBack->value, 'note' => 'At work, call after 6pm.',
        'next_follow_up_at' => now()->addDay()->toDateString(),
    ])->assertRedirect(route('admin.crm.leads.show', $lead));

    $lead->refresh();
    expect($lead->follow_up_status)->toBe(FollowUpStatus::CallBack)
        ->and($lead->assigned_to)->toBe($this->agent->id)
        ->and($lead->last_contacted_at)->not->toBeNull()
        ->and($lead->contactAttempts()->count())->toBe(1);

    $this->actingAs($this->agent)->post(route('admin.crm.leads.attempts.store', $lead), ['channel' => 'fax', 'outcome' => 'reached'])
        ->assertSessionHasErrors('channel');

    $lead->update(['follow_up_status' => FollowUpStatus::DoNotContact]);
    $this->actingAs($this->agent)->post(route('admin.crm.leads.attempts.store', $lead), ['channel' => 'sms', 'outcome' => 'reached'])
        ->assertSessionHasErrors('outcome');
});

it('updates the follow-up but keeps an opted-out worker do-not-contact', function () {
    $lead = Lead::factory()->create();

    $this->actingAs($this->agent)->put(route('admin.crm.leads.update', $lead), [
        'assigned_to' => $this->agent->id, 'follow_up_status' => 'call_back', 'next_follow_up_at' => now()->addDays(2)->toDateString(),
    ])->assertRedirect();
    expect($lead->fresh()?->follow_up_status)->toBe(FollowUpStatus::CallBack);

    $profile = WorkerProfile::factory()->create(['staff_contact_opted_out_at' => now()]);
    $optedOut = Lead::factory()->forWorker($profile->user)->create(['follow_up_status' => FollowUpStatus::DoNotContact]);

    $this->actingAs($this->agent)->put(route('admin.crm.leads.update', $optedOut), ['follow_up_status' => 'contacted'])
        ->assertSessionHasErrors('follow_up_status');
    expect($optedOut->fresh()?->follow_up_status)->toBe(FollowUpStatus::DoNotContact);
});

it('ticks only the stages the worker really reached', function () {
    $profile = WorkerProfile::factory()->create(['profile_completed_at' => now()]);
    Application::factory()->create(['worker_profile_id' => $profile->id]);
    $lead = Lead::factory()->forWorker($profile->user)->create(['conversion_stage' => ConversionStage::Applied]);

    $this->actingAs($this->agent)->get(route('admin.crm.leads.show', $lead))
        ->assertInertia(fn (Assert $page) => $page
            ->where('lead.progress.1', ['stage' => 'profile_complete', 'reached' => true])
            ->where('lead.progress.2', ['stage' => 'qualified', 'reached' => false])
            ->where('lead.progress.3', ['stage' => 'applied', 'reached' => true]));
});
