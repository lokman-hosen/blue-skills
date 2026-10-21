<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Config\Models\Language;
use App\Domain\Taxonomy\Actions\RemoveTaxonomyItem;
use App\Domain\Taxonomy\Actions\SaveTaxonomyItem;
use App\Domain\Taxonomy\Data\TaxonomyItemData;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTaxonomyItemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The occupation taxonomy: Sector → Occupation → Specialization → Skill (spec §11, D-093).
 */
class TaxonomyController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('manageTaxonomy');

        $sectors = Sector::query()->orderBy('sort_order')->with(['occupations' => fn ($query) => $query->orderBy('slug')])->get();

        return Inertia::render('admin/taxonomy/index', [
            'sectors' => $sectors->map(fn (Sector $sector) => TaxonomyItemData::fromItem(
                $sector,
                $sector->occupations->map(fn (Occupation $occupation) => TaxonomyItemData::fromItem($occupation))->values()->all(),
            ))->values()->all(),
            'locales' => $this->locales(),
        ]);
    }

    public function show(Occupation $occupation): Response
    {
        Gate::authorize('manageTaxonomy');

        return Inertia::render('admin/taxonomy/occupation', [
            'occupation' => TaxonomyItemData::fromItem($occupation),
            'specializations' => $occupation->specializations()->orderBy('slug')->get()->map(fn (Specialization $item) => TaxonomyItemData::fromItem($item))->values()->all(),
            'skills' => $occupation->skills()->orderBy('slug')->get()->map(fn (Skill $item) => TaxonomyItemData::fromItem($item))->values()->all(),
            'locales' => $this->locales(),
        ]);
    }

    public function store(SaveTaxonomyItemRequest $request, SaveTaxonomyItem $save): RedirectResponse
    {
        Gate::authorize('manageTaxonomy');

        /** @var array<string, string> $names */
        $names = array_map('strval', array_filter($request->array('names'), 'is_string'));
        $save->handle($request->string('type')->toString(), null, $request->string('parent_id')->toString() ?: null, $names, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('taxonomy.added')]);

        return back();
    }

    public function update(SaveTaxonomyItemRequest $request, string $type, string $id, SaveTaxonomyItem $save): RedirectResponse
    {
        Gate::authorize('manageTaxonomy');

        /** @var array<string, string> $names */
        $names = array_map('strval', array_filter($request->array('names'), 'is_string'));
        $save->handle($type, $this->item($type, $id), null, $names, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('taxonomy.renamed')]);

        return back();
    }

    public function destroy(Request $request, string $type, string $id, RemoveTaxonomyItem $remove): RedirectResponse
    {
        Gate::authorize('manageTaxonomy');

        $item = $this->item($type, $id);
        $remove->handle($item, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('taxonomy.removed')]);

        return $item instanceof Occupation ? to_route('admin.taxonomy.index') : back();
    }

    private function item(string $type, string $id): Sector|Occupation|Specialization|Skill
    {
        return match ($type) {
            'sector' => Sector::query()->findOrFail($id),
            'occupation' => Occupation::query()->findOrFail($id),
            'specialization' => Specialization::query()->findOrFail($id),
            default => Skill::query()->findOrFail($id),
        };
    }

    /**
     * Launch languages the names are written in (English first).
     *
     * @return list<array{code: string, name: string}>
     */
    private function locales(): array
    {
        return Language::query()->active()->orderByRaw("code <> 'en'")->orderBy('code')->get()
            ->map(fn (Language $language) => ['code' => $language->code, 'name' => $language->english_name])->values()->all();
    }
}
