<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;
use Illuminate\Support\Facades\DB;
use NumberFormatter;

/**
 * Passport readiness, availability, destinations and salary expectation.
 */
class UpdateWorkerPreferences
{
    public function __construct(private readonly ProfileCompletion $completion) {}

    /**
     * @param  array{passport_status: string, passport_valid_until_year: int|null, availability: string, destinations: list<string>, salary_amount: int|null, salary_currency: string|null, salary_period: string|null}  $fields
     */
    public function handle(WorkerProfile $profile, array $fields): WorkerProfile
    {
        DB::transaction(function () use ($profile, $fields): void {
            $hasSalary = $fields['salary_amount'] !== null && $fields['salary_currency'] !== null;

            $profile->update([
                'passport_status' => $fields['passport_status'],
                'passport_valid_until_year' => $fields['passport_valid_until_year'],
                'availability' => $fields['availability'],
                'salary_expectation_minor' => $hasSalary ? self::toMinor((int) $fields['salary_amount'], (string) $fields['salary_currency']) : null,
                'salary_currency' => $hasSalary ? $fields['salary_currency'] : null,
                'salary_period' => $hasSalary ? $fields['salary_period'] : null,
            ]);

            $profile->destinationPreferences()->delete();

            foreach ($fields['destinations'] as $index => $code) {
                $profile->destinationPreferences()->create(['country_code' => $code, 'priority' => $index + 1]);
            }

            activity('worker')->performedOn($profile)->causedBy($profile->user)
                ->withProperties(['attributes' => $fields])
                ->log('profile.preferences_updated');
        });

        $this->completion->refresh($profile);

        return $profile;
    }

    /**
     * Whole currency units to the minor unit (JPY has none, OMR has three).
     */
    public static function toMinor(int $amount, string $currency): int
    {
        $formatter = new NumberFormatter("en@currency={$currency}", NumberFormatter::CURRENCY);
        $digits = (int) $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS);

        return $amount * 10 ** $digits;
    }
}
