import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

/**
 * Plays an approved SkillShow for an organization. Always labelled as a worker-provided
 * demonstration, not certification (product rule 3).
 */
export function SkillShowPlayer({
    skillShow,
    className,
}: {
    skillShow: App.Domain.Worker.Data.SkillShowData;
    className?: string;
}) {
    const { t } = useTranslation();

    return (
        <figure className={cn('flex flex-col gap-2', className)}>
            <div className="aspect-[9/12] max-h-[420px] overflow-hidden rounded-xl bg-screen">
                {skillShow.videoUrl && (
                    <video
                        src={skillShow.videoUrl}
                        poster={skillShow.posterUrl ?? undefined}
                        controls
                        playsInline
                        preload="metadata"
                        className="size-full object-contain"
                    >
                        <track kind="captions" />
                    </video>
                )}
            </div>
            <figcaption className="flex flex-col gap-0.5 text-xs">
                <span className="font-semibold text-warn">{t('skillshow.disclaimer')}</span>
                {skillShow.skills.length > 0 && (
                    <span className="text-muted">{skillShow.skills.join(' · ')}</span>
                )}
            </figcaption>
        </figure>
    );
}
