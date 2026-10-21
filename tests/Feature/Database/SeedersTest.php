<?php

use App\Domain\Applications\Data\ProfileSnapshotData;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Shortlist;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\Language;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Data\InstituteCountsData;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Jobs\Actions\PauseJobsUnderLapsedMandates;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Support\Unread;
use App\Domain\Mobility\Data\CaseCountsData;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Verification\Models\Document;
use Database\Seeders\CountrySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperuserSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

it('seeds the reference data and the sample world', function () {
    Storage::fake('private');
    $this->seed(DatabaseSeeder::class);

    expect(Country::query()->pluck('code')->sort()->values()->all())
        ->toBe(['AE', 'BD', 'CZ', 'IN', 'JP', 'LK', 'NP', 'OM', 'PK', 'PL', 'QA', 'SA'])
        ->and(Language::query()->pluck('code')->sort()->values()->all())
        ->toBe(['ar', 'bn', 'en', 'hi', 'ne', 'si', 'ta', 'ur'])
        ->and(Language::query()->where('rtl', true)->pluck('code')->sort()->values()->all())->toBe(['ar', 'ur'])
        ->and(Organization::query()->pluck('display_name'))
        ->toContain('ABC Contracting', 'Northstar Manpower', 'Meghna Technical Training Centre', 'Gulf Star Facilities')
        ->and(JobPosting::query()->count())->toBe(8)
        ->and(Application::query()->count())->toBeGreaterThan(0);

    $job = JobPosting::query()->where('public_code', 'UAE-EL-0001')->firstOrFail();
    expect($job->status)->toBeInstanceOf(Published::class)
        ->and($job->employer->display_name)->toBe('ABC Contracting')
        ->and($job->agency?->display_name)->toBe('Northstar Manpower')
        ->and($job->mandate?->isVerified())->toBeTrue();

    $rahim = User::query()->where('name', 'Rahim Uddin')->firstOrFail();
    expect($rahim->isWorker())->toBeTrue()
        ->and($rahim->workerProfile?->voiceAnswers)->toHaveCount(7);

    // Module 4: every sample application carries a snapshot the pipeline can read.
    $pipeline = JobPosting::query()->where('public_code', 'AE-MT-0115')->firstOrFail();
    expect($pipeline->applications()->count())->toBe(6)
        ->and(Application::query()->with('snapshot')->get()->every(fn (Application $application) => ProfileSnapshotData::from($application->snapshot?->data ?? []) instanceof ProfileSnapshotData))->toBeTrue()
        ->and(Shortlist::query()->sole()->entries)->toHaveCount(2)
        ->and(Document::query()->where('type', 'passport')->where('owner_user_id', $rahim->id)->sole()->getFirstMedia(Document::FILE_COLLECTION)?->uuid)->not->toBeNull()
        // Module 5: conversations and alerts render.
        ->and(Conversation::query()->count())->toBe(4)
        ->and($rahim->unreadNotifications()->count())->toBe(2)
        ->and(Unread::conversations($rahim, null))->toBe(1);

    // Module 6: Rahim's case has the UAE checklist, his passport scan shared and the medical due.
    $rahimCase = MobilityCase::query()->where('reference', 'MC-AE-2026-0188')->firstOrFail();
    expect($rahimCase->documents()->pluck('status', 'type')->map->value->all())
        ->toBe(['passport' => 'accepted', 'photo' => 'accepted', 'medical' => 'requested', 'contract' => 'accepted']);

    // Agency workspace: one document to check, one stage on hold, and a job paused under an
    // expired mandate that the daily check leaves alone (D-081).
    $northstar = Organization::query()->where('display_name', 'Northstar Manpower')->firstOrFail();
    expect(CaseCountsData::forOrganization($northstar))
        ->open->toBe(3)
        ->documentsToCheck->toBe(1)
        ->onHold->toBe(1)
        ->and(JobPosting::query()->where('public_code', 'UAE-EL-0004')->firstOrFail()->status->equals(Paused::class))->toBeTrue()
        ->and(app(PauseJobsUnderLapsedMandates::class)->handle())->toBe(0);

    // Institute: Meghna's 2026 cohort with two graduates attested and a published pool (D-087).
    $meghna = Organization::query()->where('display_name', 'Meghna Technical Training Centre')->firstOrFail();
    expect(InstituteCountsData::forInstitute($meghna))
        ->graduates->toBe(2)
        ->rosterWaiting->toBe(2)
        ->attested->toBe(2)
        ->assessmentsPassed->toBe(1)
        ->and(Cohort::query()->where('invite_code', 'MTTCEW26')->exists())->toBeTrue()
        ->and($meghna->talentPools()->whereNotNull('published_at')->first()?->workers()->count())->toBe(2);

    // Trust: reports waiting (Module 3's and Sunil's), a case that suspended JP-IT-0007 with an appeal waiting (D-094).
    $case = TrustCase::query()->where('reference', 'TC-2026-0001')->firstOrFail();
    expect(Report::query()->whereState('status', 'open')->count())->toBe(2)
        ->and($case->reports()->count())->toBe(2)
        ->and(JobPosting::query()->where('public_code', 'JP-IT-0007')->firstOrFail()->status->getValue())->toBe('suspended')
        ->and(Appeal::query()->where('status', 'submitted')->count())->toBe(1);
});

it('creates the local superuser with the superuser staff role', function () {
    $this->seed([RolesAndPermissionsSeeder::class, SuperuserSeeder::class]);

    $superuser = User::query()->where('email', SuperuserSeeder::LOCAL_EMAIL)->firstOrFail();

    expect($superuser->isStaff())->toBeTrue()
        ->and($superuser->hasStaffRole(StaffRole::Superuser))->toBeTrue()
        ->and($superuser->hasStaffPermission('horizon.view'))->toBeTrue();
});

it('keeps reference seeders idempotent', function () {
    $seeders = [CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class, SuperuserSeeder::class];

    $this->seed($seeders);
    $counts = [Country::query()->count(), Language::query()->count(), Skill::query()->count(), Role::query()->count(), User::query()->count()];
    $this->seed($seeders);

    expect([Country::query()->count(), Language::query()->count(), Skill::query()->count(), Role::query()->count(), User::query()->count()])
        ->toBe($counts);
});

it('skips the sample world and the default superuser in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // db:seed asks for confirmation in production, so run the seeder class directly.
    app(DatabaseSeeder::class)->setContainer(app())->__invoke();

    expect(Organization::query()->count())->toBe(0)
        ->and(User::query()->count())->toBe(0)
        ->and(Country::query()->count())->toBe(12);
});
