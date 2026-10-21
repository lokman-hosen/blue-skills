<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Worker\Support\MediaUrls;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    Storage::fake('private');
    $this->agency = Organization::factory()->agency()->verified()->create([
        'display_name' => 'Northstar Manpower', 'licence_number' => 'RA-2026-0418',
        'licence_expires_on' => now()->addYear(), 'licence_countries' => ['AE'],
    ]);
    $this->agencyOwner = orgMember($this->agency, OrganizationRole::AgencyOwner);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->employerHr = orgMember($this->employer, OrganizationRole::HrAdmin);
    $this->officer = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::VerificationOfficer);
    $this->electrician = Occupation::query()->where('slug', 'electrician')->value('id');
});

function mandateForm(array $overrides = []): array
{
    return [
        'employer_id' => test()->employer->id,
        'occupation_ids' => [test()->electrician],
        'destination_country_code' => 'AE',
        'headcount' => 200,
        'valid_until' => now()->addMonths(6)->toDateString(),
        'letter' => UploadedFile::fake()->createWithContent('mandate.pdf', "%PDF-1.4\n%%EOF"),
        ...$overrides,
    ];
}

function requestMandate(): JobMandate
{
    actingInOrg(test()->agencyOwner, test()->agency)->post(route('org.mandates.store'), mandateForm())->assertSessionHasNoErrors();

    return JobMandate::query()->latest()->firstOrFail();
}

it('lets an agency request a mandate with its letter and tells the employer', function () {
    $mandate = requestMandate();

    expect($mandate->status)->toBe(MandateStatus::AwaitingEmployer)
        ->and($mandate->reference)->toStartWith('MD-')
        ->and($mandate->verificationRequests()->sole()->getMedia(VerificationRequest::EVIDENCE_COLLECTION))->toHaveCount(1)
        ->and($this->employerHr->notifications()->sole()->type)->toBe('mandate_requested');

    actingInOrg($this->employerHr, $this->employer)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('mandateRequests', 1));
});

it('validates the licence, the employer and the letter', function () {
    actingInOrg($this->agencyOwner, $this->agency);

    $this->post(route('org.mandates.store'), mandateForm(['destination_country_code' => 'SA']))
        ->assertSessionHasErrors(['destination_country_code' => __('mandates.licence_not_valid')]);
    $this->post(route('org.mandates.store'), mandateForm(['employer_id' => Organization::factory()->employer()->create()->id]))
        ->assertSessionHasErrors(['employer' => __('mandates.employer_not_verified')]);
    $this->post(route('org.mandates.store'), mandateForm(['letter' => null, 'occupation_ids' => [], 'valid_until' => now()->subDay()->toDateString()]))
        ->assertSessionHasErrors(['letter', 'occupation_ids', 'valid_until']);

    actingInOrg(orgMember($this->agency, OrganizationRole::CaseOfficer), $this->agency)
        ->post(route('org.mandates.store'), mandateForm())->assertForbidden();

    expect(JobMandate::query()->count())->toBe(0);
});

it('lets only the employer\'s owner or HR admin confirm, and requires a note to decline', function () {
    $mandate = requestMandate();

    actingInOrg(orgMember($this->employer, OrganizationRole::Recruiter), $this->employer)
        ->post(route('org.mandates.respond', [$mandate, 'confirm']))->assertForbidden();
    actingInOrg($this->agencyOwner, $this->agency)
        ->post(route('org.mandates.respond', [$mandate, 'confirm']))->assertForbidden();

    actingInOrg($this->employerHr, $this->employer)
        ->post(route('org.mandates.respond', [$mandate, 'decline']), [])->assertSessionHasErrors('note');
    $this->post(route('org.mandates.respond', [$mandate, 'confirm']))->assertRedirect();

    expect($mandate->fresh()?->status)->toBe(MandateStatus::Pending)
        ->and($mandate->fresh()?->employer_responded_by)->toBe($this->employerHr->id)
        ->and($this->agencyOwner->notifications()->sole()->type)->toBe('mandate_answered');
});

