<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Public job search over published jobs only (D-053: Postgres now, Scout later). Workers
 * browse by destination, trade, salary and conditions (spec §8).
 */
final class JobSearch
{
    public const SORTS = ['newest', 'deadline'];

    /**
     * @param  array{q?: string|null, country?: string|null, occupation?: string|null, no_worker_costs?: bool, accommodation?: bool, salary_disclosed?: bool, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, JobPosting>
     */
    public static function run(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = JobPosting::query()
            ->whereState('status', Published::class)
            ->where(fn (Builder $query) => $query->whereNull('deadline')->orWhere('deadline', '>=', today()))
            ->with(['employer', 'organization', 'agency', 'mandate', 'occupation', 'destinationCountry', 'costs', 'skills']);

        if (filled($filters['q'] ?? null)) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim((string) $filters['q'])).'%';
            $query->where(fn (Builder $query) => $query
                ->where('title', 'ilike', $term)
                ->orWhere('destination_city', 'ilike', $term)
                ->orWhere('public_code', 'ilike', $term)
                ->orWhereHas('employer', fn (Builder $employer) => $employer->where('display_name', 'ilike', $term))
                ->orWhereHas('occupation', fn (Builder $occupation) => $occupation->whereRaw("name->>'en' ilike ?", [$term]))
                ->orWhereHas('skills', fn (Builder $skill) => $skill->whereRaw("skills.name->>'en' ilike ?", [$term])));
        }

        if (filled($filters['country'] ?? null)) {
            $query->where('destination_country_code', $filters['country']);
        }

        if (filled($filters['occupation'] ?? null)) {
            $query->whereHas('occupation', fn (Builder $occupation) => $occupation->where('slug', $filters['occupation']));
        }

        if ($filters['no_worker_costs'] ?? false) {
            $query->whereDoesntHave('costs', fn (Builder $cost) => $cost->where('payer', CostPayer::Worker))
                ->where(fn (Builder $query) => $query->whereNull('visa_payer')->orWhere('visa_payer', '!=', CostPayer::Worker))
                ->where(fn (Builder $query) => $query->whereNull('permit_payer')->orWhere('permit_payer', '!=', CostPayer::Worker));
        }

        if ($filters['accommodation'] ?? false) {
            $query->where('accommodation', BenefitProvision::Provided);
        }

        if ($filters['salary_disclosed'] ?? false) {
            $query->whereNotNull('salary_amount_minor');
        }

        ($filters['sort'] ?? 'newest') === 'deadline'
            ? $query->orderByRaw('deadline asc nulls last')
            : $query->latest('published_at');

        return $query->paginate($perPage)->withQueryString();
    }
}
