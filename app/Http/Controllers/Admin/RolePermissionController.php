<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\ResetRolePermissions;
use App\Domain\Identity\Actions\UpdateRolePermissions;
use App\Domain\Identity\Support\RolePermissionOverview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin › Roles & permissions (D-112): only the Superuser sees and changes what each role may do.
 */
class RolePermissionController extends Controller
{
    public function __construct(private readonly RolePermissionOverview $overview) {}

    public function index(): Response
    {
        Gate::authorize('manageRoles');

        return Inertia::render('admin/roles/index', [
            'roles' => $this->overview->summaries(),
        ]);
    }

    public function edit(string $role): Response
    {
        Gate::authorize('manageRoles');
        $model = $this->overview->find($role);

        return Inertia::render('admin/roles/edit', [
            'role' => $this->overview->summaryOf($model),
            'groups' => $this->overview->groups($model),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, string $role, UpdateRolePermissions $update): RedirectResponse
    {
        $model = $this->overview->find($role);
        $update->handle($model, $request->permissions(), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('roles.saved', ['role' => __("role.{$model->name}")])]);

        return to_route('admin.roles.edit', $model->name);
    }

    public function reset(Request $request, string $role, ResetRolePermissions $reset): RedirectResponse
    {
        Gate::authorize('manageRoles');
        $model = $this->overview->find($role);
        $reset->handle($model, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('roles.reset_done', ['role' => __("role.{$model->name}")])]);

        return to_route('admin.roles.edit', $model->name);
    }
}
