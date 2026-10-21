<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Config\Actions\SaveCountryRules;
use App\Domain\Config\Actions\UpdateCountry;
use App\Domain\Config\Actions\UpdateLanguageStatus;
use App\Domain\Config\Data\CountryConfigData;
use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\Language;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Verification\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveCountryRulesRequest;
use App\Http\Requests\Admin\UpdateCountryRequest;
use App\Http\Requests\Admin\UpdateLanguageRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Countries, corridor rules and languages (prototype x-config, D-093). Every change is logged.
 */
class ConfigController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manageConfig');

        return Inertia::render('admin/config/index', [
            'countries' => Country::query()->orderBy('name')->get()->map(fn (Country $country) => CountryConfigData::fromCountry($country))->values()->all(),
            'languages' => Language::query()->orderBy('english_name')->get()->map(fn (Language $language) => [
                'code' => $language->code,
                'name' => $language->english_name,
                'nativeName' => $language->native_name,
                'rtl' => $language->rtl,
                'status' => $language->status->value,
            ])->values()->all(),
            'languageStatuses' => array_map(fn (LanguageStatus $status) => $status->value, LanguageStatus::cases()),
        ]);
    }

    public function show(Country $country): Response
    {
        Gate::authorize('manageConfig');

        return Inertia::render('admin/config/country', [
            'country' => CountryConfigData::fromCountry($country, withRules: true),
            'options' => [
                'languages' => Language::query()->orderBy('english_name')->get()->map(fn (Language $language) => ['code' => $language->code, 'name' => $language->english_name])->values()->all(),
                'costCategories' => array_map(fn (JobCostCategory $category) => $category->value, JobCostCategory::cases()),
                'disclosures' => array_keys(JobCompliance::DISCLOSURES),
                'documentTypes' => array_map(fn (DocumentType $type) => $type->value, DocumentType::cases()),
            ],
        ]);
    }

    public function update(UpdateCountryRequest $request, Country $country, UpdateCountry $update): RedirectResponse
    {
        Gate::authorize('manageConfig');

        $update->handle($country, [
            'currency' => $request->string('currency')->toString(),
            'enabled_as_source' => $request->boolean('enabled_as_source'),
            'enabled_as_destination' => $request->boolean('enabled_as_destination'),
            'legal_reviewed' => $request->boolean('legal_reviewed'),
        ], $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('config.saved')]);

        return back();
    }

    public function rules(SaveCountryRulesRequest $request, Country $country, SaveCountryRules $save): RedirectResponse
    {
        Gate::authorize('manageConfig');

        /** @var array<string, mixed> $rules */
        $rules = $request->validated();
        $save->handle($country, [
            ...$rules,
            'agency_eligibility' => (string) ($rules['agency_eligibility'] ?? ''),
            'worker_cost_policy' => (string) ($rules['worker_cost_policy'] ?? ''),
            'local_notice' => (string) ($rules['local_notice'] ?? ''),
            'languages' => $rules['languages'] ?? [],
            'allowed_worker_cost_categories' => $rules['allowed_worker_cost_categories'] ?? [],
            'mandatory_disclosures' => $rules['mandatory_disclosures'] ?? [],
            'document_types' => $rules['document_types'] ?? [],
            'mobility_stage_template' => array_map(fn (array $stage) => [
                'key' => (string) ($stage['key'] ?? ''),
                'label' => (string) $stage['label'],
                'done_on_open' => (bool) ($stage['done_on_open'] ?? false),
            ], $rules['mobility_stage_template'] ?? []),
        ], $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('config.rules_saved')]);

        return to_route('admin.config.countries.show', $country);
    }

    public function language(UpdateLanguageRequest $request, Language $language, UpdateLanguageStatus $update): RedirectResponse
    {
        Gate::authorize('manageConfig');

        $update->handle($language, LanguageStatus::from($request->string('status')->toString()), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('config.saved')]);

        return back();
    }
}
