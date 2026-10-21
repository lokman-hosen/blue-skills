<?php

use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\Language;
use App\Domain\Config\Support\CountryRules;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Mobility\Support\StageTemplate;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
    $this->superuser = staffUser(StaffRole::Superuser);
});

it('edits a country and its corridor rules, logging every change', function () {
    $this->actingAs($this->superuser)->get(route('admin.config.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/config/index')->has('countries')->has('languages'));
    $this->get(route('admin.config.countries.show', 'SA'))
        ->assertInertia(fn (Assert $page) => $page->where('country.rules.document_types', ['passport', 'medical', 'police_clearance', 'certificate']));

    $this->put(route('admin.config.countries.update', 'SA'), ['currency' => 'SA', 'enabled_as_destination' => true])->assertSessionHasErrors('currency');
    $this->put(route('admin.config.countries.update', 'SA'), ['currency' => 'sar', 'enabled_as_source' => false, 'enabled_as_destination' => true, 'legal_reviewed' => true])->assertRedirect();
    $saudi = Country::query()->where('code', 'SA')->sole();
    expect($saudi->legal_reviewed_at)->not->toBeNull()
        ->and($saudi->legal_reviewed_by)->toBe($this->superuser->id)
        ->and($saudi->currency)->toBe('SAR');

    $rules = CountryRules::for($saudi);
    $rules['mandatory_disclosures'] = ['salary', 'not_a_field'];
    $this->put(route('admin.config.countries.rules', 'SA'), $rules)->assertSessionHasErrors('mandatory_disclosures.1');

    $rules['mandatory_disclosures'] = ['salary', 'food'];
    $rules['worker_cost_policy'] = 'No fees of any kind.';
    $rules['mobility_stage_template'] = [
        ['key' => 'contract', 'label' => 'Contract signed', 'done_on_open' => true],
        ['key' => '', 'label' => 'Biometrics appointment', 'done_on_open' => false],
        ['key' => 'joined', 'label' => 'Joined', 'done_on_open' => false],
    ];
    $this->put(route('admin.config.countries.rules', 'SA'), $rules)->assertRedirect(route('admin.config.countries.show', 'SA'));

    expect(array_column(StageTemplate::for('SA'), 'key'))->toBe(['contract', 'biometrics_appointment', 'joined'])
        ->and(StageTemplate::for('SA')[0]['done_on_open'] ?? false)->toBeTrue()
        ->and(CountryRules::for($saudi)['worker_cost_policy'])->toBe('No fees of any kind.')
        ->and(Activity::query()->where('description', 'country.rule_updated')->count())->toBe(3);

    $this->put(route('admin.config.languages.update', 'hi'), ['status' => 'active'])->assertRedirect();
    expect(Language::query()->where('code', 'hi')->sole()->status)->toBe(LanguageStatus::Active);

    $this->actingAs(staffUser(StaffRole::TrustOfficer))->get(route('admin.config.index'))->assertForbidden();
});

it('adds, renames and removes taxonomy items, keeping what is in use', function () {
    $this->actingAs($this->superuser)->post(route('admin.taxonomy.store'), ['type' => 'sector', 'names' => ['en' => 'Hospitality', 'bn' => 'আতিথেয়তা']])->assertRedirect();
    $sector = Sector::query()->where('slug', 'hospitality')->sole();
    expect($sector->name)->toEqual(['en' => 'Hospitality', 'bn' => 'আতিথেয়তা']);

    $this->post(route('admin.taxonomy.store'), ['type' => 'occupation', 'names' => ['en' => 'Cook']])->assertSessionHasErrors('parent_id');
    $this->post(route('admin.taxonomy.store'), ['type' => 'occupation', 'parent_id' => $sector->id, 'names' => ['en' => 'Cook']]);
    $cook = Occupation::query()->where('slug', 'cook')->sole();
    $this->post(route('admin.taxonomy.store'), ['type' => 'occupation', 'parent_id' => $sector->id, 'names' => ['en' => 'Cook']])->assertSessionHasErrors('names.en');
    $this->post(route('admin.taxonomy.store'), ['type' => 'skill', 'parent_id' => $cook->id, 'names' => ['en' => 'Tandoor cooking']]);
    $skill = Skill::query()->where('slug', 'tandoor-cooking')->sole();

    $this->put(route('admin.taxonomy.update', ['skill', $skill->id]), ['names' => ['en' => 'Tandoor and grill']])->assertRedirect();
    expect($skill->fresh()?->slug)->toBe('tandoor-cooking')
        ->and($skill->fresh()?->name['en'])->toBe('Tandoor and grill');

    $this->get(route('admin.taxonomy.occupations.show', $cook))->assertInertia(fn (Assert $page) => $page->where('skills.0.usage', 0)->where('occupation.usage', 1));

    // In use: a worker picked the occupation, so it can only be renamed.
    makeWorker(['occupation_id' => $cook->id]);
    $this->delete(route('admin.taxonomy.destroy', ['occupation', $cook->id]))->assertSessionHasErrors('item');
    $this->delete(route('admin.taxonomy.destroy', ['skill', $skill->id]))->assertRedirect();
    expect(Skill::query()->whereKey($skill->id)->exists())->toBeFalse();

    $this->actingAs(staffUser(StaffRole::VerificationOfficer))->get(route('admin.taxonomy.index'))->assertForbidden();
});
