import { EvidenceBadge } from './evidence-badge';
import type { EvidenceState } from './evidence-badge';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export type SkillPassport = {
    code: string;
    name: string;
    headline: string | null;
    /** ISO 3166 alpha-3 code used on the machine-readable line, e.g. BGD. */
    nationality: string;
    yearsExperience: number | null;
    passportStatus: App.Domain.Worker.Enums.PassportStatus | null;
    availability: App.Domain.Worker.Enums.Availability | null;
    countriesWorked: { code: string; name: string; years: number }[];
    skills: string[];
    evidence?: { label: string; state: EvidenceState }[];
};

/**
 * The worker-owned Global Skill Passport. Shows evidence item by item; never a blanket
 * "verified" claim. Phone numbers and passport scans are never part of this card.
 */
export function SkillPassportCard({
    passport,
    className,
}: {
    passport: SkillPassport;
    className?: string;
}) {
    const { t, formatNumber } = useTranslation();
    const nameParts = passport.name.trim().split(/\s+/);
    const initials = nameParts
        .slice(-2)
        .map((part) => part[0])
        .join('');
    const mrzName = `${(nameParts.at(-1) ?? '').toUpperCase()}<<${nameParts.slice(0, -1).join('<').toUpperCase()}`;

    return (
        <div className={cn('flex min-w-0 flex-col gap-3', className)}>
            <div className="relative overflow-hidden rounded-2xl p-5 passport-cover">
                <div className="flex items-start justify-between gap-2">
                    <p className="eyebrow opacity-80">{t('passport.title')}</p>
                    <span className="font-mono text-[10px] opacity-80" dir="ltr">
                        {passport.code}
                    </span>
                </div>
                <div className="mt-4 flex items-center gap-3">
                    <span className="grid size-14 shrink-0 place-items-center rounded-xl bg-on-brand/15 font-display text-xl font-bold">
                        {initials}
                    </span>
                    <div className="min-w-0">
                        <p className="font-display text-xl leading-tight font-bold break-words uppercase">
                            {passport.name}
                        </p>
                        {passport.headline && (
                            <p className="text-[13px] opacity-85">{passport.headline}</p>
                        )}
                    </div>
                </div>
                <dl className="mt-4 grid grid-cols-3 gap-2 text-xs">
                    <div className="min-w-0">
                        <dt className="opacity-75">{t('passport.experience')}</dt>
                        <dd className="font-semibold">
                            {passport.yearsExperience !== null
                                ? t('passport.years', {
                                      count: formatNumber(passport.yearsExperience),
                                  })
                                : t('common.not_disclosed')}
                        </dd>
                    </div>
                    <div className="min-w-0">
                        <dt className="opacity-75">{t('passport.passport')}</dt>
                        <dd className="font-semibold">
                            {passport.passportStatus
                                ? t(`passport.status.${passport.passportStatus}`)
                                : t('common.not_disclosed')}
                        </dd>
                    </div>
                    <div className="min-w-0">
                        <dt className="opacity-75">{t('passport.available')}</dt>
                        <dd className="font-semibold">
                            {passport.availability
                                ? t(`availability.${passport.availability}`)
                                : t('common.not_disclosed')}
                        </dd>
                    </div>
                </dl>
                <p className="mt-4 mrz opacity-75" dir="ltr" aria-hidden>
                    {`SKP<${passport.nationality}<${mrzName}<<<<`}
                    <br />
                    {`${passport.code.replace('-', '')}<${String(passport.yearsExperience ?? 0).padStart(2, '0')}Y<<<<<<`}
                </p>
            </div>

            <div className="rounded-xl border border-line bg-surface p-4">
                <p className="mb-2 eyebrow text-muted">{t('passport.countries')}</p>
                <p className="text-sm font-semibold">
                    {passport.countriesWorked.length > 0
                        ? passport.countriesWorked
                              .map(
                                  (country) => `${country.name} (${formatNumber(country.years)} y)`,
                              )
                              .join(', ')
                        : t('common.not_disclosed')}
                </p>
                <p className="mt-3 mb-2 eyebrow text-muted">{t('passport.skills')}</p>
                <ul className="flex flex-wrap gap-1.5">
                    {passport.skills.map((skill) => (
                        <li
                            key={skill}
                            className="rounded-full bg-brand-soft px-2 py-0.5 text-[11.5px] font-semibold text-brand"
                        >
                            {skill}
                        </li>
                    ))}
                </ul>
            </div>

            {passport.evidence && passport.evidence.length > 0 && (
                <div className="rounded-xl border border-line bg-surface p-4">
                    <p className="mb-1 eyebrow text-muted">{t('evidence.heading')}</p>
                    <ul>
                        {passport.evidence.map((item) => (
                            <li
                                key={item.label}
                                className="flex items-center justify-between gap-2 py-1.5"
                            >
                                <span className="min-w-0 text-[13px]">{item.label}</span>
                                <EvidenceBadge state={item.state} />
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
