<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\CohortMemberSource;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

/**
 * Pasted roster lines ("Name, phone") so the institute can see who joined (D-082). Nobody is
 * contacted and no profile is linked: a line matches a worker only once that worker accepts.
 */
class AddRosterLines
{
    public const MAX_LINES = 300;

    /**
     * @return int Lines added (phones already on the roster are skipped).
     */
    public function handle(Cohort $cohort, string $text, User $actor): int
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: [])));

        if (count($lines) > self::MAX_LINES) {
            throw ValidationException::withMessages(['roster' => __('cohorts.roster.too_many', ['max' => self::MAX_LINES])]);
        }

        $country = $cohort->organization->country_code ?? 'BD';
        $rows = [];
        $errors = [];

        foreach ($lines as $number => $line) {
            $parts = preg_split('/\s*[,;\t]\s*/', $line) ?: [];
            $name = trim((string) ($parts[0] ?? ''));
            $raw = trim((string) ($parts[1] ?? ''));

            try {
                $phone = $raw !== '' ? (new PhoneNumber($raw, $country))->formatE164() : null;
            } catch (Throwable) {
                $phone = null;
            }

            if ($name === '' || mb_strlen($name) > 120 || $phone === null) {
                $errors[] = $number + 1;

                continue;
            }

            $rows[$phone] = $name;
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['roster' => __('cohorts.roster.bad_lines', ['lines' => implode(', ', array_slice($errors, 0, 10))])]);
        }

        $existing = $cohort->members()->whereIn('roster_phone', array_keys($rows))->pluck('roster_phone')->all();
        $new = array_diff_key($rows, array_flip($existing));

        DB::transaction(function () use ($cohort, $new): void {
            foreach ($new as $phone => $name) {
                $cohort->members()->create([
                    'roster_name' => $name,
                    'roster_phone' => $phone,
                    'status' => CohortMemberStatus::Invited,
                    'source' => CohortMemberSource::Roster,
                ]);
            }
        });

        activity('institute')->performedOn($cohort)->causedBy($actor)
            ->withProperties(['added' => count($new), 'skipped' => count($rows) - count($new)])
            ->log('cohort.roster_added');

        return count($new);
    }
}
