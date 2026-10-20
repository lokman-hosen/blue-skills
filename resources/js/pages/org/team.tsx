import { Form, Head, router } from '@inertiajs/react';
import { MailPlus, UserMinus } from 'lucide-react';
import TeamController from '@/actions/App/Http/Controllers/Organization/TeamController';
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

type Role = App.Domain.Identity.Enums.OrganizationRole;
type Member = App.Domain.Organization.Data.TeamMemberData;
type Invitation = App.Domain.Organization.Data.TeamInvitationData;

type Props = {
    members: Member[];
    invitations: Invitation[];
    roles: Role[];
    canManage: boolean;
};

export default function Team({ members, invitations, roles, canManage }: Props) {
    const { t, formatDate } = useTranslation();

    const changeRole = (member: Member, role: Role) =>
        router.patch(TeamController.updateRole.url(member.id), { role }, { preserveScroll: true });

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
            cell: (member) =>
                canManage ? (
                    <NativeSelect
                        aria-label={t('team.role_for', { name: member.name })}
                        value={member.role ?? ''}
                        onChange={(event) => changeRole(member, event.target.value as Role)}
                        className="h-11 min-w-44 md:h-9"
                    >
                        {member.role === null && <option value="">—</option>}
                        {roles.map((role) => (
                            <option key={role} value={role}>
                                {t(`role.${role}`)}
                            </option>
                        ))}
                    </NativeSelect>
                ) : (
                    <StatusChip
                        label={member.role ? t(`role.${member.role}`) : '—'}
                        tone="brand"
                        icon={false}
                    />
                ),
        },
        {
            key: 'joined',
            header: t('team.joined'),
            cell: (member) => (member.joinedAt ? formatDate(member.joinedAt) : '—'),
        },
    ];

    if (canManage) {
        columns.push({
            key: 'actions',
            header: t('team.actions'),
            align: 'end',
            cell: (member) =>
                member.isYou ? null : (
                    <ConfirmDialog
                        destructive
                        title={t('team.remove_title', { name: member.name })}
                        description={t('team.remove_body')}
                        confirmLabel={t('team.remove')}
                        onConfirm={() =>
                            router.delete(TeamController.remove.url(member.id), {
                                preserveScroll: true,
                            })
                        }
                        trigger={
                            <Button
                                variant="ghost"
                                size="sm"
                                className="min-h-11 text-bad hover:bg-bad-soft hover:text-bad md:min-h-9"
                            >
                                <UserMinus aria-hidden />
                                {t('team.remove')}
                            </Button>
                        }
                    />
                ),
        });
    }

    return (
        <>
            <Head title={t('nav.team')} />
            <div className="flex flex-col gap-6">
                <PageHeader title={t('nav.team')} description={t('team.description')} />

                {canManage && (
                    <section
                        aria-labelledby="invite-heading"
                        className="rounded-2xl border border-line bg-surface p-4 sm:p-5"
                    >
                        <h2 id="invite-heading" className="mb-3 font-semibold">
                            {t('team.invite_title')}
                        </h2>
                        <Form
                            {...TeamController.invite.form()}
                            options={{ preserveScroll: true }}
                            resetOnSuccess={['email']}
                            className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,14rem)_auto] sm:items-end"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="flex flex-col gap-1.5">
                                        <Label htmlFor="invite-email">{t('team.email')}</Label>
                                        <Input
                                            id="invite-email"
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
                                        <Label htmlFor="invite-role">{t('team.role')}</Label>
                                        <NativeSelect
                                            id="invite-role"
                                            name="role"
                                            defaultValue={roles[1] ?? roles[0]}
                                        >
                                            {roles.map((role) => (
                                                <option key={role} value={role}>
                                                    {t(`role.${role}`)}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                        <InputError message={errors.role} />
                                    </div>
                                    <Button
                                        type="submit"
                                        className="min-h-11"
                                        disabled={processing}
                                    >
                                        {processing ? <Spinner /> : <MailPlus aria-hidden />}
                                        {t('team.invite')}
                                    </Button>
                                </>
                            )}
                        </Form>
                    </section>
                )}

                <DataTable
                    caption={t('nav.team')}
                    columns={columns}
                    rows={members}
                    rowKey={(member) => member.id}
                />

                {invitations.length > 0 && (
                    <section aria-labelledby="pending-heading" className="flex flex-col gap-3">
                        <h2 id="pending-heading" className="font-semibold">
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
                                    {canManage && (
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="min-h-11 md:min-h-9"
                                            onClick={() =>
                                                router.delete(
                                                    TeamController.revoke.url(invitation.id),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            {t('team.revoke')}
                                        </Button>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </div>
        </>
    );
}
