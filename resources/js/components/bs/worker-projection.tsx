import { EvidenceBadge } from '@/components/bs/evidence-badge';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Projection = Pick<
    App.Domain.Applications.Data.ProfileSnapshotData,
    | 'headline'
    | 'occupation'
    | 'specialization'
    | 'yearsExperience'
    | 'currentCountry'
    | 'passportStatus'
    | 'passportValidUntilYear'
    | 'availability'
    | 'skills'
    | 'languages'
    | 'destinations'
    | 'experiences'
    | 'credentials'
>;

/**
 * The professional projection of a worker an organization may see (product rule 1): never a
 * phone number, document or address. Each credential shows its own evidence state (rule 9).
 */
export function WorkerProjection({
    profile,
    className,
}: {
    profile: Projection;
    className?: string;
}) {
    const { t, formatDate } = useTranslation();

    return (
        <div className={cn('flex flex-col gap-4', className)}>
            {profile.headline && <p className="text-sm">{profile.headline}</p>}
            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                <Fact label={t('talent.field.trade')}>
                    {[profile.occupation?.name, profile.specialization?.name]
                        .filter(Boolean)
                        .join(' · ') || t('common.not_disclosed')}
                </Fact>
                <Fact label={t('passport.experience')}>
                    {profile.yearsExperience !== null
                        ? t('passport.years', { count: profile.yearsExperience })
                        : t('common.not_disclosed')}
                </Fact>
                <Fact label={t('talent.field.lives_in')}>
                    {profile.currentCountry?.name ?? t('common.not_disclosed')}
                </Fact>
                <Fact label={t('passport.passport')}>
                    {profile.passportStatus
                        ? `${t(`passport.status.${profile.passportStatus}`)}${profile.passportValidUntilYear ? ` · ${profile.passportValidUntilYear}` : ''}`
                        : t('common.not_disclosed')}
                </Fact>
                <Fact label={t('passport.available')}>
                    {profile.availability
                        ? t(`availability.${profile.availability}`)
                        : t('common.not_disclosed')}
                </Fact>
                <Fact label={t('talent.field.destinations')}>
                    {profile.destinations.map((country) => country.name).join(', ') ||
                        t('common.not_disclosed')}
                </Fact>
            </dl>

            <List title={t('passport.skills')}>
                {profile.skills.length === 0 ? (
                    <p className="text-sm text-muted">{t('common.not_disclosed')}</p>
                ) : (
                    <ul className="flex flex-wrap gap-1.5">
                        {profile.skills.map((skill) => (
                            <li
                                key={skill.id}
                                className="rounded-full bg-surface-2 px-2.5 py-1 text-xs font-semibold"
                            >
                                {skill.name}
                            </li>
                        ))}
                    </ul>
                )}
            </List>

            <List title={t('talent.field.languages')}>
                {profile.languages.length === 0 ? (
                    <p className="text-sm text-muted">{t('common.not_disclosed')}</p>
                ) : (
                    <p className="text-sm">
                        {profile.languages
                            .map(
                                (language) =>
                                    `${language.name} (${t(`proficiency.${language.proficiency}`)})`,
                            )
                            .join(', ')}
                    </p>
                )}
            </List>

            {profile.experiences.length > 0 && (
                <List title={t('consent.item.work_experience')}>
                    <ul className="flex flex-col divide-y divide-line">
                        {profile.experiences.map((experience) => (
                            <li key={experience.id} className="py-2 text-sm">
                                <p className="font-semibold">
                                    {experience.role}
                                    {experience.employerName && ` · ${experience.employerName}`}
                                </p>
                                <p className="text-xs text-muted">
                                    {experience.countryName}
                                    {experience.years !== null &&
                                        ` · ${t('passport.years', { count: experience.years })}`}
                                    {experience.startedOn &&
                                        ` · ${formatDate(experience.startedOn, { year: 'numeric', month: 'short' })}`}
                                </p>
                            </li>
                        ))}
                    </ul>
                </List>
            )}

            {profile.credentials.length > 0 && (
                <List title={t('consent.item.training_certificates')}>
                    <ul className="flex flex-col gap-2">
                        {profile.credentials.map((credential) => (
                            <li
                                key={credential.id}
                                className="flex flex-wrap items-center justify-between gap-2 text-sm"
                            >
                                <span className="min-w-0">
                                    {credential.title ?? t(`evidence.type.${credential.type}`)}
                                    {credential.issuer && (
                                        <span className="block text-xs text-muted">
                                            {t('evidence.issued_by', { issuer: credential.issuer })}
                                        </span>
                                    )}
                                </span>
                                <EvidenceBadge state={credential.state as EvidenceState} />
                            </li>
                        ))}
                    </ul>
                </List>
            )}
        </div>
    );
}

function Fact({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="min-w-0">
            <dt className="text-xs text-muted">{label}</dt>
            <dd className="font-semibold break-words">{children}</dd>
        </div>
    );
}

function List({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <section className="flex flex-col gap-1.5">
            <h3 className="eyebrow text-muted">{title}</h3>
            {children}
        </section>
    );
}
