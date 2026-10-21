<?php

use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
});

it('keeps one conversation per application with immutable messages', function () {
    $conversation = Conversation::factory()->create();
    Message::factory()->for($conversation)->create();

    expect($conversation->application->conversation?->is($conversation))->toBeTrue()
        ->and($conversation->messages)->toHaveCount(1);

    Conversation::factory()->create(['application_id' => $conversation->application_id]);
})->throws(UniqueConstraintViolationException::class);

it('never edits a message', function () {
    Message::factory()->create()->update(['body' => 'Changed']);
})->throws(LogicException::class, 'Messages are never edited.');

it('closes conversations 30 days after the application closes', function () {
    $conversation = Conversation::factory()->create();
    expect($conversation->isWritable())->toBeTrue();

    $conversation->application->status->transitionTo(Rejected::class);
    $conversation->application->update(['stage_changed_at' => now()->subDays(10)]);
    expect($conversation->fresh()?->isWritable())->toBeTrue();

    $conversation->application->update(['stage_changed_at' => now()->subDays(31)]);
    expect($conversation->fresh()?->isWritable())->toBeFalse();
});

it('lets only the worker and the posting organization take part', function () {
    $conversation = Conversation::factory()->create();
    $worker = $conversation->workerProfile->user;
    $organization = $conversation->organization;

    expect(Gate::forUser($worker)->allows('post', $conversation))->toBeTrue()
        ->and(Gate::forUser(orgMember($organization, OrganizationRole::Recruiter))->allows('post', $conversation))->toBeTrue()
        ->and(Gate::forUser(orgMember($organization, OrganizationRole::HiringManager))->allows('post', $conversation))->toBeTrue()
        ->and(Gate::forUser(orgMember($organization, OrganizationRole::Viewer))->allows('view', $conversation))->toBeTrue()
        ->and(Gate::forUser(orgMember($organization, OrganizationRole::Viewer))->allows('post', $conversation))->toBeFalse()
        ->and(Gate::forUser(makeWorker())->allows('view', $conversation))->toBeFalse()
        ->and(Gate::forUser(orgMember(Organization::factory()->employer()->create(), OrganizationRole::Owner))->allows('view', $conversation))->toBeFalse();
});

it('stores delivery recipients encrypted and invitations once per job and worker', function () {
    $outbound = OutboundMessage::factory()->create(['channel' => DeliveryChannel::Sms, 'recipient' => '+8801711000099']);

    expect($outbound->fresh()?->recipient)->toBe('+8801711000099')
        ->and(DB::table('outbound_messages')->value('recipient'))->not->toContain('8801711000099');

    $invitation = JobInvitation::factory()->create();
    JobInvitation::factory()->create(['job_id' => $invitation->job_id, 'worker_profile_id' => $invitation->worker_profile_id]);
})->throws(UniqueConstraintViolationException::class);

it('keeps the conversation open after an accepted offer', function () {
    $conversation = Conversation::factory()->create();
    $conversation->application->update(['status' => Accepted::class, 'stage_changed_at' => now()->subDays(90)]);

    expect($conversation->fresh()?->isWritable())->toBeTrue();
});
