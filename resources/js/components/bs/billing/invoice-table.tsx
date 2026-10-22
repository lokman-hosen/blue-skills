import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { Receipt } from 'lucide-react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';

type Invoice = App.Domain.Billing.Data.InvoiceListItemData;

/** Invoices as a table on desktop and stacked cards on phones. */
export function InvoiceTable({
    invoices,
    href,
    showOrganization = false,
}: {
    invoices: Invoice[];
    href: (invoice: Invoice) => NonNullable<InertiaLinkProps['href']>;
    showOrganization?: boolean;
}) {
    const { t, formatDate, formatMoney } = useTranslation();

    return (
        <DataTable<Invoice>
            caption={t('billing.invoices')}
            rows={invoices}
            rowKey={(row) => row.id}
            empty={
                <EmptyState
                    icon={Receipt}
                    title={t('billing.no_invoices_title')}
                    description={t('billing.no_invoices_body')}
                />
            }
            columns={[
                {
                    key: 'number',
                    header: t('billing.invoice.number'),
                    primary: true,
                    cell: (row) => (
                        <Link href={href(row)} className="font-semibold text-brand hover:underline">
                            <span dir="ltr" className="font-mono">
                                {row.number ?? t('billing.invoice.draft')}
                            </span>
                            {showOrganization && (
                                <span className="block text-xs font-normal text-muted">
                                    {row.organization}
                                </span>
                            )}
                        </Link>
                    ),
                },
                {
                    key: 'status',
                    header: t('admin.organizations.status'),
                    cell: (row) => (
                        <span className="flex flex-wrap gap-1.5">
                            <StatusChip status={row.status} />
                            {row.overdue && (
                                <StatusChip tone="bad" label={t('billing.overdue')} icon={false} />
                            )}
                        </span>
                    ),
                },
                {
                    key: 'total',
                    header: t('billing.invoice.total'),
                    align: 'end',
                    cell: (row) => <span className="tnum">{formatMoney(row.total)}</span>,
                },
                {
                    key: 'due',
                    header: t('billing.invoice.due'),
                    cell: (row) => (row.dueOn ? formatDate(row.dueOn) : '—'),
                },
            ]}
        />
    );
}
