import { Link } from '@inertiajs/react';
import { BadgeCheck, MapPin, Video } from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

/**
 * One worker in talent search or on a shortlist (deck slide 17): practical skills first,
 * evidence as counted checks, never a phone number or document (product rule 6).
 */
export function TalentCard({
    worker,
    actions,
}: {
    worker: App.Domain.Applications.Data.TalentCardData;
    actions?: ReactNode;
}) {
    const { t, formatNumber } = useTranslation();

    return (
        <article className="flex min-w-0 gap-3 rounded-xl border border-line bg-surface p-4">
            <Link
                href={org.talent.show(worker.reference)}
                className="relative grid h-24 w-18 shrink-0 place-items-center overflow-hidden rounded-lg bg-surface-2 text-brand"
                aria-label={t('talent.open_profile', { name: worker.name })}
            >
                {worker.skillShowPosterUrl ? (
                    <>
                        <span aria-hidden className="absolute inset-0 bg-screen" />
                        <img
                            src={worker.skillShowPosterUrl}
                            alt=""
                            className="size-full object-cover"
                        />
                        <span className="absolute start-1 bottom-1 inline-flex items-center gap-0.5 rounded bg-black/60 px-1 text-[10px] font-semibold text-white">
                            <Video aria-hidden className="size-3" />
                            {t('nav.skillshow')}
                        </span>
                    </>
                ) : (
                    <span className="font-display text-2xl font-bold">{worker.name.charAt(0)}</span>
                )}
            </Link>
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div className="min-w-0">
                        <h3 className="font-semibold">
                            <Link
                                href={org.talent.show(worker.reference)}
                                className="hover:underline"
                            >
                                {worker.name}
                            </Link>
                        </h3>
                        <p className="text-xs text-muted">
                            {[
                                worker.occupation,
                                worker.yearsExperience !== null &&
                                    t('passport.years', { count: worker.yearsExperience }),
                                worker.countriesWorked.join(', '),
                            ]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    </div>
                    {actions}
                </div>
                {worker.skills.length > 0 && (
                    <p className="text-[13px]">{worker.skills.slice(0, 6).join(' · ')}</p>
                )}
                <div className="flex flex-wrap items-center gap-1.5">
                    {worker.currentCountry && (
                        <span className="inline-flex items-center gap-1 text-xs text-muted">
                            <MapPin aria-hidden className="size-3.5" />
                            {worker.currentCountry}
                        </span>
                    )}
                    {worker.passportStatus && (
                        <StatusChip
                            icon={false}
                            tone={worker.passportStatus === 'ready' ? 'ok' : 'neutral'}
                            label={`${t('passport.passport')}: ${t(`passport.status.${worker.passportStatus}`)}`}
                        />
                    )}
                    {worker.availability && (
                        <StatusChip icon={false} label={t(`availability.${worker.availability}`)} />
                    )}
                    {worker.verifiedEvidence > 0 && (
                        <span className="inline-flex items-center gap-1 text-xs font-semibold text-ok">
                            <BadgeCheck aria-hidden className="size-3.5" />
                            {t('talent.verified_evidence', {
                                count: formatNumber(worker.verifiedEvidence),
                            })}
                        </span>
                    )}
                    {worker.match && (
                        <StatusChip
                            tone="brand"
                            icon={false}
                            label={t('jobs.match.summary', {
                                met: formatNumber(worker.match.met),
                                total: formatNumber(worker.match.total),
                            })}
                        />
                    )}
                </div>
            </div>
        </article>
    );
}
