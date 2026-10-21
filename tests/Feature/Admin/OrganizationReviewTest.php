<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Mail\OrganizationReviewDecidedMail;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use App\Domain\Organization\States\Verification\Rejected;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\Approved;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Verification\States\VerificationRequest\NeedsInfo;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
    Mail::fake();
    Storage::fake('private');

    $this->owner = User::factory()->create();
    $this->organization = Organization::factory()->employer()->create(['verification_status' => Pending::class]);
    $this->organization->users()->attach($this->owner->id, ['joined_at' => now()]);
    TeamContext::run($this->organization->id, fn () => $this->owner->assignRole(OrganizationRole::Owner->value));
    $this->review = $this->organization->verificationRequests()->create(['type' => VerificationType::Organization]);
    $this->review->addMedia(UploadedFile::fake()->createWithContent('licence.pdf', "%PDF-1.4\n%%EOF"))
        ->toMediaCollection(VerificationRequest::EVIDENCE_COLLECTION);

    $this->reviewer = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::VerificationOfficer);
});

it('lists open registrations and shows evidence through signed links to reviewers', function () {
    $this->actingAs($this->reviewer)->get(route('admin.organizations.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/organizations/index')->has('reviews', 1)
            ->where('reviews.0.evidenceCount', 1));

    $url = null;
    $this->get(route('admin.organizations.show', $this->review))->assertOk()
        ->assertInertia(function (Assert $page) use (&$url) {
            $page->component('admin/organizations/show')->where('decidable', true)->has('members', 1)->has('reasons', 6);
            $url = $page->toArray()['props']['documents'][0]['url'];
        });

    $this->get($url)->assertOk();
});

it('keeps the queue to staff with the review permission', function () {
    $crm = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::CrmAgent);

    $this->actingAs($crm)->get(route('admin.organizations.index'))->assertForbidden();
    $this->actingAs($crm)->patch(route('admin.organizations.decide', $this->review), ['decision' => 'approved'])->assertForbidden();
    $this->actingAs($this->owner)->get(route('admin.organizations.index'))->assertForbidden();

    expect($this->organization->refresh()->verification_status)->toBeInstanceOf(Pending::class);
});

it('approves an organization and emails its owners', function () {
    $this->actingAs($this->reviewer)->patch(route('admin.organizations.decide', $this->review), ['decision' => 'approved'])
        ->assertRedirect(route('admin.organizations.index'));

    expect($this->organization->refresh()->verification_status)->toBeInstanceOf(Verified::class)
        ->and($this->organization->verified_at)->not->toBeNull()
        ->and($this->review->refresh()->status)->toBeInstanceOf(Approved::class)
        ->and($this->review->decided_by)->toBe($this->reviewer->id);

    Mail::assertQueued(OrganizationReviewDecidedMail::class, fn ($mail) => $mail->hasTo($this->owner->email));
    expect(Activity::query()->where('description', 'review.approved')->where('causer_id', $this->reviewer->id)->exists())->toBeTrue();
});

it('asks for more information, then the organization answers with a new document', function () {
    $this->actingAs($this->reviewer)->patch(route('admin.organizations.decide', $this->review), [
        'decision' => 'needs_info', 'reason' => 'document_expired', 'note' => 'Please upload the 2026 licence.',
    ])->assertRedirect();

    expect($this->review->refresh()->status)->toBeInstanceOf(NeedsInfo::class)
        ->and($this->review->decision_note)->toBe('Please upload the 2026 licence.')
        ->and($this->organization->refresh()->verification_status)->toBeInstanceOf(Pending::class);

    $this->actingAs($this->owner)->get(route('organizations.verification.edit'))->assertInertia(fn (Assert $page) => $page
        ->where('lastDecision.decision', 'needs_info')
        ->where('lastDecision.reasonCode', 'document_expired')
        ->where('lastDecision.reviewerName', null));

    $this->post(route('organizations.verification.evidence.store'), [
        'document' => UploadedFile::fake()->createWithContent('licence-2026.pdf', "%PDF-1.4\n%%EOF"),
    ]);

    expect($this->review->refresh()->status)->toBeInstanceOf(InReview::class);
});

it('needs a reason to reject, and lets a rejected organization ask again', function () {
    $this->actingAs($this->reviewer)->patch(route('admin.organizations.decide', $this->review), ['decision' => 'rejected'])
        ->assertSessionHasErrors('reason');

    $this->patch(route('admin.organizations.decide', $this->review), ['decision' => 'rejected', 'reason' => 'not_a_registered_business']);
    expect($this->organization->refresh()->verification_status)->toBeInstanceOf(Rejected::class);

    // A decided review cannot be decided again.
    $this->patch(route('admin.organizations.decide', $this->review), ['decision' => 'approved'])->assertSessionHasErrors('decision');

    $this->actingAs($this->owner)->post(route('organizations.verification.resubmit'))->assertRedirect(route('organizations.verification.edit'));

    expect($this->organization->refresh()->verification_status)->toBeInstanceOf(Pending::class)
        ->and($this->organization->openVerificationRequest())->not->toBeNull();
});
