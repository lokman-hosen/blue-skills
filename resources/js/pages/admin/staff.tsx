import { Form, Head, router } from '@inertiajs/react';
import { KeyRound, MailPlus, UserX } from 'lucide-react';
import { toast } from 'sonner';
import StaffController from '@/actions/App/Http/Controllers/Admin/StaffController';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import type { DataTableColumn } from '@/components/bs/data-table';
import { DataTable } from '@/components/bs/data-table';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

/** A role name: built-in, or created by the Superuser (D-113). Label via `role.{name}`. */
type Role = string;
type Member = App.Domain.Identity.Data.StaffMemberData;

type Props = {
    members: Member[];
    invitations: App.Domain.Identity.Data.StaffInvitationData[];
    roles: Role[];
};

export default function Staff({ members, invitations, roles }: Props) {
    const { t, formatDate } = useTranslation();

    const columns: DataTableColumn<Member>[] = [
        {
            key: 'name',
            header: t('team.member'),
            primary: true,
            cell: (member) => (
                <span className="flex flex-col">
                    <span className="font-semibold">
                        {member.name}
                        {member.isYou && (
                            <span className="ms-1 text-xs font-normal text-muted">
                                ({t('team.you')})
                            </span>
                        )}
                    </span>
                    <span className="text-xs text-muted">{member.email}</span>
                </span>
            ),
        },
        {
            key: 'role',
            header: t('team.role'),
            cell: (member) => (
                <NativeSelect
                    aria-label={t('team.role_for', { name: member.name })}
                    value={member.role ?? ''}
                    onChange={(event) =>
                        router.patch(
                            StaffController.updateRole.url(member.id),
                            { role: event.target.value },
                            { preserveScroll: true, onError: showError },
                        )
                    }
                    className="h-11 min-w-48 md:h-9"
                >
                    {member.role === null && <option value="">—</option>}
                    {roles.map((role) => (
                        <option key={role} value={role}>
                            {t(`role.${role}`)}
                        </option>
                    ))}
                </NativeSelect>
            ),
        },
        {
            key: 'two_factor',
            header: t('staff.two_factor'),
            cell: (member) =>
                member.twoFactorEnabled ? (
                    <StatusChip label={t('staff.two_factor_on')} tone="ok" />
                ) : (
                    <StatusChip label={t('staff.two_factor_off')} tone="warn" />
                ),
        },
        {
            key: 'actions',
            header: t('team.actions'),
            align: 'end',
            cell: (member) =>
                member.isYou ? null : (
                    <div className="flex flex-wrap justify-end gap-1">
                        {member.twoFactorEnabled && (
                            <ConfirmDialog
                                destructive
                                title={t('staff.reset_two_factor_title', { name: member.name })}
                                description={t('staff.reset_two_factor_body')}
                                confirmLabel={t('staff.reset_two_factor')}
                                onConfirm={() =>
                                    router.post(
                                        StaffController.resetTwoFactor.url(member.id),
                                        {},
                                        { preserveScroll: true, onError: showError },
                                    )
                                }
                                trigger={
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="min-h-11 md:min-h-9"
                                    >
                                        <KeyRound aria-hidden />
                                        {t('staff.reset_two_factor')}
                                    </Button>
                                }
                            />
                        )}
                        <ConfirmDialog
                            destructive
                            title={t('staff.deactivate_title', { name: member.name })}
                            description={t('staff.deactivate_body')}
                            confirmLabel={t('staff.deactivate')}
                            onConfirm={() =>
                                router.delete(StaffController.deactivate.url(member.id), {
                                    preserveScroll: true,
                                    onError: showError,
                                })
                            }
                            trigger={
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="min-h-11 text-bad hover:bg-bad-soft hover:text-bad md:min-h-9"
                                >
                                    <UserX aria-hidden />
                                    {t('staff.deactivate')}
                                </Button>
                            }
                        />
                    </div>
                ),
        },
    ];

    return (
        <>
            <Head title={t('nav.staff')} />
            <div className="flex flex-col gap-6">
                <PageHeader title={t('nav.staff')} description={t('staff.description')} />

                <section
                    aria-labelledby="invite-staff"
                    className="rounded-2xl border border-line bg-surface p-4 sm:p-5"
                >
                    <h2 id="invite-staff" className="mb-3 font-semibold">
                        {t('staff.invite_title')}
                    </h2>
                    <Form
                        {...StaffController.invite.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess={['email']}
                        className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,14rem)_auto] sm:items-end"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="staff-email">{t('team.email')}</Label>
                                    <Input
                                        id="staff-email"
                                        name="email"
                                        type="email"
                                        required
                                        autoComplete="off"
                                        className="h-11"
                                        aria-invalid={!!errors.email}
                                    />
                                    <InputError message={errors.email} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="staff-role">{t('team.role')}</Label>
                                    <NativeSelect
                                        id="staff-role"
                                        name="role"
                                        defaultValue="verification_officer"
                                    >
                                        {roles.map((role) => (
                                            <option key={role} value={role}>
                                                {t(`role.${role}`)}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.role} />
                                </div>
                                <Button type="submit" className="min-h-11" disabled={processing}>
                                    {processing ? <Spinner /> : <MailPlus aria-hidden />}
                                    {t('team.invite')}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <DataTable
                    caption={t('nav.staff')}
                    columns={columns}
                    rows={members}
                    rowKey={(member) => member.id}
                />

                {invitations.length > 0 && (
                    <section aria-labelledby="staff-pending" className="flex flex-col gap-3">
                        <h2 id="staff-pending" className="font-semibold">
                            {t('team.pending_title')}
                        </h2>
                        <ul className="flex flex-col gap-2">
                            {invitations.map((invitation) => (
                                <li
                                    key={invitation.id}
                                    className="flex flex-wrap items-center gap-3 rounded-xl border border-line bg-surface p-3"
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-semibold">
                                            {invitation.email}
                                        </span>
                                        <span className="block text-xs text-muted">
                                            {t(`role.${invitation.role}`)} ·{' '}
                                            {t('team.expires', {
                                                date: formatDate(invitation.expiresAt),
                                            })}
                                        </span>
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="min-h-11 md:min-h-9"
                                        onClick={() =>
                                            router.delete(
                                                StaffController.revoke.url(invitation.id),
                                                { preserveScroll: true, onError: showError },
                                            )
                                        }
                                    >
                                        {t('team.revoke')}
                                    </Button>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}

/** Refusals such as "needs at least one superuser" come back as a validation error on `member`. */
function showError(errors: Record<string, string>) {
    const message = errors.member ?? Object.values(errors)[0];

    if (message) {
        toast.error(message);
    }
}
