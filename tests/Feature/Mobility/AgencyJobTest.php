<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->agency = Organization::factory()->agency()->verified()->create([
        'display_name' => 'Northstar Manpower', 'licence_countries' => ['AE'],
    ]);
    $this->agencyOwner = orgMember($this->agency, OrganizationRole::AgencyOwner);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->electrician = Occupation::query()->where('slug', 'electrician')->value('id');
    $this->mandate = JobMandate::factory()->create([
        'agency_organization_id' => $this->agency->id,
        'employer_organization_id' => $this->employer->id,
        'occupation_ids' => [$this->electrician],
        'destination_country_code' => 'AE',
        'headcount' => 30,
        'status' => MandateStatus::Verified,
    ]);
});

function agencyRequirement(array $overrides = []): array
{
    return [
        'job_mandate_id' => test()->mandate->id,
        'title' => 'Building Electrician',
        'occupation_id' => test()->electrician,
        'headcount' => 20,
        'destination_country_code' => 'AE',
        'destination_city' => 'Dubai',
        ...$overrides,
    ];
}

/** A complete agency job saved through the four wizard steps. */
function postAgencyJob(?User $user = null): JobPosting
{
    actingInOrg($user ?? test()->agencyOwner, test()->agency)->post(route('org.jobs.store'), agencyRequirement())->assertSessionHasNoErrors();
    $job = JobPosting::query()->latest()->firstOrFail();

    test()->put(route('org.jobs.update', [$job, 'skills']), ['skill_ids' => [], 'language_requirements' => []])->assertSessionHasNoErrors();
    test()->put(route('org.jobs.update', [$job, 'terms']), [
        'salary_amount' => 2200, 'salary_currency' => 'AED', 'salary_period' => 'month',
        'hours_per_day' => 8, 'days_per_week' => 6, 'contract_months' => 24,
    ])->assertSessionHasNoErrors();
    test()->put(route('org.jobs.update', [$job, 'mobility']), [
        'accommodation' => 'provided', 'permit_payer' => 'employer', 'visa_payer' => 'employer', 'costs' => [],
    ])->assertSessionHasNoErrors();

    return $job->refresh();
}

it('lets an agency post a job for the mandate\'s employer and send it to review', function () {
    actingInOrg($this->agencyOwner, $this->agency)->get(route('org.jobs.create'))
        ->assertInertia(fn (Assert $page) => $page->component('org/jobs/wizard')
            ->where('agency', true)
            ->has('options.mandates', 1)
            ->where('options.mandates.0.reference', $this->mandate->reference)
            ->where('options.mandates.0.headcountLeft', 30));

    $job = postAgencyJob();

    expect($job->organization_id)->toBe($this->agency->id)
        ->and($job->agency_organization_id)->toBe($this->agency->id)
        ->and($job->employer_organization_id)->toBe($this->employer->id)
        ->and($job->job_mandate_id)->toBe($this->mandate->id);

    $this->get(route('org.jobs.edit', [$job, 'preview']))->assertInertia(fn (Assert $page) => $page
        ->where('problems', [])
        ->where('preview.postedByAgency', true)
        ->where('preview.employerName', 'ABC Contracting')
        ->where('preview.mandateReference', $this->mandate->reference));

    $this->post(route('org.jobs.submit', $job))->assertRedirect();
    expect($job->fresh()?->status)->toBeInstanceOf(Submitted::class);
});

it('lets a case officer post, but not a compliance reviewer or an agency without a usable mandate', function () {
    postAgencyJob(orgMember($this->agency, OrganizationRole::CaseOfficer));

    actingInOrg(orgMember($this->agency, OrganizationRole::ComplianceReviewer), $this->agency)
        ->get(route('org.jobs.create'))->assertForbidden();

    $this->mandate->update(['status' => MandateStatus::Pending]);
    actingInOrg($this->agencyOwner, $this->agency)->get(route('org.jobs.create'))->assertForbidden();
    $this->get(route('org.jobs.index'))->assertInertia(fn (Assert $page) => $page->where('canCreate', false)->where('needsMandate', true));
    $this->post(route('org.jobs.store'), agencyRequirement())->assertForbidden();

    $this->mandate->update(['status' => MandateStatus::Verified]);
    $this->agency->update(['licence_expires_on' => now()->subDay()]);
    $this->get(route('org.jobs.create'))->assertForbidden();
});

