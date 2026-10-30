<?php

namespace App\Http\Controllers\Shared;

use App\Domain\Manual\Data\ManualRoleData;
use App\Domain\Manual\Enums\ManualRole;
use App\Domain\Manual\Support\ManualLibrary;
use App\Domain\Manual\Support\ManualRoles;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The User Manual (D-110): everyone reads the manual of their own role(s); Superusers can open
 * every role's manual. Opening another role's manual by URL is refused here, not only hidden.
 */
class ManualController extends Controller
{
    public function __construct(
        private readonly ManualRoles $roles,
        private readonly ManualLibrary $library,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $role = $this->roles->heldBy($user)[0] ?? $this->roles->readableBy($user)[0] ?? null;

        return $this->render($request, $role);
    }

    public function show(Request $request, ManualRole $role): Response
    {
        Gate::authorize('viewManual', $role);

        return $this->render($request, $role);
    }

    private function render(Request $request, ?ManualRole $role): Response
    {
        $user = $request->user();
        abort_if($user === null, 403);

        return Inertia::render('manual/show', [
            'manual' => $role !== null ? $this->library->load($role, app()->getLocale()) : null,
            'roles' => array_map(fn (ManualRole $readable) => ManualRoleData::fromRole($readable), $this->roles->readableBy($user)),
            'canReadAll' => $this->roles->canReadAll($user),
        ]);
    }
}
