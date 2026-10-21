import { Head } from '@inertiajs/react';
import { MandateSummary } from '@/components/bs/mandate-summary';
import { PageHeader } from '@/components/bs/page-header';
import { ReviewDecisionForm } from '@/components/bs/review-decision-form';
import { ReviewHistory } from '@/components/bs/review-history';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Props = {
    reviewId: string;
    mandate: App.Domain.Jobs.Data.MandateData;
    licence: {
        number: string | null;
        expiresOn: string | null;
        countries: string[];
        valid: boolean;
    };
    history: App.Domain.Verification.Data.ReviewDecisionData[];
    decidable: boolean;
    reasons: App.Domain.Verification.Enums.ReviewReason[];
};

/** Check the mandate letter and the agency licence, then decide (product rule 10). */
export default function MandateReview({
    reviewId,
    mandate,
    licence,
    history,
    decidable,
    reasons,
}: Props) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <Head title={mandate.reference} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={mandate.reference}
                    title={`${mandate.agency} → ${mandate.employer}`}
                    back={{ href: admin.mandates.index(), label: t('admin.mandates.title') }}
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,360px)] lg:items-start">
                    <div className="flex min-w-0 flex-col gap-5">
                        <MandateSummary mandate={mandate} />
                        <section className="flex flex-col gap-2 rounded-2xl border border-line bg-surface p-5 text-sm">
                            <h2 className="eyebrow text-muted">{t('licence.title')}</h2>
                            <p>
                                <span className="font-mono font-bold" dir="ltr">
                                    {licence.number ?? '—'}
                                </span>
                                {licence.expiresOn &&
                                    ` · ${t('licence.expires')} ${formatDate(licence.expiresOn)}`}
                            </p>
                            <p>
                                {t('licence.countries')}: {licence.countries.join(', ') || '—'}
                            </p>
                            <StatusChip
                                className="self-start"
                                tone={licence.valid ? 'ok' : 'bad'}
                                label={
                                    licence.valid
                                        ? t('admin.mandates.licence_covers')
                                        : t('admin.mandates.licence_does_not_cover')
                                }
                            />
                        </section>
                    </div>
                    <div className="flex flex-col gap-4">
                        {decidable ? (
                            <ReviewDecisionForm
                                action={admin.mandates.decide.url(reviewId)}
                                reasons={reasons}
                            />
                        ) : (
                            <p className="text-sm text-muted">{t('admin.review.not_open')}</p>
                        )}
                        {history.length > 0 && <ReviewHistory history={history} />}
                    </div>
                </div>
            </div>
        </>
    );
}
