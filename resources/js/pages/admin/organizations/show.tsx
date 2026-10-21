import { Head } from '@inertiajs/react';
import { ExternalLink, FileText } from 'lucide-react';
import OrganizationReviewController from '@/actions/App/Http/Controllers/Admin/OrganizationReviewController';
import { PageHeader } from '@/components/bs/page-header';
import { ReviewDecisionForm } from '@/components/bs/review-decision-form';
import { ReviewHistory } from '@/components/bs/review-history';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Props = {
    review: App.Domain.Organization.Data.OrganizationReviewData;
    documents: App.Domain.Organization.Data.EvidenceDocumentData[];
    members: App.Domain.Organization.Data.TeamMemberData[];
    history: App.Domain.Verification.Data.ReviewDecisionData[];
    decidable: boolean;
    reasons: App.Domain.Verification.Enums.ReviewReason[];
};

/** One organization registration: details, evidence, team, history and the decision. */
export default function OrganizationReview({
    review,
    documents,
    members,
    history,
    decidable,
    reasons,
}: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const notGiven = t('common.not_disclosed');

    return (
        <>
            <Head title={review.displayName} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={t(`org.type.${review.type}`)}
                    title={review.displayName}
                    description={review.legalName}
                    back={{
                        href: admin.organizations.index(),
                        label: t('admin.organizations.title'),
                    }}
                    actions={
                        <span className="flex flex-wrap gap-1.5">
                            <StatusChip status={review.reviewStatus} />
                            <StatusChip status={review.verification} />
                        </span>
                    }
                />

                {review.flags && (
                    <p
                        role="note"
                        className="rounded-xl border border-warn/30 bg-warn-soft px-4 py-3 text-sm"
                    >
                        {review.flags}
                    </p>
                )}

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,420px)] lg:items-start">
                    <div className="flex flex-col gap-5">
                        <section className="rounded-xl border border-line bg-surface p-4">
                            <h2 className="mb-3 eyebrow text-muted">
                                {t('admin.organizations.details')}
                            </h2>
                            <dl className="grid gap-x-4 gap-y-2 sm:grid-cols-2">
                                {[
                                    [
                                        t('admin.organizations.country'),
                                        [review.city, review.countryCode]
                                            .filter(Boolean)
                                            .join(', '),
                                    ],
                                    [
                                        t('admin.organizations.registration_number'),
                                        review.registrationNumber ?? notGiven,
                                    ],
                                    ...(review.type === 'agency'
                                        ? [
                                              [
                                                  t('admin.organizations.licence_number'),
                                                  review.licenceNumber ?? notGiven,
                                              ],
                                              [
                                                  t('admin.organizations.licence_expires'),
                                                  review.licenceExpiresOn
                                                      ? formatDate(review.licenceExpiresOn)
                                                      : notGiven,
                                              ],
                                          ]
                                        : []),
                                    [
                                        t('admin.organizations.submitted'),
                                        review.submittedAt ? formatDate(review.submittedAt) : '',
                                    ],
                                ].map(([label, value]) => (
                                    <div key={label} className="min-w-0">
                                        <dt className="text-xs text-muted">{label}</dt>
                                        <dd
                                            className="text-sm font-semibold break-words"
                                            dir="auto"
                                        >
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </section>

                        <section className="rounded-xl border border-line bg-surface p-4">
                            <h2 className="mb-3 eyebrow text-muted">
                                {t('admin.organizations.documents')}
                            </h2>
                            {documents.length === 0 ? (
                                <p className="text-sm text-muted">
                                    {t('admin.organizations.no_documents')}
                                </p>
                            ) : (
                                <ul className="flex flex-col gap-2">
                                    {documents.map((document) => (
                                        <li key={document.id}>
                                            <a
                                                href={document.url ?? '#'}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="flex min-h-11 items-center gap-3 rounded-lg border border-line p-3 hover:border-brand/60"
                                            >
                                                <FileText
                                                    aria-hidden
                                                    className="size-5 shrink-0 text-brand"
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block truncate text-sm font-semibold">
                                                        {document.name}
                                                    </span>
                                                    <span className="block text-xs text-muted">
                                                        {formatNumber(document.sizeKb)} KB
                                                        {document.uploadedAt &&
                                                            ` · ${formatDate(document.uploadedAt)}`}
                                                    </span>
                                                </span>
                                                <ExternalLink
                                                    aria-hidden
                                                    className="size-4 text-muted rtl:-scale-x-100"
                                                />
                                                <span className="sr-only">
                                                    {t('admin.organizations.opens_new_tab')}
                                                </span>
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </section>

                        <section className="rounded-xl border border-line bg-surface p-4">
                            <h2 className="mb-3 eyebrow text-muted">{t('nav.team')}</h2>
                            <ul className="flex flex-col divide-y divide-line">
                                {members.map((member) => (
                                    <li
                                        key={member.id}
                                        className="flex flex-wrap items-center justify-between gap-2 py-2"
                                    >
                                        <span className="min-w-0">
                                            <span className="block text-sm font-semibold">
                                                {member.name}
                                            </span>
                                            <span className="block text-xs break-all text-muted">
                                                {[member.email, member.title]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </span>
                                        </span>
                                        {member.role && (
                                            <StatusChip
                                                tone="neutral"
                                                label={t(`role.${member.role}`)}
                                            />
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>

                        {history.length > 0 && <ReviewHistory history={history} />}
                    </div>

                    <section className="rounded-xl border border-line bg-surface p-4 lg:sticky lg:top-20">
                        <h2 className="mb-3 font-semibold">{t('admin.review.decision')}</h2>
                        {decidable ? (
                            <ReviewDecisionForm
                                action={OrganizationReviewController.decide.url(review.reviewId)}
                                reasons={reasons}
                            />
                        ) : (
                            <p className="text-sm text-muted">
                                {t('admin.review.waiting_for_organization')}
                            </p>
                        )}
                    </section>
                </div>
            </div>
        </>
    );
}
