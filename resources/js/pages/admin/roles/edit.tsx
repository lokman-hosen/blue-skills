import { Head, router } from '@inertiajs/react';
import { Lock, RotateCcw, Save, Trash2 } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { PageHeader } from '@/components/bs/page-header';
import { RoleDialog } from '@/components/bs/roles/role-dialog';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Role = App.Domain.Identity.Data.RoleSummaryData;
type Group = App.Domain.Identity.Data.RoleGroupData;
type Permission = App.Domain.Identity.Data.RolePermissionData;

/**
 * One role's permissions, grouped by area and module (D-112). Locked permissions show why; the
 * Superuser's own role is read-only. Saving lists what changes before it is sent.
 */
export default function EditRole({ role, groups }: { role: Role; groups: Group[] }) {
    const { t } = useTranslation();
    const all = useMemo(
        () => groups.flatMap((group) => group.modules.flatMap((module) => module.permissions)),
        [groups],
    );
    const initial = useMemo(
        () => new Set(all.filter((permission) => permission.granted).map((p) => p.name)),
        [all],
    );
    const [granted, setGranted] = useState<Set<string>>(initial);
    // After a save or reset the server sends the role again; start from what it now holds.
    useEffect(() => setGranted(initial), [initial]);
    const [confirming, setConfirming] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | undefined>();

    const gains = all.filter((p) => granted.has(p.name) && !initial.has(p.name));
    const losses = all.filter((p) => !granted.has(p.name) && initial.has(p.name));
    const dirty = gains.length + losses.length > 0;

    const set = (permissions: Permission[], on: boolean) =>
        setGranted((current) => {
            const next = new Set(current);
            permissions
                .filter((p) => p.lockedReason === null)
                .forEach((p) => (on ? next.add(p.name) : next.delete(p.name)));

            return next;
        });

    const save = () => {
        setProcessing(true);
        router.put(
            admin.roles.update.url(role.name),
            { permissions: [...granted] },
            {
                preserveScroll: true,
                onError: (errors) => setError(errors.permissions ?? Object.values(errors)[0]),
                onSuccess: () => setError(undefined),
                onFinish: () => {
                    setProcessing(false);
                    setConfirming(false);
                },
            },
        );
    };

    return (
        <>
            <Head title={`${role.label} · ${t('roles.title')}`} />
            <div className="flex flex-col gap-6 pb-24">
                <PageHeader
                    eyebrow={t(`roles.kind.${role.kind}`)}
                    title={role.label}
                    description={role.description ?? t('roles.body')}
                    back={{ href: admin.roles.index(), label: t('roles.back') }}
                    actions={
                        role.custom ? (
                            <>
                                <StatusChip label={t('roles.custom')} tone="stamp" icon={false} />
                                <RoleDialog role={role} />
                                <ConfirmDialog
                                    destructive
                                    title={t('roles.delete_title', { role: role.label })}
                                    description={t('roles.delete_body')}
                                    confirmLabel={t('roles.delete')}
                                    onConfirm={() =>
                                        router.delete(admin.roles.destroy.url(role.name), {
                                            preserveScroll: true,
                                            onError: (errors) => setError(Object.values(errors)[0]),
                                        })
                                    }
                                    trigger={
                                        <Button
                                            variant="ghost"
                                            className="min-h-11 text-bad hover:bg-bad-soft hover:text-bad"
                                        >
                                            <Trash2 aria-hidden />
                                            {t('roles.delete')}
                                        </Button>
                                    }
                                />
                            </>
                        ) : (
                            role.changed &&
                            !role.locked && <StatusChip label={t('roles.changed')} tone="warn" />
                        )
                    }
                />

                {role.custom && role.basedOnLabel && (
                    <p className="rounded-xl bg-brand-soft px-4 py-3 text-sm">
                        {t('roles.custom_note', { role: role.basedOnLabel })}
                    </p>
                )}

                {role.locked && (
                    <p className="flex items-start gap-2 rounded-xl bg-brand-soft px-4 py-3 text-sm">
                        <Lock aria-hidden className="mt-0.5 size-4 shrink-0 text-brand" />
                        {t('roles.superuser_note')}
                    </p>
                )}
                <InputError message={error} />

                {groups.map((group) => (
                    <section
                        key={group.key}
                        aria-labelledby={`group-${group.key}`}
                        className="flex flex-col gap-3"
                    >
                        <h2 id={`group-${group.key}`} className="font-display text-lg font-bold">
                            {group.label}
                        </h2>
                        <div className="grid gap-3 lg:grid-cols-2">
                            {group.modules.map((module) => {
                                const editable = module.permissions.filter(
                                    (p) => p.lockedReason === null,
                                );
                                const allOn = editable.every((p) => granted.has(p.name));

                                return (
                                    <div
                                        key={module.key}
                                        role="group"
                                        aria-labelledby={`module-${group.key}-${module.key}`}
                                        className="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface p-4"
                                    >
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <h3
                                                id={`module-${group.key}-${module.key}`}
                                                className="font-semibold"
                                            >
                                                {module.label}
                                            </h3>
                                            {editable.length > 1 && !role.locked && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="min-h-11 md:min-h-9"
                                                    onClick={() => set(editable, !allOn)}
                                                >
                                                    {allOn
                                                        ? t('roles.clear_all')
                                                        : t('roles.select_all')}
                                                    <span className="sr-only">
                                                        {' '}
                                                        · {module.label}
                                                    </span>
                                                </Button>
                                            )}
                                        </div>
                                        <ul className="flex flex-col gap-3">
                                            {module.permissions.map((permission) => (
                                                <PermissionRow
                                                    key={permission.name}
                                                    permission={permission}
                                                    checked={
                                                        permission.lockedReason !== null
                                                            ? permission.granted
                                                            : granted.has(permission.name)
                                                    }
                                                    onChange={(on) => set([permission], on)}
                                                />
                                            ))}
                                        </ul>
                                    </div>
                                );
                            })}
                        </div>
                    </section>
                ))}
            </div>

            {!role.locked && (
                <div className="sticky bottom-0 z-20 -mx-4 flex flex-wrap items-center justify-end gap-2 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur print:hidden">
                    {role.changed && (
                        <ConfirmDialog
                            destructive
                            title={t('roles.reset_title', { role: role.label })}
                            description={t('roles.reset_body')}
                            confirmLabel={t('roles.reset')}
                            onConfirm={() =>
                                router.post(
                                    admin.roles.reset.url(role.name),
                                    {},
                                    {
                                        preserveScroll: true,
                                        onError: (errors) => setError(Object.values(errors)[0]),
                                    },
                                )
                            }
                            trigger={
                                <Button variant="outline" className="me-auto min-h-11">
                                    <RotateCcw aria-hidden />
                                    {t('roles.reset')}
                                </Button>
                            }
                        />
                    )}
                    {!dirty && <p className="text-sm text-muted">{t('roles.no_changes')}</p>}
                    <Button
                        className="min-h-11"
                        disabled={!dirty || processing}
                        onClick={() => setConfirming(true)}
                    >
                        {processing ? <Spinner /> : <Save aria-hidden />}
                        {t('roles.save')}
                    </Button>
                </div>
            )}

            <Dialog open={confirming} onOpenChange={setConfirming}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{t('roles.confirm_title', { role: role.label })}</DialogTitle>
                        <DialogDescription>{t('roles.confirm_body')}</DialogDescription>
                    </DialogHeader>
                    {gains.length > 0 && <ChangeList title={t('roles.will_gain')} items={gains} />}
                    {losses.length > 0 && (
                        <ChangeList title={t('roles.will_lose')} items={losses} />
                    )}
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="outline" className="min-h-11">
                                {t('common.cancel')}
                            </Button>
                        </DialogClose>
                        <Button className="min-h-11" disabled={processing} onClick={save}>
                            {processing && <Spinner />}
                            {t('roles.save')}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

function PermissionRow({
    permission,
    checked,
    onChange,
}: {
    permission: Permission;
    checked: boolean;
    onChange: (on: boolean) => void;
}) {
    const { t } = useTranslation();
    const id = `permission-${permission.name.replace('.', '-')}`;
    const locked = permission.lockedReason !== null;
    // A role the Superuser created has no defaults, so nothing is marked (D-113).
    const differs = !locked && permission.default !== null && checked !== permission.default;

    return (
        <li className="flex items-start gap-3">
            <Checkbox
                id={id}
                checked={checked}
                disabled={locked}
                onCheckedChange={(value) => onChange(value === true)}
                aria-describedby={`${id}-hint`}
                className="mt-0.5 size-5"
            />
            <div className="flex min-w-0 flex-col gap-0.5">
                <label htmlFor={id} className="flex flex-wrap items-center gap-2 font-medium">
                    {permission.label}
                    {locked && <Lock aria-hidden className="size-3.5 text-muted" />}
                    {differs && (
                        <StatusChip
                            label={
                                permission.default ? t('roles.default_on') : t('roles.default_off')
                            }
                            tone="warn"
                            icon={false}
                        />
                    )}
                </label>
                <p id={`${id}-hint`} className="text-sm text-muted">
                    {permission.hint}
                    {locked && <span className="block text-ink">{permission.lockedReason}</span>}
                </p>
            </div>
        </li>
    );
}

function ChangeList({ title, items }: { title: string; items: Permission[] }) {
    return (
        <div className="flex flex-col gap-1 text-sm">
            <p className="font-semibold">{title}</p>
            <ul className="list-disc ps-5">
                {items.map((item) => (
                    <li key={item.name}>{item.label}</li>
                ))}
            </ul>
        </div>
    );
}
