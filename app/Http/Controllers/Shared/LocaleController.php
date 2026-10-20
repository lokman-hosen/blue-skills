<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Identity\Actions\SetPreferredLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function update(UpdateLocaleRequest $request, SetPreferredLocale $setPreferredLocale): RedirectResponse
    {
        $setPreferredLocale->handle($request->session(), $request->string('locale')->toString(), $request->user());

        return back();
    }
}
