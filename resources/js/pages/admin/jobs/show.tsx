import { Head } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2 } from 'lucide-react';
import JobReviewController from '@/actions/App/Http/Controllers/Admin/JobReviewController';
import { PageHeader } from '@/components/bs/page-header';
import { ReviewDecisionForm } from '@/components/bs/review-decision-form';
import { ReviewHistory } from '@/components/bs/review-history';
import { StatusChip } from '@/components/bs/status-chip';
import { TruthCard } from '@/components/bs/truth-card';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Props = {
    review: App.Domain.Jobs.Data.JobReviewItemData;
    job: App.Domain.Jobs.Data.JobTruthCardData;
    problems: Record<string, string>;
    history: App.Domain.Verification.Data.ReviewDecisionData[];
    decidable: boolean;
    reasons: App.Domain.Verification.Enums.ReviewReason[];
};

/** One submitted job: its Truth Card exactly as workers would see it, the checks and the decision. */
export default function JobReview({ review, job, problems, history, decidable, reasons }: Props) {
    const { t } = useTranslation();
    const problemList = Object.entries(problems);

    return (
        <>
            <Head title={review.title} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={review.code}
                    title={review.title}
                    description={review.employerName}
                    back={{ href: admin.jobs.index(), label: t('admin.jobs.title') }}
                    actions={<StatusChip status={review.reviewStatus} />}
                />

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,420px)] lg:items-start">
                    <TruthCard job={job} preview />

                    <div className="flex flex-col gap-5 lg:sticky lg:top-20">
                        <section className="flex flex-col gap-2 rounded-xl border border-line bg-surface p-4">
                            <h2 className="eyebrow text-muted">{t('admin.jobs.checks')}</h2>
                            <p className="flex items-center gap-2 text-sm">
                                <StatusChip status={review.employerVerification} />
                                {t('admin.jobs.employer_status')}
                            </p>
                            {review.notes && (
                                <p className="rounded-lg bg-warn-soft px-3 py-2 text-sm">
                                    {review.notes}
                                </p>
                            )}
                            {problemList.length === 0 ? (
                                <p className="flex items-center gap-2 text-sm text-ok">
                                    <CheckCircle2 aria-hidden className="size-4" />
                                    {t('admin.jobs.rules_ok_long')}
                                </p>
                            ) : (
                                <ul className="flex flex-col gap-1 text-sm text-bad">
                                    {problemList.map(([field, message]) => (
                                        <li key={field} className="flex gap-2">
                                            <AlertTriangle
                                                aria-hidden
                                                className="mt-0.5 size-4 shrink-0"
                                            />
                                            <span>
                                                <span className="font-semibold">
                                                    {t(`jobs.problem_field.${field}`, {}, field)}:
                                                </span>{' '}
                                                {message}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="rounded-xl border border-line bg-surface p-4">
                            <h2 className="mb-3 font-semibold">{t('admin.review.decision')}</h2>
                            {decidable ? (
                                <ReviewDecisionForm
                                    action={JobReviewController.decide.url(review.reviewId)}
                                    reasons={reasons}
                                />
                            ) : (
                                <p className="text-sm text-muted">{t('admin.review.not_open')}</p>
                            )}
                        </section>

                        {history.length > 0 && <ReviewHistory history={history} />}
                    </div>
                </div>
            </div>
        </>
    );
}
