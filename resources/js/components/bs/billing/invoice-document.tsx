import { Printer } from 'lucide-react';
import { Logo } from '@/components/bs/logo';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';

type Invoice = App.Domain.Billing.Data.InvoiceData;

/**
 * An invoice laid out to read on screen and print (spec §11). Platform billing only: worker job
 * costs never appear on BLUESKILLS invoices (product rule 13).
 */
export function InvoiceDocument({
    invoice,
    onRemoveLine,
}: {
    invoice: Invoice;
    onRemoveLine?: (lineId: string) => void;
}) {
    const { t, formatDate, formatMoney, formatNumber } = useTranslation();
    const summary = invoice.summary;

    return (
        <article className="flex flex-col gap-5 rounded-2xl border border-line bg-surface p-5 sm:p-8 print:border-0 print:p-0">
            <header className="flex flex-wrap items-start justify-between gap-4">
                <div className="flex flex-col gap-2">
                    <Logo />
                    <p className="text-xs text-muted">{t('billing.invoice.platform')}</p>
                </div>
                <div className="flex flex-col items-end gap-1.5 text-end">
                    <h2 className="font-display text-2xl font-bold">
                        {summary.number ?? t('billing.invoice.draft')}
                    </h2>
                    <StatusChip status={summary.status} />
                    <Button
                        variant="outline"
                        className="min-h-11 print:hidden"
                        onClick={() => window.print()}
                    >
                        <Printer aria-hidden className="size-4" />
                        {t('billing.invoice.print')}
                    </Button>
                </div>
            </header>

            <dl className="grid gap-4 text-sm sm:grid-cols-3">
                <div className="flex flex-col gap-0.5">
                    <dt className="eyebrow text-muted">{t('billing.invoice.bill_to')}</dt>
                    <dd className="font-semibold">{summary.organization}</dd>
                    {invoice.organizationLegalName &&
                        invoice.organizationLegalName !== summary.organization && (
                            <dd className="text-muted">{invoice.organizationLegalName}</dd>
                        )}
                    {invoice.organizationCountryCode && (
                        <dd className="text-muted">{invoice.organizationCountryCode}</dd>
                    )}
                </div>
                <div className="flex flex-col gap-0.5">
                    <dt className="eyebrow text-muted">{t('billing.invoice.issued')}</dt>
                    <dd>{summary.issuedAt ? formatDate(summary.issuedAt) : '—'}</dd>
                </div>
                <div className="flex flex-col gap-0.5">
                    <dt className="eyebrow text-muted">
                        {summary.paidAt ? t('billing.invoice.paid_on') : t('billing.invoice.due')}
                    </dt>
                    <dd className={summary.overdue ? 'font-semibold text-bad' : undefined}>
                        {summary.paidAt
                            ? formatDate(summary.paidAt)
                            : summary.dueOn
                              ? formatDate(summary.dueOn)
                              : '—'}
                        {summary.overdue && ` · ${t('billing.overdue')}`}
                    </dd>
                </div>
            </dl>

            <div className="overflow-x-auto">
                <table className="w-full min-w-[32rem] text-sm">
                    <caption className="sr-only">{t('billing.invoice.lines')}</caption>
                    <thead>
                        <tr className="border-b border-line text-start text-xs text-muted">
                            <th scope="col" className="py-2 text-start font-medium">
                                {t('billing.invoice.description')}
                            </th>
                            <th scope="col" className="py-2 text-end font-medium">
                                {t('billing.invoice.quantity')}
                            </th>
                            <th scope="col" className="py-2 text-end font-medium">
                                {t('billing.invoice.unit')}
                            </th>
                            <th scope="col" className="py-2 text-end font-medium">
                                {t('billing.invoice.amount')}
                            </th>
                            {onRemoveLine && (
                                <th scope="col" className="w-10 print:hidden">
                                    <span className="sr-only">
                                        {t('billing.invoice.remove_line')}
                                    </span>
                                </th>
                            )}
                        </tr>
                    </thead>
                    <tbody>
                        {invoice.lines.length === 0 && (
                            <tr>
                                <td colSpan={5} className="py-4 text-center text-muted">
                                    {t('billing.invoice.no_lines')}
                                </td>
                            </tr>
                        )}
                        {invoice.lines.map((line) => (
                            <tr key={line.id} className="border-b border-line">
                                <td className="py-2.5">
                                    {line.description}
                                    {line.credits > 0 && (
                                        <span className="block text-xs text-muted">
                                            {t('billing.invoice.credits_included', {
                                                count: formatNumber(line.credits * line.quantity),
                                            })}
                                        </span>
                                    )}
                                </td>
                                <td className="py-2.5 text-end tnum">
                                    {formatNumber(line.quantity)}
                                </td>
                                <td className="py-2.5 text-end tnum">
                                    {formatMoney(line.unitAmount)}
                                </td>
                                <td className="py-2.5 text-end tnum">{formatMoney(line.amount)}</td>
                                {onRemoveLine && (
                                    <td className="py-1 text-end print:hidden">
                                        <Button
                                            variant="ghost"
                                            className="min-h-11 text-bad"
                                            onClick={() => onRemoveLine(line.id)}
                                            aria-label={t('billing.invoice.remove_line_named', {
                                                line: line.description,
                                            })}
                                        >
                                            ×
                                        </Button>
                                    </td>
                                )}
                            </tr>
                        ))}
                    </tbody>
                    <tfoot className="text-sm">
                        <tr>
                            <th
                                scope="row"
                                colSpan={3}
                                className="pt-3 text-end font-normal text-muted"
                            >
                                {t('billing.invoice.subtotal')}
                            </th>
                            <td className="pt-3 text-end tnum">{formatMoney(invoice.subtotal)}</td>
                        </tr>
                        <tr>
                            <th
                                scope="row"
                                colSpan={3}
                                className="pt-1 text-end font-normal text-muted"
                            >
                                {t('billing.invoice.vat', {
                                    rate: formatNumber(invoice.taxRatePercent),
                                })}
                            </th>
                            <td className="pt-1 text-end tnum">{formatMoney(invoice.tax)}</td>
                        </tr>
                        <tr>
                            <th scope="row" colSpan={3} className="pt-2 text-end font-semibold">
                                {t('billing.invoice.total')}
                            </th>
                            <td className="pt-2 text-end font-display text-lg font-bold tnum">
                                {formatMoney(summary.total)}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <footer className="flex flex-col gap-1.5 border-t border-line pt-4 text-sm">
                {invoice.paymentReference ? (
                    <p>
                        {t('billing.invoice.paid_by', {
                            method: invoice.paymentMethod
                                ? t(`payment_method.${invoice.paymentMethod}`)
                                : '',
                            reference: invoice.paymentReference,
                        })}
                    </p>
                ) : (
                    <p className="text-muted">
                        {invoice.paymentInstructions ??
                            t('billing.invoice.how_to_pay', {
                                number: summary.number ?? '',
                            })}
                    </p>
                )}
                {invoice.paymentUrl && (
                    <a
                        href={invoice.paymentUrl}
                        className="font-semibold text-brand hover:underline"
                    >
                        {t('billing.invoice.pay_online')}
                    </a>
                )}
                <p className="text-xs text-muted">{t('billing.invoice.separate_ledger')}</p>
            </footer>
        </article>
    );
}
