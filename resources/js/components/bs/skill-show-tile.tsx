import { Play, Video } from 'lucide-react';
import { StatusChip } from './status-chip';
import type { Tone } from './tone';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type SkillShowTileProps = {
    durationMs: number | null;
    skills: string[];
    moderation: App.Domain.Worker.Enums.ModerationStatus | null;
    posterUrl?: string | null;
    workerName?: string;
    onPlay?: () => void;
    className?: string;
};

const moderationTone: Record<App.Domain.Worker.Enums.ModerationStatus, Tone> = {
    processing: 'neutral',
    failed: 'bad',
    pending: 'warn',
    approved: 'ok',
    rejected: 'bad',
};

function duration(ms: number): string {
    const seconds = Math.round(ms / 1000);

    return `00:${String(Math.min(seconds, 59)).padStart(2, '0')}`;
}

/**
 * A 30-second SkillShow. Always labelled as a worker-provided demonstration, never a
 * certification (product rule 3).
 */
export function SkillShowTile({
    durationMs,
    skills,
    moderation,
    posterUrl,
    workerName,
    onPlay,
    className,
}: SkillShowTileProps) {
    const { t } = useTranslation();
    const hasVideo = moderation !== null;

    return (
        <figure className={cn('flex min-w-0 flex-col gap-2', className)}>
            <div className="relative aspect-[9/12] overflow-hidden rounded-2xl border border-line">
                {hasVideo ? (
                    <button
                        type="button"
                        onClick={onPlay}
                        className="group grid size-full place-items-center bg-screen bg-cover bg-center text-on-screen"
                        style={posterUrl ? { backgroundImage: `url(${posterUrl})` } : undefined}
                        aria-label={
                            workerName
                                ? `${t('skillshow.play')} · ${workerName}`
                                : t('skillshow.play')
                        }
                    >
                        <span className="grid size-14 place-items-center rounded-full bg-on-screen/20 transition group-hover:bg-on-screen/30">
                            <Play aria-hidden className="size-6 rtl:-scale-x-100" />
                        </span>
                        {durationMs !== null && (
                            <span
                                className="absolute end-2 bottom-2 rounded bg-screen/80 px-1.5 font-mono text-[11px]"
                                dir="ltr"
                            >
                                {duration(durationMs)}
                            </span>
                        )}
                    </button>
                ) : (
                    <div className="grid size-full place-items-center bg-surface-2 text-muted">
                        <div className="flex flex-col items-center gap-2">
                            <Video aria-hidden className="size-10" />
                            <p className="text-xs">{t('skillshow.none')}</p>
                        </div>
                    </div>
                )}
            </div>
            <figcaption className="flex flex-col gap-1.5">
                <div className="flex items-center justify-between gap-2">
                    <p className="text-[13px] font-semibold">{t('skillshow.title')}</p>
                    {moderation && (
                        <StatusChip
                            label={t(`skillshow.moderation.${moderation}`)}
                            tone={moderationTone[moderation]}
                        />
                    )}
                </div>
                {skills.length > 0 && <p className="text-xs text-muted">{skills.join(' · ')}</p>}
                <p className="rounded-lg bg-surface-2 px-2.5 py-1.5 text-[11.5px] text-muted">
                    {t('skillshow.disclaimer')}
                </p>
            </figcaption>
        </figure>
    );
}
