import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Send } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { InvoiceDocument } from '@/components/bs/billing/invoice-document';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Invoice = App.Domain.Billing.Data.InvoiceData;

type Props = { invoice: Invoice; paymentMethods: App.Domain.Billing.Enums.PaymentMethod[] };

/** An invoice for finance: edit a draft, issue it, record payment or void it (D-095). */
export default function InvoiceShow({ invoice, paymentMethods }: Props) {
    const { t } = useTranslation();
    const status = invoice.summary.status.value.replace('invoice_', '');
    const id = invoice.summary.id;
    const { errors } = usePage<{ errors: Record<string, string> }>().props;

    return (
        <>
            <Head title={invoice.summary.number ?? t('billing.invoice.draft')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={invoice.summary.number ?? t('billing.invoice.draft')}
                    description={invoice.summary.organization}
                    back={{
                        href: admin.billing.organizations.show(invoice.organizationId),
                        label: invoice.summary.organization,
                    }}
                />
                {errors.invoice && <InputError message={errors.invoice} />}
                <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
                    <InvoiceDocument
                        invoice={invoice}
                        onRemoveLine={
                            status === 'draft'
                                ? (lineId) =>
                                      router.delete(
                                          admin.billing.invoices.lines.destroy.url([id, lineId]),
                                          { preserveScroll: true },
                                      )
                                : undefined
                        }
                    />
                    <div className="flex flex-col gap-5 print:hidden">
                        {status === 'draft' && (
                            <>
                                <LineForm invoiceId={id} />
                                <Panel title={t('billing.issue_title')}>
                                    <p className="text-sm text-muted">{t('billing.issue_hint')}</p>
                                    <ConfirmDialog
                                        trigger={
                                            <Button
                                                className="min-h-11 self-start"
                                                disabled={invoice.lines.length === 0}
                                            >
                                                <Send aria-hidden className="size-4" />
                                                {t('billing.issue')}
                                            </Button>
                                        }
                                        title={t('billing.issue_confirm')}
                                        description={t('billing.issue_hint')}
                                        confirmLabel={t('billing.issue')}
                                        onConfirm={() =>
                                            router.post(
                                                admin.billing.invoices.issue.url(id),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    />
                                </Panel>
                            </>
                        )}
                        {status === 'issued' && <PayForm invoiceId={id} methods={paymentMethods} />}
                        {(status === 'draft' || status === 'issued') && <VoidForm invoiceId={id} />}
                        {invoice.notes && (
                            <Panel title={t('billing.notes')}>
                                <p className="text-sm whitespace-pre-line">{invoice.notes}</p>
                            </Panel>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function LineForm({ invoiceId }: { invoiceId: string }) {
    const { t } = useTranslation();
    const form = useForm({ description: '', quantity: '1', unit_amount: '', credits: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.billing.invoices.lines.store.url(invoiceId), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <Panel title={t('billing.add_line')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="line-description">{t('billing.invoice.description')}</Label>
                    <Input
                        id="line-description"
                        className="h-11"
                        value={form.data.description}
                        onChange={(event) => form.setData('description', event.target.value)}
                        aria-invalid={!!form.errors.description}
                    />
                    <InputError message={form.errors.description} />
                </div>
                <div className="grid grid-cols-2 gap-3">
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="line-quantity">{t('billing.invoice.quantity')}</Label>
                        <Input
                            id="line-quantity"
                            type="number"
                            inputMode="numeric"
                            min={1}
                            className="h-11"
                            value={form.data.quantity}
                            onChange={(event) => form.setData('quantity', event.target.value)}
                            aria-invalid={!!form.errors.quantity}
                        />
                        <InputError message={form.errors.quantity} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="line-amount">{t('billing.invoice.unit')}</Label>
                        <Input
                            id="line-amount"
                            inputMode="decimal"
                            className="h-11"
                            value={form.data.unit_amount}
                            onChange={(event) => form.setData('unit_amount', event.target.value)}
                            aria-invalid={!!form.errors.unit_amount}
                        />
                        <InputError message={form.errors.unit_amount} />
                    </div>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="line-credits">{t('billing.line_credits')}</Label>
                    <Input
                        id="line-credits"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        className="h-11"
                        value={form.data.credits}
                        onChange={(event) => form.setData('credits', event.target.value)}
                        aria-describedby="line-credits-hint"
                    />
                    <p id="line-credits-hint" className="text-xs text-muted">
                        {t('billing.line_credits_hint')}
                    </p>
                </div>
                <Button
                    type="submit"
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('billing.add_line')}
                </Button>
            </form>
        </Panel>
    );
}

function PayForm({
    invoiceId,
    methods,
}: {
    invoiceId: string;
    methods: App.Domain.Billing.Enums.PaymentMethod[];
}) {
    const { t } = useTranslation();
    const form = useForm({
        payment_method: 'bank_transfer',
        payment_reference: '',
        paid_on: new Date().toISOString().slice(0, 10),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.billing.invoices.pay.url(invoiceId), { preserveScroll: true });
    };

    return (
        <Panel title={t('billing.mark_paid')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="pay-method">{t('billing.payment_method')}</Label>
                    <NativeSelect
                        id="pay-method"
                        value={form.data.payment_method}
                        onChange={(event) => form.setData('payment_method', event.target.value)}
                    >
                        {methods.map((method) => (
                            <option key={method} value={method}>
                                {t(`payment_method.${method}`)}
                            </option>
                        ))}
                    </NativeSelect>
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="pay-reference">{t('billing.payment_reference')}</Label>
                    <Input
                        id="pay-reference"
                        className="h-11"
                        value={form.data.payment_reference}
                        onChange={(event) => form.setData('payment_reference', event.target.value)}
                        aria-invalid={!!form.errors.payment_reference}
                    />
                    <InputError message={form.errors.payment_reference} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="pay-date">{t('billing.paid_on')}</Label>
                    <Input
                        id="pay-date"
                        type="date"
                        className="h-11"
                        value={form.data.paid_on}
                        onChange={(event) => form.setData('paid_on', event.target.value)}
                        aria-invalid={!!form.errors.paid_on}
                    />
                    <InputError message={form.errors.paid_on} />
                </div>
                <p className="text-xs text-muted">{t('billing.mark_paid_hint')}</p>
                <Button type="submit" className="min-h-11 self-start" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    {t('billing.mark_paid')}
                </Button>
            </form>
        </Panel>
    );
}

function VoidForm({ invoiceId }: { invoiceId: string }) {
    const { t } = useTranslation();
    const form = useForm({ note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.billing.invoices.void.url(invoiceId), { preserveScroll: true });
    };

    return (
        <Panel title={t('billing.void')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="void-note">{t('billing.void_reason')}</Label>
                    <Input
                        id="void-note"
                        className="h-11"
                        value={form.data.note}
                        onChange={(event) => form.setData('note', event.target.value)}
                        aria-invalid={!!form.errors.note}
                    />
                    <InputError message={form.errors.note} />
                </div>
                <Button
                    type="submit"
                    variant="ghost"
                    className="min-h-11 self-start text-bad"
                    disabled={form.processing || form.data.note.trim() === ''}
                >
                    {t('billing.void')}
                </Button>
            </form>
        </Panel>
    );
}
