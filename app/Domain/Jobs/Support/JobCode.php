<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Taxonomy\Models\Occupation;
use Illuminate\Support\Str;

/**
 * Public job codes such as AE-EL-4821: destination, trade, four random digits. Random so the
 * code reveals nothing about how many jobs exist (CLAUDE.md: no sequential ids in URLs).
 */
final class JobCode
{
    public static function generate(string $countryCode, Occupation $occupation): string
    {
        $trade = Str::upper(Str::substr(Str::before($occupation->slug, '-'), 0, 2));

        do {
            $code = sprintf('%s-%s-%04d', Str::upper($countryCode), $trade, random_int(0, 9999));
        } while (JobPosting::withTrashed()->where('public_code', $code)->exists());

        return $code;
    }
}