it('queues confirmed mandates for staff, who verify them against the licence', function () {
    $mandate = requestMandate();

    $this->actingAs($this->officer)->get(route('admin.mandates.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/mandates/index')->has('reviews', 0));

    actingInOrg($this->employerHr, $this->employer)->post(route('org.mandates.respond', [$mandate, 'confirm']));
    $review = $mandate->verificationRequests()->sole();

    $this->actingAs($this->officer)->get(route('admin.mandates.index'))
        ->assertInertia(fn (Assert $page) => $page->has('reviews', 1));
    $this->get(route('admin.mandates.show', $review))
        ->assertInertia(fn (Assert $page) => $page
            ->where('decidable', true)
            ->where('licence.valid', true)
            ->has('mandate.evidence', 1));

    $this->patch(route('admin.mandates.decide', $review), ['decision' => 'approved'])->assertRedirect(route('admin.mandates.index'));

    expect($mandate->fresh()?->status)->toBe(MandateStatus::Verified)
        ->and($mandate->fresh()?->isUsable())->toBeTrue()
        ->and($this->agencyOwner->notifications()->pluck('type')->all())->toContain('mandate_reviewed');
});

it('refuses approval when the licence lapsed, and sends the review back after new evidence', function () {
    $mandate = requestMandate();
    actingInOrg($this->employerHr, $this->employer)->post(route('org.mandates.respond', [$mandate, 'confirm']));
    $review = $mandate->verificationRequests()->sole();

    $this->agency->update(['licence_expires_on' => now()->subDay()]);
    $this->actingAs($this->officer)->patch(route('admin.mandates.decide', $review), ['decision' => 'approved'])
        ->assertSessionHasErrors(['decision' => __('mandates.licence_not_valid')]);

    $this->patch(route('admin.mandates.decide', $review), ['decision' => 'needs_info', 'reason' => 'mandate_not_signed'])->assertRedirect();
    expect($mandate->fresh()?->status)->toBe(MandateStatus::Pending);

    actingInOrg($this->agencyOwner, $this->agency)->post(route('org.mandates.evidence', $mandate), [
        'document' => UploadedFile::fake()->createWithContent('signed.pdf', "%PDF-1.4\n%%EOF"),
    ])->assertRedirect();

    expect($review->fresh()?->status)->toBeInstanceOf(InReview::class);
});

it('shows a mandate and its letter only to its agency, its employer and staff', function () {
    $mandate = requestMandate();
    $letter = MediaUrls::signed($mandate->verificationRequests()->sole()->getFirstMedia(VerificationRequest::EVIDENCE_COLLECTION));

    actingInOrg($this->employerHr, $this->employer)->get(route('org.mandates.show', $mandate))
        ->assertInertia(fn (Assert $page) => $page->where('side', 'employer')->where('abilities.confirm', true));
    $this->get($letter)->assertOk();

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->get(route('org.mandates.show', $mandate))->assertNotFound();
    $this->get($letter)->assertForbidden();

    $this->actingAs(makeWorker())->get(route('admin.mandates.index'))->assertForbidden();
});

it('lets the agency owner set the destinations the licence covers', function () {
    actingInOrg($this->agencyOwner, $this->agency)->get(route('org.licence.edit'))
        ->assertInertia(fn (Assert $page) => $page->component('org/licence')->where('licence.valid', true)->where('canEdit', true));

    $this->put(route('org.licence.update'), ['countries' => ['AE', 'SA']])->assertRedirect();
    expect($this->agency->fresh()?->licence_countries)->toBe(['AE', 'SA']);

    $this->put(route('org.licence.update'), ['countries' => ['XX']])->assertSessionHasErrors('countries.0');

    actingInOrg($this->employerHr, $this->employer)->get(route('org.licence.edit'))->assertNotFound();
});
