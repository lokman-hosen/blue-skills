import { StatusChip } from '@/components/bs/status-chip';
import type { Tone } from '@/components/bs/tone';
import { useTranslation } from '@/lib/i18n';

const decisionTone: Record<App.Domain.Verification.Enums.VerificationDecision, Tone> = {
    approved: 'ok',
    needs_info: 'warn',
    rejected: 'bad',
};

/** Earlier review decisions, newest first, with reason and note. */
export function ReviewHistory({
    history,
}: {
    history: App.Domain.Verification.Data.ReviewDecisionData[];
}) {
    const { t, formatDate } = useTranslation();

    return (
        <section className="rounded-xl border border-line bg-surface p-4">
            <h2 className="mb-3 eyebrow text-muted">{t('admin.review.history')}</h2>
            <ol className="flex flex-col gap-3">
                {history.map((item, index) => (
                    <li key={index} className="flex flex-col gap-1 border-s-2 border-line ps-3">
                        <span className="flex flex-wrap items-center gap-2">
                            <StatusChip
                                tone={decisionTone[item.decision]}
                                label={t(`review_decision.${item.decision}`)}
                            />
                            <span className="text-xs text-muted">
                                {[
                                    item.decidedAt ? formatDate(item.decidedAt) : null,
                                    item.reviewerName,
                                ]
                                    .filter(Boolean)
                                    .join(' · ')}
                            </span>
                        </span>
                        {item.reasonCode && (
                            <span className="text-sm">{t(`review_reason.${item.reasonCode}`)}</span>
                        )}
                        {item.note && (
                            <span className="text-sm text-muted" dir="auto">
                                “{item.note}”
                            </span>
                        )}
                    </li>
                ))}
            </ol>
        </section>
    );
}
