<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Messaging\Events\MessageSent;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Organization\Models\Organization;
use App\Domain\Trust\Models\Report;
use App\Domain\Worker\Support\MediaUrls;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    Storage::fake('private');
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $this->worker = makeWorker();
    $this->application = Application::factory()->create([
        'job_id' => JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]),
        'worker_profile_id' => $this->worker->workerProfile->id,
    ]);
});

it('opens one conversation per application from either side', function () {
    $this->actingAs($this->worker)->get(route('worker.applications.messages', $this->application))
        ->assertRedirect(route('worker.messages.show', Conversation::query()->sole()));

    actingInOrg($this->recruiter, $this->employer)->get(route('org.applicants.messages', $this->application))
        ->assertRedirect(route('org.messages.show', Conversation::query()->sole()));

    expect(Conversation::query()->count())->toBe(1);
});

it('sends messages live, with unread counts until the other side reads them', function () {
    Event::fake([MessageSent::class]);
    $conversation = Conversation::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs($this->worker)->post(route('worker.messages.store', $conversation), ['body' => 'When is the interview?'])
        ->assertRedirect(route('worker.messages.show', $conversation));
    Event::assertDispatched(MessageSent::class);

    actingInOrg($this->recruiter, $this->employer)->get(route('org.messages.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('org/messages/index')
            ->where('conversations.0.unread', 1)
            ->where('conversations.0.counterpart', $this->worker->name)
            ->where('unread.messages', 1));

    $this->get(route('org.messages.show', $conversation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.0.body', 'When is the interview?')
            ->where('conversation.canPost', true)
            ->where('unread.messages', 0));

    $this->post(route('org.messages.store', $conversation), ['body' => 'Next Tuesday at 10:00.']);

    $this->actingAs($this->worker)->get(route('worker.messages.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversations.0.unread', 1)
            ->where('conversations.0.counterpart', $this->employer->display_name));
    $this->get(route('worker.messages.show', $conversation))
        ->assertInertia(fn (Assert $page) => $page
            ->where('conversation.messages.1.senderName', "{$this->recruiter->name} · {$this->employer->display_name}")
            ->where('conversation.messages.1.mine', false));
});

it('keeps conversations to their parties and roles', function () {
    $conversation = Conversation::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs(makeWorker())->get(route('worker.messages.show', $conversation))->assertForbidden();
    $this->actingAs(makeWorker())->post(route('worker.messages.store', $conversation), ['body' => 'Hi'])->assertForbidden();

    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    actingInOrg($viewer, $this->employer)->get(route('org.messages.show', $conversation))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('conversation.canPost', false));
    $this->post(route('org.messages.store', $conversation), ['body' => 'Hi'])->assertForbidden();

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->get(route('org.messages.show', $conversation))->assertNotFound();

    expect(Message::query()->count())->toBe(0);
});

it('closes the conversation 30 days after the application ends', function () {
    $conversation = Conversation::factory()->create(['application_id' => $this->application->id]);
    $this->application->status->transitionTo(Rejected::class);
    $this->application->update(['stage_changed_at' => now()->subDays(31)]);

    $this->actingAs($this->worker)->post(route('worker.messages.store', $conversation), ['body' => 'Hello?'])->assertForbidden();
    $this->get(route('worker.messages.show', $conversation))
        ->assertInertia(fn (Assert $page) => $page->where('conversation.writable', false));
});

it('validates messages and serves attachments to the conversation only', function () {
    $conversation = Conversation::factory()->create(['application_id' => $this->application->id]);

    $this->actingAs($this->worker)->post(route('worker.messages.store', $conversation), ['body' => ''])->assertSessionHasErrors('body');
    $this->post(route('worker.messages.store', $conversation), [
        'body' => 'File',
        'attachments' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')],
    ])->assertSessionHasErrors('attachments.0');

    $this->post(route('worker.messages.store', $conversation), [
        'body' => 'My training certificate',
        'attachments' => [UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf')],
    ])->assertSessionHasNoErrors();

    $url = MediaUrls::signed(Message::query()->sole()->getFirstMedia(Message::ATTACHMENTS));
    actingInOrg($this->recruiter, $this->employer)->get($url)->assertOk();

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->get($url)->assertForbidden();
});

it('lets either side report a message for trust review', function () {
    $conversation = Conversation::factory()->create(['application_id' => $this->application->id]);
    $message = Message::factory()->create(['conversation_id' => $conversation->id, 'sender_user_id' => $this->recruiter->id, 'body' => 'Send 500 USD for the visa']);

    $this->actingAs($this->worker)->post(route('messages.report', $message), ['category' => 'fake_job'])->assertSessionHasErrors('category');
    $this->post(route('messages.report', $message), ['category' => 'unauthorized_payment'])->assertSessionHasNoErrors();

    $report = Report::query()->sole();
    expect($report->subject_type)->toBe('message')
        ->and($report->subject_id)->toBe($message->id);

    $this->actingAs(makeWorker())->post(route('messages.report', $message), ['category' => 'harassment'])->assertForbidden();
});
