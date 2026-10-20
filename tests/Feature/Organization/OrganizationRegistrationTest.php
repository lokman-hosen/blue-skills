<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\Open;
use App\Http\Middleware\EnsureOrganizationVerified;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
});

function organizationPayload(array $overrides = []): array
{
    return [
        'type' => 'employer',
        'legal_name' => 'ABC Contracting LLC',
        'display_name' => 'ABC Contracting',
        'country_code' => 'AE',
        'city' => 'Dubai',
        'registration_number' => 'DED-1023456',
        'title' => 'HR Manager',
        ...$overrides,
    ];
}

it('shows the registration form only to organization users', function () {
    $this->get(route('organizations.create'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())
        ->get(route('organizations.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('onboarding/organization')->has('countries', 12));

    $this->actingAs(User::factory()->worker()->create())->get(route('organizations.create'))->assertForbidden();
    $this->actingAs(User::factory()->staff()->create())->get(route('organizations.create'))->assertForbidden();
});

it('requires a verified email before registering an organization', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('organizations.create'))
        ->assertRedirect(route('verification.notice'));
});

it('registers a pending organization with its owner and a review request', function (string $type, OrganizationRole $owner, VerificationType $review, array $extra) {
    $user = User::factory()->create(['email' => 'hr@abc-contracting.test']);

    $this->actingAs($user)
        ->post(route('organizations.store'), organizationPayload(['type' => $type, ...$extra]))
        ->assertRedirect(route('organizations.verification.edit'));

    $organization = Organization::query()->firstOrFail();
    $request = $organization->verificationRequests()->firstOrFail();

    expect($organization->type->value)->toBe($type)
        ->and($organization->verification_status)->toBeInstanceOf(Pending::class)
        ->and($organization->users->pluck('id')->all())->toBe([$user->id])
        ->and(TeamContext::run($organization->id, fn () => $user->unsetRelation('roles')->hasRole($owner->value)))->toBeTrue()
        ->and($request->type)->toBe($review)
        ->and($request->status)->toBeInstanceOf(Open::class)
        ->and($request->notes)->toBeNull()
        ->and(session(CurrentOrganization::SESSION_KEY))->toBe($organization->id);
})->with([
    'employer' => ['employer', OrganizationRole::Owner, VerificationType::Organization, []],
    'agency' => ['agency', OrganizationRole::AgencyOwner, VerificationType::AgencyLicence, ['licence_number' => 'RA-2026-0418', 'licence_expires_on' => '2027-03-31']],
    'institute' => ['institute', OrganizationRole::InstituteOwner, VerificationType::InstituteAccreditation, []],
]);

it('flags free email addresses for the reviewer', function () {
    $user = User::factory()->create(['email' => 'abc.contracting@gmail.com']);

    $this->actingAs($user)->post(route('organizations.store'), organizationPayload());

    expect(VerificationRequest::query()->firstOrFail()->notes)->toContain('gmail.com');
});

it('validates organization details', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('organizations.store'), organizationPayload(['type' => 'agency', 'legal_name' => '', 'country_code' => 'XX']))
        ->assertSessionHasErrors(['legal_name', 'country_code', 'licence_number', 'licence_expires_on']);

    expect(Organization::query()->count())->toBe(0);
});

it('stores registration evidence privately on the open review', function () {
    Storage::fake('private');
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('organizations.store'), organizationPayload());

    $this->post(route('organizations.verification.evidence.store'), [
        'document' => UploadedFile::fake()->createWithContent('trade-licence.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
    ])->assertRedirect();

    $media = VerificationRequest::query()->firstOrFail()->getFirstMedia(VerificationRequest::EVIDENCE_COLLECTION);

    expect($media?->disk)->toBe('private')
        ->and($media?->getCustomProperty('uploaded_by'))->toBe($user->id);
    Storage::disk('private')->assertExists($media->getPathRelativeToRoot());

    $this->get(route('organizations.verification.edit'))->assertInertia(fn (Assert $page) => $page
        ->component('onboarding/evidence')
        ->has('documents', 1)
        ->where('documents.0.name', 'trade-licence.pdf'));
});

it('rejects evidence of the wrong type', function () {
    Storage::fake('private');
    $this->actingAs(User::factory()->create())->post(route('organizations.store'), organizationPayload());

    $this->post(route('organizations.verification.evidence.store'), [
        'document' => UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload'),
    ])->assertSessionHasErrors('document');
});

it('lets only members who can update the organization add evidence', function () {
    Storage::fake('private');
    $owner = User::factory()->create();
    $this->actingAs($owner)->post(route('organizations.store'), organizationPayload());
    $organization = Organization::query()->firstOrFail();

    $viewer = User::factory()->create();
    $organization->users()->attach($viewer->id, ['joined_at' => now()]);
    TeamContext::run($organization->id, fn () => $viewer->assignRole(OrganizationRole::Viewer->value));

    $this->actingAs($viewer)->get(route('organizations.verification.edit'))->assertForbidden();
    $this->actingAs($viewer)->post(route('organizations.verification.evidence.store'), [
        'document' => UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'),
    ])->assertForbidden();
});

it('blocks unverified organizations from verified-only routes', function () {
    Route::middleware(['web', 'auth', EnsureOrganizationVerified::class])->get('/_test/verified-only', fn () => 'ok');

    $user = User::factory()->create();
    $organization = Organization::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $this->actingAs($user)->get('/_test/verified-only')->assertRedirect(route('dashboard'));

    $organization->forceFill(['verification_status' => 'verified'])->save();

    $this->actingAs($user)->get('/_test/verified-only')->assertOk();
});
