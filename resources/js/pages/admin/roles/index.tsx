import { Head, Link } from '@inertiajs/react';
import { Lock, Pencil, Eye } from 'lucide-react';
import type { DataTableColumn } from '@/components/bs/data-table';
import { DataTable } from '@/components/bs/data-table';
import { PageHeader } from '@/components/bs/page-header';
import { RoleDialog } from '@/components/bs/roles/role-dialog';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Role = App.Domain.Identity.Data.RoleSummaryData;

const KINDS = ['staff', 'employer', 'agency', 'institute'];

/**
 * Admin › Roles & permissions (D-112, D-113): every role, its people and its permissions, and new
 * roles per kind. Superuser only.
 */
export default function Roles({ roles }: { roles: Role[] }) {
    const { t, formatNumber } = useTranslation();

    const columns: DataTableColumn<Role>[] = [
        {
            key: 'role',
            header: t('roles.column.role'),
            primary: true,
            cell: (role) => (
                <span className="flex flex-wrap items-center gap-2">
                    <span className="font-semibold">{role.label}</span>
                    {role.locked && (
                        <span title={t('roles.locked_hint')}>
                            <StatusChip label={t('roles.locked')} tone="brand" icon={Lock} />
                        </span>
                    )}
                    {role.changed && (
                        <span title={t('roles.changed_hint')}>
                            <StatusChip label={t('roles.changed')} tone="warn" />
                        </span>
                    )}
                    {role.custom && (
                        <span title={t('roles.custom_hint')}>
                            <StatusChip label={t('roles.custom')} tone="stamp" icon={false} />
                        </span>
                    )}
                    {role.basedOnLabel && (
                        <span className="w-full text-xs text-muted">
                            {t('roles.started_from', { role: role.basedOnLabel })}
                        </span>
                    )}
                </span>
            ),
        },
        {
            key: 'people',
            header: t('roles.column.people'),
            cell: (role) => <span className="tnum">{formatNumber(role.members)}</span>,
        },
        {
            key: 'permissions',
            header: t('roles.column.permissions'),
            cell: (role) => (
                <span className="tnum">
                    {t('roles.granted_of', {
                        granted: formatNumber(role.granted),
                        offered: formatNumber(role.offered),
                    })}
                </span>
            ),
        },
        {
            key: 'actions',
            header: t('team.actions'),
            align: 'end',
            cell: (role) => (
                <Button asChild variant="ghost" size="sm" className="min-h-11 md:min-h-9">
                    <Link href={admin.roles.edit(role.name)}>
                        {role.locked ? <Eye aria-hidden /> : <Pencil aria-hidden />}
                        {role.locked ? t('roles.view') : t('roles.edit')}
                        <span className="sr-only"> · {role.label}</span>
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <>
            <Head title={t('roles.title')} />
            <div className="flex flex-col gap-6">
                <PageHeader title={t('roles.title')} description={t('roles.body')} />
                <p className="rounded-xl bg-brand-soft px-4 py-3 text-sm">
                    {t('roles.manuals_note')}
                </p>

                {KINDS.map((kind) => {
                    const rows = roles.filter((role) => role.kind === kind);

                    return rows.length === 0 ? null : (
                        <section
                            key={kind}
                            aria-labelledby={`roles-${kind}`}
                            className="flex flex-col gap-3"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <h2 id={`roles-${kind}`} className="font-display text-lg font-bold">
                                    {t(`roles.kind.${kind}`)}
                                </h2>
                                <RoleDialog kind={kind} bases={rows} />
                            </div>
                            <DataTable
                                caption={t(`roles.kind.${kind}`)}
                                columns={columns}
                                rows={rows}
                                rowKey={(role) => role.name}
                            />
                        </section>
                    );
                })}
            </div>
        </>
    );
}
