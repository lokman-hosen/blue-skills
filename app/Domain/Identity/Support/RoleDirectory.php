<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Organization\Enums\OrganizationType;
use Spatie\Permission\Models\Role;

/**
 * Every role there is: the built-in ones (OrganizationRole, StaffRole) and the ones the Superuser
 * created (D-113). Pickers, validation and labels ask here instead of the enums. Within an HTTP
 * request the custom roles are read once.
 */
class RoleDirectory
{
    /** Internal names of created roles start with this; the label is what people see. */
    public const CUSTOM_PREFIX = 'c_';

    public function kindOf(string $name): ?RoleKind
    {
        return RoleKind::of($name) ?? (($kind = $this->custom()[$name]['kind'] ?? null) !== null ? RoleKind::tryFrom($kind) : null);
    }

    public function exists(string $name): bool
    {
        return $this->kindOf($name) !== null;
    }

    public function isSystem(string $name): bool
    {
        return RoleKind::of($name) !== null;
    }

    public function label(string $name): string
    {
        return $this->custom()[$name]['label'] ?? __("role.{$name}");
    }

    /** The role a created role started from; its manual follows that role. */
    public function basedOn(string $name): ?string
    {
        return $this->custom()[$name]['based_on'] ?? null;
    }

    /**
     * Role names of one kind: the built-in ones in their usual order, then created ones by name.
     *
     * @return list<string>
     */
    public function namesFor(RoleKind $kind): array
    {
        $custom = array_keys(array_filter($this->custom(), fn (array $role) => $role['kind'] === $kind->value));
        usort($custom, fn (string $a, string $b) => strcasecmp($this->label($a), $this->label($b)));

        return [...$kind->roleNames(), ...$custom];
    }

    /**
     * @return list<string>
     */
    public function namesForOrganization(OrganizationType $type): array
    {
        return $this->namesFor(RoleKind::from($type->value));
    }

    public function fitsOrganization(string $name, OrganizationType $type): bool
    {
        return $this->kindOf($name)?->value === $type->value;
    }

    /** Only built-in owner roles are owners; a created role never is (D-113). */
    public function isOwner(string $name): bool
    {
        return OrganizationRole::tryFrom($name)?->isOwner() === true;
    }

    public function isSuperuser(string $name): bool
    {
        return $name === StaffRole::Superuser->value;
    }

    /**
     * `role.{name}` => label for every created role, merged into the interface strings so
     * `t('role.' + name)` works for them too.
     *
     * @return array<string, string>
     */
    public function customLabels(): array
    {
        $labels = [];

        foreach ($this->custom() as $name => $role) {
            $labels["role.{$name}"] = $role['label'];
        }

        return $labels;
    }

    /**
     * @return array<string, array{kind: string, label: string, based_on: ?string}>
     */
    private function custom(): array
    {
        $load = fn (): array => Role::query()
            ->where('guard_name', 'web')
            ->whereNull(config('permission.column_names.team_foreign_key'))
            ->where('system', false)
            ->whereNotNull('kind')
            ->get(['name', 'kind', 'label', 'based_on'])
            ->mapWithKeys(fn (Role $role) => [$role->name => [
                'kind' => (string) $role->getAttribute('kind'),
                'label' => (string) ($role->getAttribute('label') ?? $role->name),
                'based_on' => $role->getAttribute('based_on'),
            ]])
            ->all();

        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return $load();
        }

        $attributes = request()->attributes;

        if (! $attributes->has('custom_roles')) {
            $attributes->set('custom_roles', $load());
        }

        return $attributes->get('custom_roles');
    }

    /** Forget what this request read, after a role is created, renamed or deleted. */
    public function forget(): void
    {
        request()->attributes->remove('custom_roles');
    }
}
