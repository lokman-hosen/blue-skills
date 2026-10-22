import { Head } from '@inertiajs/react';
import { BillingSummary } from '@/components/bs/billing/billing-summary';
import { InvoiceTable } from '@/components/bs/billing/invoice-table';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = { billing: App.Domain.Billing.Data.OrganizationBillingData };

/** Plan, posting credits, seats and invoices for the organization (spec §11, D-095). */
export default function Billing({ billing }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.billing')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.billing')} description={t('billing.org_body')} />
                <BillingSummary billing={billing} />
                <section className="flex flex-col gap-3">
                    <h2 className="eyebrow text-muted">{t('billing.invoices')}</h2>
                    <InvoiceTable
                        invoices={billing.invoices}
                        href={(invoice) => org.billing.invoices.show(invoice.id)}
                    />
                </section>
                <p className="text-xs text-muted">{t('billing.separate_ledger')}</p>
            </div>
        </>
    );
}
