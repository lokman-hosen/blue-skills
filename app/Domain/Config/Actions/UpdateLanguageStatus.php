<?php

namespace App\Domain\Config\Actions;

use App\Domain\Config\Enums\LanguageStatus;
use App\Domain\Config\Models\Language;
use App\Domain\Identity\Models\User;

/**
 * Whether a language is offered to users, in review or planned (D-093).
 */
class UpdateLanguageStatus
{
    public function handle(Language $language, LanguageStatus $status, User $actor): void
    {
        $old = $language->status;
        $language->update(['status' => $status]);

        activity('config')->performedOn($language)->causedBy($actor)
            ->withProperties(['old' => ['status' => $old->value], 'attributes' => ['status' => $status->value]])
            ->log('language.updated');
    }
}
