import { Form, Head, router } from '@inertiajs/react';
import { KeyRound, Ticket } from 'lucide-react';
import PilotInviteController from '@/actions/App/Http/Controllers/Admin/PilotInviteController';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import type { DataTableColumn } from '@/components/bs/data-table';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

type Invite = App.Domain.Organization.Data.PilotInviteData;
type OrganizationType = App.Domain.Organization.Enums.OrganizationType;

type Props = {
    pilotEnabled: boolean;
    invites: Invite[];
    types: OrganizationType[];
};

/** Pilot invite codes for selected partners (D-108). */
export default function PilotInvites({ pilotEnabled, invites, types }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    const columns: DataTableColumn<Invite>[] = [
        {
            key: 'code',
            header: t('pilot.column.code'),
            primary: true,
            cell: (invite) => (
                <span className="flex flex-col">
                    <span className="font-mono font-semibold" dir="ltr">
                        {invite.code}
                    </span>
                    <span className="text-xs text-muted">{invite.partnerName}</span>
                </span>
            ),
        },
        {
            key: 'type',
            header: t('pilot.column.type'),
            cell: (invite) =>
                invite.organizationType
                    ? t(`org.type.${invite.organizationType}`)
                    : t('pilot.any_type'),
        },
        {
            key: 'uses',
            header: t('pilot.column.uses'),
            cell: (invite) => (
                <span className="flex flex-col">
                    <span>
                        {t('pilot.uses', {
                            used: formatNumber(invite.usedCount),
                            max: formatNumber(invite.maxUses),
                        })}
                    </span>
                    {invite.organizations.length > 0 && (
                        <span className="text-xs text-muted">
                            {invite.organizations.join(', ')}
                        </span>
                    )}
                </span>
            ),
        },
        {
            key: 'expires',
            header: t('pilot.column.expires'),
            cell: (invite) =>
                invite.expiresAt ? formatDate(invite.expiresAt) : t('pilot.no_expiry'),
        },
        {
            key: 'status',
            header: t('admin.organizations.status'),
            cell: (invite) => <StatusChip status={invite.status} />,
        },
        {
            key: 'actions',
            header: t('team.actions'),
            align: 'end',
            cell: (invite) =>
                invite.status.value === 'active' ? (
                    <ConfirmDialog
                        destructive
                        title={t('pilot.revoke_title', { code: invite.code })}
                        description={t('pilot.revoke_body')}
                        confirmLabel={t('pilot.revoke')}
                        onConfirm={() =>
                            router.post(
                                PilotInviteController.revoke.url(invite.id),
                                {},
                                { preserveScroll: true },
                            )
                        }
                        trigger={
                            <Button variant="ghost" size="sm" className="min-h-11 md:min-h-9">
                                {t('pilot.revoke')}
                            </Button>
                        }
                    />
                ) : null,
        },
    ];

    return (
        <>
            <Head title={t('pilot.title')} />
            <div className="flex flex-col gap-6">
                <PageHeader title={t('pilot.title')} description={t('pilot.description')} />

                <p
                    className={
                        pilotEnabled
                            ? 'rounded-xl border border-ok/40 bg-ok-soft px-4 py-3 text-sm'
                            : 'rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm'
                    }
                >
                    {t(pilotEnabled ? 'pilot.mode_on' : 'pilot.mode_off')}
                </p>

                <section
                    aria-labelledby="issue-pilot-code"
                    className="rounded-2xl border border-line bg-surface p-4 sm:p-5"
                >
                    <h2 id="issue-pilot-code" className="mb-3 font-semibold">
                        {t('pilot.issue_title')}
                    </h2>
                    <Form
                        {...PilotInviteController.store.form()}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,1fr)_12rem_8rem_11rem_auto] lg:items-end"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="pilot-partner">{t('pilot.partner')}</Label>
                                    <Input
                                        id="pilot-partner"
                                        name="partner_name"
                                        required
                                        autoComplete="off"
                                        className="h-11"
                                        aria-invalid={!!errors.partner_name}
                                    />
                                    <InputError message={errors.partner_name} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="pilot-type">{t('pilot.column.type')}</Label>
                                    <NativeSelect
                                        id="pilot-type"
                                        name="organization_type"
                                        defaultValue=""
                                    >
                                        <option value="">{t('pilot.any_type')}</option>
                                        {types.map((type) => (
                                            <option key={type} value={type}>
                                                {t(`org.type.${type}`)}
                                            </option>
                                        ))}
                                    </NativeSelect>
                                    <InputError message={errors.organization_type} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="pilot-uses">{t('pilot.max_uses')}</Label>
                                    <Input
                                        id="pilot-uses"
                                        name="max_uses"
                                        type="number"
                                        min={1}
                                        max={50}
                                        defaultValue={1}
                                        required
                                        className="h-11"
                                        aria-invalid={!!errors.max_uses}
                                    />
                                    <InputError message={errors.max_uses} />
                                </div>
                                <div className="flex flex-col gap-1.5">
                                    <Label htmlFor="pilot-expires">{t('pilot.expires_on')}</Label>
                                    <Input
                                        id="pilot-expires"
                                        name="expires_on"
                                        type="date"
                                        className="h-11"
                                        aria-invalid={!!errors.expires_on}
                                    />
                                    <InputError message={errors.expires_on} />
                                </div>
                                <Button type="submit" className="min-h-11" disabled={processing}>
                                    {processing ? <Spinner /> : <KeyRound aria-hidden />}
                                    {t('pilot.issue')}
                                </Button>
                            </>
                        )}
                    </Form>
                </section>

                <DataTable
                    caption={t('pilot.title')}
                    columns={columns}
                    rows={invites}
                    rowKey={(invite) => invite.id}
                    empty={
                        <EmptyState
                            icon={Ticket}
                            title={t('pilot.empty_title')}
                            description={t('pilot.empty_body')}
                        />
                    }
                />
            </div>
        </>
    );
}
