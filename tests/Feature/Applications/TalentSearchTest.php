<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Shortlist;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Actions\UpdateProfileVisibility;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $this->electrician = Occupation::query()->where('slug', 'electrician')->firstOrFail();
});

/** A worker with a trade who chose "employers and agencies" on the Privacy page. */
function findableWorker(array $profile = []): User
{
    $worker = makeWorker(['occupation_id' => Occupation::query()->where('slug', 'electrician')->value('id'), 'years_experience' => 5, ...$profile]);
    app(UpdateProfileVisibility::class)->handle($worker->workerProfile, ProfileVisibility::EmployersAndAgencies);

    return $worker;
}

it('finds only workers who chose to be found, without contact details', function () {
    $findable = findableWorker();
    makeWorker(['occupation_id' => $this->electrician->id]); // applications only
    $gone = findableWorker();
    $gone->delete();

    actingInOrg($this->recruiter, $this->employer)->get(route('org.talent.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('org/talent/index')
            ->where('verified', true)
            ->has('results.data', 1)
            ->where('results.data.0.reference', $findable->workerProfile->reference)
            ->missing('results.data.0.phone'))
        ->assertDontSee((string) $findable->phone);
});

it('filters by structured fields and explains matching for a chosen job', function () {
    $ready = findableWorker(['passport_status' => PassportStatus::Ready, 'years_experience' => 9]);
    $ready->workerProfile->experiences()->create(['country_code' => 'AE', 'role' => 'Electrician', 'years' => 3]);
    findableWorker(['passport_status' => PassportStatus::Applied, 'years_experience' => 2]);
    $job = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id, 'occupation_id' => $this->electrician->id]);

    actingInOrg($this->recruiter, $this->employer);

    $this->get(route('org.talent.index', ['passport_ready' => 1]))->assertInertia(fn (Assert $page) => $page->has('results.data', 1));
    $this->get(route('org.talent.index', ['min_years' => 5]))->assertInertia(fn (Assert $page) => $page->has('results.data', 1));
    $this->get(route('org.talent.index', ['worked_in' => 'AE']))->assertInertia(fn (Assert $page) => $page
        ->has('results.data', 1)
        ->where('results.data.0.countriesWorked', ['United Arab Emirates']));
    $this->get(route('org.talent.index', ['occupation' => 'welder']))->assertInertia(fn (Assert $page) => $page->has('results.data', 0));
    $this->get(route('org.talent.index', ['q' => $ready->name]))->assertInertia(fn (Assert $page) => $page->has('results.data', 1));
    $this->get(route('org.talent.index', ['job' => $job->public_code]))->assertInertia(fn (Assert $page) => $page
        ->where('filters.job', $job->public_code)
        ->where('results.data.0.match.total', fn ($total) => $total > 0));
});

it('locks search until the organization is verified and needs the talent permission', function () {
    $pending = Organization::factory()->employer()->create();
    actingInOrg(orgMember($pending, OrganizationRole::Owner), $pending)->get(route('org.talent.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('verified', false)->where('results', null));

    actingInOrg(orgMember($this->employer, OrganizationRole::Viewer), $this->employer)->get(route('org.talent.index'))->assertForbidden();
    $this->actingAs(makeWorker())->get(route('org.talent.index'))->assertForbidden();
});

it('opens a findable profile and hides everyone else, applicants included', function () {
    $findable = findableWorker();
    $applicant = makeWorker(['occupation_id' => $this->electrician->id]);
    Application::factory()->create([
        'worker_profile_id' => $applicant->workerProfile->id,
        'job_id' => JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]),
    ]);

    actingInOrg($this->recruiter, $this->employer)->get(route('org.talent.show', $findable->workerProfile->reference))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/talent/show')->where('profile.name', $findable->name));

    $this->get(route('org.talent.show', $applicant->workerProfile->reference))->assertNotFound();
});

it('lets searching organizations watch only SkillShows made visible to employers', function () {
    $findable = findableWorker();
    $visible = SkillShow::factory()->approved()->create(['worker_profile_id' => $findable->workerProfile->id, 'visibility' => ProfileVisibility::EmployersAndAgencies]);
    $private = SkillShow::factory()->approved()->create(['worker_profile_id' => $findable->workerProfile->id, 'visibility' => ProfileVisibility::ApplicationsOnly]);

    expect($this->recruiter->can('view', $visible))->toBeTrue()
        ->and($this->recruiter->can('view', $private))->toBeFalse()
        ->and(orgMember($this->employer, OrganizationRole::Viewer)->can('view', $visible))->toBeFalse()
        ->and(orgMember(Organization::factory()->employer()->create(), OrganizationRole::Owner)->can('view', $visible))->toBeFalse();
});

it('manages shortlists of findable workers', function () {
    $findable = findableWorker();
    $hidden = makeWorker(['occupation_id' => $this->electrician->id]);

    actingInOrg($this->recruiter, $this->employer)->post(route('org.shortlists.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->post(route('org.shortlists.store'), ['name' => 'Dubai towers'])->assertRedirect();
    $shortlist = Shortlist::query()->sole();

    $this->post(route('org.shortlists.entries.store', $shortlist), ['worker' => $findable->workerProfile->reference, 'note' => 'Strong DB work'])
        ->assertSessionHasNoErrors();
    $this->post(route('org.shortlists.entries.store', $shortlist), ['worker' => $findable->workerProfile->reference])
        ->assertSessionHasErrors(['worker' => __('shortlists.already_on_list')]);
    $this->post(route('org.shortlists.entries.store', $shortlist), ['worker' => $hidden->workerProfile->reference])
        ->assertSessionHasErrors(['worker' => __('shortlists.not_visible')]);

    $this->get(route('org.shortlists.show', $shortlist))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.note', 'Strong DB work')
            ->where('entries.0.worker.reference', $findable->workerProfile->reference));

    app(UpdateProfileVisibility::class)->handle($findable->workerProfile->fresh(), ProfileVisibility::Hidden);
    $this->get(route('org.shortlists.show', $shortlist))
        ->assertInertia(fn (Assert $page) => $page->where('entries.0.worker', null));

    $this->delete(route('org.shortlists.entries.destroy', [$shortlist, $shortlist->entries()->sole()]))->assertRedirect();
    $this->delete(route('org.shortlists.destroy', $shortlist))->assertRedirect(route('org.shortlists.index'));

    expect(Shortlist::query()->count())->toBe(0);
});

it('keeps shortlists inside their organization and away from viewers', function () {
    $shortlist = Shortlist::factory()->create(['organization_id' => $this->employer->id]);

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->get(route('org.shortlists.show', $shortlist))->assertNotFound();

    actingInOrg(orgMember($this->employer, OrganizationRole::Viewer), $this->employer)->get(route('org.shortlists.index'))->assertForbidden();
    $this->post(route('org.shortlists.store'), ['name' => 'Mine'])->assertForbidden();
});
