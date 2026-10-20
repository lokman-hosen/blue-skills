import { Head, Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { Mic, Pencil } from 'lucide-react';
import type { ReactNode } from 'react';
import VoiceController from '@/actions/App/Http/Controllers/Worker/VoiceController';
import { EvidenceBadge } from '@/components/bs/evidence-badge';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import { PageHeader } from '@/components/bs/page-header';
import { ProfileCompletionCard } from '@/components/bs/profile-completion-card';
import { SkillPassportCard } from '@/components/bs/skill-passport-card';
import { SkillShowTile } from '@/components/bs/skill-show-tile';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { toSkillPassportCard } from '@/lib/passport';
import worker from '@/routes/worker';

type Props = {
    passport: App.Domain.Worker.Data.WorkerPassportData;
};

/** The worker's own Global Skill Passport (spec §3, prototype "Passport"). */
export default function PassportShow({ passport }: Props) {
    const { t, formatNumber, formatMoney } = useTranslation();
    const notDisclosed = t('common.not_disclosed');

    return (
        <>
            <Head title={t('passport.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('passport.title')}
                    description={t('worker.passport.owner_note')}
                    actions={
                        <Button asChild variant="outline" className="min-h-11">
                            <Link href={VoiceController.show.url()}>
                                <Mic aria-hidden className="size-4" />
                                {t('worker.passport.update_by_voice')}
                            </Link>
                        </Button>
                    }
                />

                {passport.completion.next !== null && (
                    <ProfileCompletionCard completion={passport.completion} />
                )}

                <div className="grid gap-5 lg:grid-cols-[minmax(0,380px)_minmax(0,1fr)] lg:items-start">
                    <div className="flex flex-col gap-4 lg:sticky lg:top-6">
                        <SkillPassportCard passport={toSkillPassportCard(passport)} />
                        <Link
                            href={worker.passport.edit.url('photo')}
                            className="inline-flex min-h-11 items-center gap-1.5 self-start text-sm font-semibold text-brand"
                        >
                            <Pencil aria-hidden className="size-4" />
                            {passport.photoUrl ? t('worker.photo.change') : t('worker.photo.add')}
                        </Link>
                    </div>

                    <div className="flex flex-col gap-4">
                        <Section
                            title={t('worker.section.about')}
                            edit={worker.passport.edit.url('about')}
                        >
                            <Facts
                                rows={[
                                    [
                                        t('voice.label.occupation'),
                                        [passport.occupation?.name, passport.specialization?.name]
                                            .filter(Boolean)
                                            .join(' › ') || notDisclosed,
                                    ],
                                    [t('worker.field.headline'), passport.headline ?? notDisclosed],
                                    [
                                        t('passport.experience'),
                                        passport.yearsExperience !== null
                                            ? t('passport.years', {
                                                  count: formatNumber(passport.yearsExperience),
                                              })
                                            : notDisclosed,
                                    ],
                                    [
                                        t('worker.field.current_country'),
                                        passport.currentCountry?.name ?? notDisclosed,
                                    ],
                                ]}
                            />
                        </Section>

                        <Section
                            title={t('worker.section.experience')}
                            edit={worker.passport.edit.url('experience')}
                        >
                            {passport.experiences.length > 0 ? (
                                <ul className="flex flex-col divide-y divide-line">
                                    {passport.experiences.map((experience) => (
                                        <li
                                            key={experience.id}
                                            className="py-2.5 first:pt-0 last:pb-0"
                                        >
                                            <p className="text-sm font-semibold">
                                                {experience.role}
                                            </p>
                                            <p className="text-xs text-muted">
                                                {[
                                                    experience.employerName,
                                                    experience.countryName,
                                                    experience.years !== null
                                                        ? t('passport.years', {
                                                              count: formatNumber(experience.years),
                                                          })
                                                        : null,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted">{t('worker.empty.experience')}</p>
                            )}
                        </Section>

                        <Section
                            title={t('worker.section.skills')}
                            edit={worker.passport.edit.url('skills')}
                        >
                            {passport.skills.length > 0 ? (
                                <ul className="flex flex-wrap gap-1.5">
                                    {passport.skills.map((skill) => (
                                        <li
                                            key={skill.id}
                                            className="rounded-full bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand"
                                        >
                                            {skill.name}
                                            {skill.source === 'institute' && (
                                                <span className="ms-1 font-normal">
                                                    · {t('worker.skill.attested')}
                                                </span>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted">{t('worker.empty.skills')}</p>
                            )}
                        </Section>

                        <Section
                            title={t('worker.section.languages')}
                            edit={worker.passport.edit.url('languages')}
                        >
                            {passport.languages.length > 0 ? (
                                <ul className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    {passport.languages.map((language) => (
                                        <li key={language.code}>
                                            <span className="font-semibold">{language.name}</span>{' '}
                                            <span className="text-muted">
                                                · {t(`proficiency.${language.proficiency}`)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted">{t('worker.empty.languages')}</p>
                            )}
                        </Section>

                        <Section
                            title={t('worker.section.preferences')}
                            edit={worker.passport.edit.url('preferences')}
                        >
                            <Facts
                                rows={[
                                    [
                                        t('passport.passport'),
                                        passport.passportStatus
                                            ? [
                                                  t(`passport.status.${passport.passportStatus}`),
                                                  passport.passportValidUntilYear
                                                      ? t('voice.valid_until', {
                                                            year: passport.passportValidUntilYear,
                                                        })
                                                      : null,
                                              ]
                                                  .filter(Boolean)
                                                  .join(' · ')
                                            : notDisclosed,
                                    ],
                                    [
                                        t('passport.available'),
                                        passport.availability
                                            ? t(`availability.${passport.availability}`)
                                            : notDisclosed,
                                    ],
                                    [
                                        t('voice.label.destinations'),
                                        passport.destinations
                                            .map((country) => country.name)
                                            .join(', ') || notDisclosed,
                                    ],
                                    [
                                        t('worker.field.salary'),
                                        passport.salaryExpectation
                                            ? `${formatMoney(passport.salaryExpectation)}${passport.salaryPeriod ? ` / ${t(`salary_period.${passport.salaryPeriod}`)}` : ''}`
                                            : notDisclosed,
                                    ],
                                ]}
                            />
                        </Section>

                        <Section title={t('evidence.heading')}>
                            <ul className="flex flex-col gap-1">
                                <li className="flex items-center justify-between gap-2 py-1.5">
                                    <span className="text-[13px]">
                                        {t('worker.evidence.mobile')}
                                    </span>
                                    <EvidenceBadge
                                        state={
                                            passport.phoneVerified
                                                ? 'issuer_verified'
                                                : 'verification_pending'
                                        }
                                    />
                                </li>
                                {passport.evidence.map((item) => (
                                    <li
                                        key={item.id}
                                        className="flex items-center justify-between gap-2 py-1.5"
                                    >
                                        <span className="min-w-0 text-[13px] break-words">
                                            {item.title ?? t(`evidence.type.${item.type}`)}
                                        </span>
                                        <EvidenceBadge state={item.state as EvidenceState} />
                                    </li>
                                ))}
                            </ul>
                            <p className="mt-2 text-xs text-muted">{t('worker.evidence.note')}</p>
                        </Section>

                        <Section title={t('skillshow.title')}>
                            {passport.skillShow ? (
                                <SkillShowTile
                                    className="max-w-56"
                                    durationMs={passport.skillShow.durationMs}
                                    skills={passport.skillShow.skills}
                                    moderation={passport.skillShow.status}
                                    posterUrl={passport.skillShow.posterUrl}
                                />
                            ) : (
                                <p className="text-sm text-muted">{t('worker.empty.skillshow')}</p>
                            )}
                        </Section>
                    </div>
                </div>
            </div>
        </>
    );
}

function Section({
    title,
    edit,
    children,
}: {
    title: string;
    edit?: NonNullable<InertiaLinkProps['href']>;
    children: ReactNode;
}) {
    const { t } = useTranslation();

    return (
        <section className="rounded-xl border border-line bg-surface p-4">
            <div className="mb-2 flex items-center justify-between gap-2">
                <h2 className="eyebrow text-muted">{title}</h2>
                {edit && (
                    <Link
                        href={edit}
                        className="-me-2 inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2 text-sm font-semibold text-brand hover:bg-surface-2"
                        aria-label={`${t('common.edit')}: ${title}`}
                    >
                        <Pencil aria-hidden className="size-4" />
                        <span aria-hidden>{t('common.edit')}</span>
                    </Link>
                )}
            </div>
            {children}
        </section>
    );
}

function Facts({ rows }: { rows: [string, string][] }) {
    return (
        <dl className="grid gap-x-4 gap-y-2 sm:grid-cols-2">
            {rows.map(([label, value]) => (
                <div key={label} className="min-w-0">
                    <dt className="text-xs text-muted">{label}</dt>
                    <dd className="text-sm font-semibold break-words">{value}</dd>
                </div>
            ))}
        </dl>
    );
}
