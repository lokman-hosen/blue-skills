import { Head } from '@inertiajs/react';
import { BillingTabs } from '@/components/bs/billing/billing-tabs';
import { InvoiceTable } from '@/components/bs/billing/invoice-table';
import { FilterPill } from '@/components/bs/filter-pill';
import type { Paginated } from '@/components/bs/jobs/types';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type State = 'open' | 'overdue' | 'draft' | 'paid' | 'void' | 'all';
type Props = { invoices: Paginated<App.Domain.Billing.Data.InvoiceListItemData>; state: State };

const STATES: State[] = ['open', 'overdue', 'draft', 'paid', 'void', 'all'];

/** All platform invoices (spec §11). */
export default function Invoices({ invoices, state }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('billing.invoices')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.billing')} description={t('billing.admin_body')} />
                <BillingTabs active="invoices" />
                <nav aria-label={t('billing.invoices')} className="flex flex-wrap gap-1.5">
                    {STATES.map((value) => (
                        <FilterPill
                            key={value}
                            href={admin.billing.invoices.index(
                                value === 'open' ? undefined : { query: { state: value } },
                            )}
                            active={state === value}
                            label={t(`billing.state.${value}`)}
                        />
                    ))}
                </nav>
                <InvoiceTable
                    invoices={invoices.data}
                    href={(invoice) => admin.billing.invoices.show(invoice.id)}
                    showOrganization
                />
                <Pager page={invoices} />
            </div>
        </>
    );
}
