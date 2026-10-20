<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\User;
use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Session\Session;

class SetPreferredLocale
{
    /**
     * Remember the locale for this session and, when signed in, on the user.
     */
    public function handle(Session $session, string $locale, ?User $user = null): void
    {
        $session->put(SetLocale::SESSION_KEY, $locale);

        $user?->forceFill(['locale' => $locale])->save();
    }
}
