import { Head, router } from '@inertiajs/react';
import { Check, Video, X } from 'lucide-react';
import { useState } from 'react';
import SkillShowModerationController from '@/actions/App/Http/Controllers/Admin/SkillShowModerationController';
import { EmptyState } from '@/components/bs/empty-state';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';

type Props = {
    queue: App.Domain.Worker.Data.SkillShowReviewData[];
    reasons: App.Domain.Worker.Enums.SkillShowRejectionReason[];
};

/** SkillShow moderation (product rule 3). Full verification queues arrive in Module 8. */
export default function SkillShowModeration({ queue, reasons }: Props) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <>
            <Head title={t('admin.skillshows.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('admin.skillshows.title')}
                    description={t('admin.skillshows.body', { count: formatNumber(queue.length) })}
                />
                {queue.length === 0 ? (
                    <EmptyState
                        icon={Video}
                        title={t('admin.skillshows.empty_title')}
                        description={t('admin.skillshows.empty_body')}
                    />
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {queue.map((item) => (
                            <li key={item.skillShow.id}>
                                <ReviewCard
                                    item={item}
                                    reasons={reasons}
                                    submittedAt={
                                        item.skillShow.createdAt
                                            ? formatDate(item.skillShow.createdAt)
                                            : null
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

function ReviewCard({
    item,
    reasons,
    submittedAt,
}: {
    item: App.Domain.Worker.Data.SkillShowReviewData;
    reasons: App.Domain.Worker.Enums.SkillShowRejectionReason[];
    submittedAt: string | null;
}) {
    const { t } = useTranslation();
    const [reason, setReason] = useState('');
    const [error, setError] = useState<string | undefined>();
    const [processing, setProcessing] = useState(false);
    const reasonId = `reason-${item.skillShow.id}`;

    const decide = (decision: 'approve' | 'reject') =>
        router.patch(
            SkillShowModerationController.update.url(item.skillShow.id),
            { decision, reason: decision === 'reject' ? reason : null },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: (errors) => setError(errors.reason ?? errors.decision),
            },
        );

    return (
        <article className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4">
            <div className="aspect-[9/12] overflow-hidden rounded-xl bg-screen">
                {item.skillShow.videoUrl && (
                    <video
                        src={item.skillShow.videoUrl}
                        poster={item.skillShow.posterUrl ?? undefined}
                        controls
                        playsInline
                        preload="metadata"
                        className="size-full object-contain"
                    >
                        <track kind="captions" />
                    </video>
                )}
            </div>
            <div className="min-w-0">
                <p className="truncate font-semibold">{item.workerName}</p>
                <p className="text-xs text-muted">
                    <span dir="ltr">{item.reference}</span>
                    {item.occupation && ` · ${item.occupation}`}
                    {submittedAt && ` · ${submittedAt}`}
                </p>
                <p className="mt-1 text-xs text-muted">{item.skillShow.skills.join(' · ')}</p>
            </div>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor={reasonId} className="text-xs">
                    {t('admin.skillshows.reason')}
                </Label>
                <NativeSelect
                    id={reasonId}
                    value={reason}
                    onChange={(event) => setReason(event.target.value)}
                >
                    <option value="">{t('admin.skillshows.reason_placeholder')}</option>
                    {reasons.map((value) => (
                        <option key={value} value={value}>
                            {t(`skillshow.reason.${value}`)}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={error} />
            </div>
            <div className="grid grid-cols-2 gap-2">
                <Button
                    className="min-h-11"
                    disabled={processing}
                    onClick={() => decide('approve')}
                >
                    <Check aria-hidden className="size-4" />
                    {t('admin.skillshows.approve')}
                </Button>
                <Button
                    variant="outline"
                    className="min-h-11 text-bad"
                    disabled={processing || reason === ''}
                    onClick={() => decide('reject')}
                >
                    <X aria-hidden className="size-4" />
                    {t('admin.skillshows.reject')}
                </Button>
            </div>
        </article>
    );
}
