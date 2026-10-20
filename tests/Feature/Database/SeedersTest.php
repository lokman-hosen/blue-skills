<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\Language;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Skill;
use Database\Seeders\CountrySeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperuserSeeder;
use Database\Seeders\TaxonomySeeder;
use Spatie\Permission\Models\Role;

it('seeds the reference data and the sample world', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Country::query()->pluck('code')->sort()->values()->all())
        ->toBe(['AE', 'BD', 'CZ', 'IN', 'JP', 'LK', 'NP', 'OM', 'PK', 'PL', 'QA', 'SA'])
        ->and(Language::query()->pluck('code')->sort()->values()->all())
        ->toBe(['ar', 'bn', 'en', 'hi', 'ne', 'si', 'ta', 'ur'])
        ->and(Language::query()->where('rtl', true)->pluck('code')->sort()->values()->all())->toBe(['ar', 'ur'])
        ->and(Organization::query()->pluck('display_name'))
        ->toContain('ABC Contracting', 'Northstar Manpower', 'Meghna Technical Training Centre')
        ->and(JobPosting::query()->count())->toBe(5)
        ->and(Application::query()->count())->toBeGreaterThan(0);

    $job = JobPosting::query()->where('public_code', 'UAE-EL-0001')->firstOrFail();
    expect($job->status)->toBeInstanceOf(Published::class)
        ->and($job->employer->display_name)->toBe('ABC Contracting')
        ->and($job->agency?->display_name)->toBe('Northstar Manpower')
        ->and($job->mandate?->isVerified())->toBeTrue();

    $rahim = User::query()->where('name', 'Rahim Uddin')->firstOrFail();
    expect($rahim->isWorker())->toBeTrue()
        ->and($rahim->workerProfile?->voiceAnswers)->toHaveCount(7);
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