it('keeps the job inside the mandate\'s trades, destination and headcount', function () {
    actingInOrg($this->agencyOwner, $this->agency);

    $this->post(route('org.jobs.store'), agencyRequirement(['job_mandate_id' => null]))->assertSessionHasErrors('job_mandate_id');

    $plumber = Occupation::query()->where('slug', '!=', 'electrician')->value('id');
    $this->post(route('org.jobs.store'), agencyRequirement(['occupation_id' => $plumber]))
        ->assertSessionHasErrors(['job_mandate_id' => __('jobs.mandate.out_of_scope', ['reference' => $this->mandate->reference])]);
    $this->post(route('org.jobs.store'), agencyRequirement(['destination_country_code' => 'SA']))->assertSessionHasErrors('job_mandate_id');
    $this->post(route('org.jobs.store'), agencyRequirement(['headcount' => 31]))
        ->assertSessionHasErrors(['headcount' => __('jobs.mandate.headcount', ['reference' => $this->mandate->reference, 'left' => 30])]);

    // The places other open jobs already use count against the mandate.
    $this->post(route('org.jobs.store'), agencyRequirement(['headcount' => 20]))->assertSessionHasNoErrors();
    $this->post(route('org.jobs.store'), agencyRequirement(['headcount' => 11]))->assertSessionHasErrors('headcount');

    $otherAgency = JobMandate::factory()->create(['status' => MandateStatus::Verified, 'destination_country_code' => 'AE']);
    $this->post(route('org.jobs.store'), agencyRequirement(['job_mandate_id' => $otherAgency->id]))->assertSessionHasErrors('job_mandate_id');

    expect(JobPosting::query()->count())->toBe(1);
});

it('does not let employers attach a mandate to their own jobs', function () {
    $hr = orgMember($this->employer, OrganizationRole::HrAdmin);

    actingInOrg($hr, $this->employer)->post(route('org.jobs.store'), agencyRequirement())->assertSessionHasErrors('job_mandate_id');
    actingInOrg($hr, $this->employer)->post(route('org.jobs.store'), agencyRequirement(['job_mandate_id' => null]))->assertSessionHasNoErrors();

    expect(JobPosting::query()->sole()->agency_organization_id)->toBeNull();
});

it('refuses submission and publication once the mandate stops backing the job', function () {
    $job = postAgencyJob();

    $this->mandate->update(['valid_until' => now()->subDay()]);
    $this->post(route('org.jobs.submit', $job))
        ->assertSessionHasErrors(['job_mandate_id' => __('jobs.mandate.not_usable', ['reference' => $this->mandate->reference])]);

    $this->mandate->update(['valid_until' => now()->addMonth()]);
    $this->post(route('org.jobs.submit', $job))->assertSessionHasNoErrors();
    $review = $job->verificationRequests()->sole();

    $this->mandate->update(['status' => MandateStatus::Revoked]);
    $moderator = User::factory()->staff()->withTwoFactor()->create()->assignStaffRole(StaffRole::TrustOfficer);
    $this->actingAs($moderator)->patch(route('admin.jobs.decide', $review), ['decision' => 'approved'])->assertSessionHasErrors('job_mandate_id');

    $this->mandate->update(['status' => MandateStatus::Verified]);
    $this->patch(route('admin.jobs.decide', $review), ['decision' => 'approved'])->assertSessionHasNoErrors();
    expect($job->fresh()?->status)->toBeInstanceOf(Published::class);
});
