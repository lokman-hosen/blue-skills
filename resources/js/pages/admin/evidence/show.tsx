import { Head, router } from '@inertiajs/react';
import { FileText, Lock, ScanSearch } from 'lucide-react';
import { useState } from 'react';
import { EvidenceBadge } from '@/components/bs/evidence-badge';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import { PageHeader } from '@/components/bs/page-header';
import { ReviewDecisionForm } from '@/components/bs/review-decision-form';
import { ReviewHistory } from '@/components/bs/review-history';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Props = {
    item: App.Domain.Verification.Data.EvidenceReviewData;
    reasons: App.Domain.Verification.Enums.ReviewReason[];
    canDecide: boolean;
};

/** One evidence item: start the review to open the file, then verify or reject (D-090). */
export default function EvidenceShow({ item, reasons, canDecide }: Props) {
    const { t, formatDate } = useTranslation();
    const [starting, setStarting] = useState(false);

    return (
        <>
            <Head title={item.title ?? t(`evidence.type.${item.type}`)} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={t(`evidence.type.${item.type}`)}
                    title={item.title ?? t(`evidence.type.${item.type}`)}
                    description={`${item.workerName} · ${item.passportReference}`}
                    back={{ href: admin.evidence.index(), label: t('admin.evidence.title') }}
                    actions={<EvidenceBadge state={item.state.value as EvidenceState} />}
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,24rem)]">
                    <section className="flex flex-col gap-3 rounded-xl border border-line bg-surface p-4">
                        <dl className="grid gap-2 text-sm">
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">
                                    {t('admin.evidence.column.uploaded')}
                                </dt>
                                <dd>{formatDate(item.uploadedAt)}</dd>
                            </div>
                            <div className="flex justify-between gap-3">
                                <dt className="text-muted">{t('admin.evidence.expires')}</dt>
                                <dd>{item.expiresOn ? formatDate(item.expiresOn) : '—'}</dd>
                            </div>
                            {item.reviewer && (
                                <div className="flex justify-between gap-3">
                                    <dt className="text-muted">{t('admin.evidence.reviewer')}</dt>
                                    <dd>{item.reviewer}</dd>
                                </div>
                            )}
                        </dl>
                        {item.fileUrl ? (
                            <a
                                href={item.fileUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex min-h-11 items-center gap-2 self-start font-semibold text-brand hover:underline"
                            >
                                <FileText aria-hidden className="size-4" />
                                {item.fileName ?? t('documents.open')}
                            </a>
                        ) : (
                            <div className="flex flex-col gap-3 rounded-lg bg-surface-2 p-3 text-sm">
                                <p className="flex items-start gap-2">
                                    <Lock
                                        aria-hidden
                                        className="mt-0.5 size-4 shrink-0 text-muted"
                                    />
                                    {t('admin.evidence.locked')}
                                </p>
                                <Button
                                    className="min-h-11 self-start"
                                    disabled={starting}
                                    onClick={() =>
                                        router.post(
                                            admin.evidence.start.url(item.id),
                                            {},
                                            {
                                                onStart: () => setStarting(true),
                                                onFinish: () => setStarting(false),
                                            },
                                        )
                                    }
                                >
                                    {starting ? (
                                        <Spinner />
                                    ) : (
                                        <ScanSearch aria-hidden className="size-4" />
                                    )}
                                    {t('admin.evidence.start')}
                                </Button>
                            </div>
                        )}
                        <p className="text-xs text-muted">{t('admin.evidence.logged')}</p>
                    </section>
                    <div className="flex flex-col gap-4">
                        {canDecide && (
                            <section className="rounded-xl border border-line bg-surface p-4">
                                <ReviewDecisionForm
                                    action={admin.evidence.decide.url(item.id)}
                                    reasons={reasons}
                                    allowNeedsInfo={false}
                                />
                            </section>
                        )}
                        {item.history.length > 0 && <ReviewHistory history={item.history} />}
                    </div>
                </div>
            </div>
        </>
    );
}
