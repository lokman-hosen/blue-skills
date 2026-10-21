<?php

namespace App\Domain\Applications\Support;

use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Structured filters over discoverable workers (spec §8); the text search also matches skill
 * names (English taxonomy names for now, D-058). Results are ordered by when the
 * profile was last updated, never by a score or by video (product rule 12).
 */
final class TalentSearch
{
    public const PER_PAGE = 20;

    /**
     * @param  array{q: ?string, occupation: ?string, min_years: ?int, country: ?string, worked_in: ?string, passport_ready: bool, available_soon: bool, language: ?string, verified_evidence: bool, skillshow: bool}  $filters
     * @return LengthAwarePaginator<int, WorkerProfile>
     */
    public static function run(array $filters): LengthAwarePaginator
    {
        return WorkerProfile::query()
            ->discoverable()
            ->with(['user', 'occupation', 'currentCountry', 'experiences.country', 'workerSkills.skill', 'languages.language', 'skillShows', 'evidenceItems'])
            ->when($filters['q'], function (Builder $query, string $q): void {
                $term = '%'.addcslashes($q, '%_\\').'%';
                $query->where(fn (Builder $match) => $match
                    ->where('headline', 'ilike', $term)
                    ->orWhere('reference', 'ilike', $term)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'ilike', $term))
                    ->orWhereHas('skills', fn (Builder $skill) => $skill->whereRaw("skills.name->>'en' ilike ?", [$term])));
            })
            ->when($filters['occupation'], fn (Builder $query, string $slug) => $query->whereHas('occupation', fn (Builder $occupation) => $occupation->where('slug', $slug)))
            ->when($filters['min_years'], fn (Builder $query, int $years) => $query->where('years_experience', '>=', $years))
            ->when($filters['country'], fn (Builder $query, string $code) => $query->where('current_country_code', $code))
            ->when($filters['worked_in'], fn (Builder $query, string $code) => $query->whereHas('experiences', fn (Builder $experience) => $experience->where('country_code', $code)))
            ->when($filters['passport_ready'], fn (Builder $query) => $query->where('passport_status', PassportStatus::Ready))
            ->when($filters['available_soon'], fn (Builder $query) => $query->whereIn('availability', [Availability::Immediately, Availability::Within30Days]))
            ->when($filters['language'], fn (Builder $query, string $code) => $query->whereHas('languages', fn (Builder $language) => $language->where('language_code', $code)))
            ->when($filters['verified_evidence'], fn (Builder $query) => $query->whereHas('evidenceItems', fn (Builder $evidence) => $evidence->where('status', 'issuer_verified')->whereNull('hidden_at')))
            ->when($filters['skillshow'], fn (Builder $query) => $query->whereHas('skillShows', fn (Builder $skillShow) => $skillShow
                ->where('moderation_status', ModerationStatus::Approved)
                ->where('visibility', ProfileVisibility::EmployersAndAgencies)))
            ->latest('updated_at')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }
}
