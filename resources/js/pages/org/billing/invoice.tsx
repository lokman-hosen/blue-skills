import { Head } from '@inertiajs/react';
import { InvoiceDocument } from '@/components/bs/billing/invoice-document';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = { invoice: App.Domain.Billing.Data.InvoiceData };

/** A BLUESKILLS invoice for the organization to read, print and pay. */
export default function OrgInvoice({ invoice }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={invoice.summary.number ?? t('billing.invoice.draft')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <PageHeader
                    title={invoice.summary.number ?? t('billing.invoice.draft')}
                    back={{ href: org.billing.index(), label: t('nav.billing') }}
                    className="print:hidden"
                />
                <InvoiceDocument invoice={invoice} />
            </div>
        </>
    );
}
