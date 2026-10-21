<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $this->viewer = orgMember($this->employer, OrganizationRole::Viewer);
    $this->worker = makeWorker();
    $this->worker->update(['timezone' => 'Asia/Dhaka']);
    $this->job = JobPosting::factory()->published()->create([
        'organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id,
        'title' => 'Maintenance Electrician', 'salary_amount_minor' => 2_000_00, 'salary_currency' => 'AED',
    ]);
    $this->application = Application::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => $this->worker->workerProfile->id]);
});

function latestFor($user): DatabaseNotification
{
    return $user->notifications()->latest()->firstOrFail();
}

it('tells the worker about shortlisting, interviews, offers and rejection in words and local time', function () {
    actingInOrg($this->recruiter, $this->employer)
        ->post(route('org.applicants.move'), ['application_ids' => [$this->application->id], 'stage' => 'shortlisted']);
    expect(latestFor($this->worker)->type)->toBe('shortlisted');
    $this->travel(1)->minute();

    $this->post(route('org.applicants.interviews.store', $this->application), [
        'scheduled_at' => now($this->recruiter->timezone ?: 'UTC')->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i'),
        'duration_minutes' => 30, 'mode' => 'video',
    ]);
    expect(latestFor($this->worker)->type)->toBe('interview_scheduled');
    $this->travel(1)->minute();

    $this->post(route('org.applicants.offers.store', $this->application), ['salary_amount' => 2000]);
    expect(latestFor($this->worker)->type)->toBe('offer_received');
    $this->travel(1)->minute();

    $this->post(route('org.applicants.reject', $this->application), ['reason' => 'positions_filled']);

    $this->actingAs($this->worker)->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->has('notifications.data', 4)
            ->where('notifications.data.0.title', 'Application closed')
            ->where('notifications.data.0.body', 'ABC Contracting closed your application for Maintenance Electrician: all positions filled.')
            ->where('notifications.data.2.body', fn (string $body) => str_contains($body, 'Maintenance Electrician'))
            ->where('unread.notifications', 4));
});

it('tells the handling recruiter, or else everyone who manages applicants, when the worker answers', function () {
    $this->application->status->transitionTo(OfferStage::class);
    $this->application->offers()->create(['salary_amount_minor' => 2_000_00, 'salary_currency' => 'AED', 'salary_period' => 'month', 'status' => 'sent']);

    $this->actingAs($this->worker)->post(route('worker.applications.offer', [$this->application, 'accept']));

    expect(latestFor($this->recruiter)->type)->toBe('application_answered')
        ->and($this->viewer->notifications()->count())->toBe(0);

    $other = Application::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => makeWorker()->workerProfile->id]);
    $sara = orgMember($this->employer, OrganizationRole::HrAdmin);
    $other->update(['assigned_user_id' => $sara->id]);
    $this->recruiter->notifications()->delete();

    $this->actingAs($other->workerProfile->user)->post(route('worker.applications.withdraw', $other));

    expect(latestFor($sara)->type)->toBe('application_answered')
        ->and($this->recruiter->notifications()->count())->toBe(0);
});

it('tells the other side about new messages', function () {
    $conversation = Conversation::factory()->create(['application_id' => $this->application->id]);

    actingInOrg($this->recruiter, $this->employer)->post(route('org.messages.store', $conversation), ['body' => 'Please bring your certificates.']);
    expect(latestFor($this->worker)->data['params']['preview'])->toBe('Please bring your certificates.');

    $this->actingAs($this->worker)->post(route('worker.messages.store', $conversation), ['body' => 'I will.']);
    expect(latestFor($this->recruiter)->type)->toBe('organization_message')
        ->and(latestFor($this->recruiter)->data['url'])->toBe(route('org.messages.show', $conversation, false));
});

it('sends one daily digest per job to members who can see applicants', function () {
    Application::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => makeWorker()->workerProfile->id, 'applied_at' => now()->subHours(3)]);
    Application::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => makeWorker()->workerProfile->id, 'applied_at' => now()->subDays(3)]);

    Artisan::call('notifications:new-applicants');

    expect(latestFor($this->viewer)->type)->toBe('new_applicants')
        ->and(latestFor($this->viewer)->data['params']['count'])->toBe(2)
        ->and($this->worker->notifications()->count())->toBe(0);
});

it('opens a notification once, only for its owner, and never off-site', function () {
    actingInOrg($this->recruiter, $this->employer)
        ->post(route('org.applicants.move'), ['application_ids' => [$this->application->id], 'stage' => 'shortlisted']);
    $notification = latestFor($this->worker);

    $this->actingAs(makeWorker())->post(route('notifications.open', $notification->id))->assertNotFound();
    $this->actingAs($this->worker)->post(route('notifications.open', $notification->id))
        ->assertRedirect(route('worker.applications.show', $this->application, false));
    expect($notification->fresh()?->read_at)->not->toBeNull();

    $notification->forceFill(['data' => [...$notification->data, 'url' => 'https://evil.example/phish']])->save();
    $this->post(route('notifications.open', $notification->id))->assertRedirect('/');

    $this->post(route('notifications.read-all'))->assertRedirect();
    expect($this->worker->unreadNotifications()->count())->toBe(0);
});
