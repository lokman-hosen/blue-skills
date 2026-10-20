import { Head, Link } from '@inertiajs/react';
import { PackageOpen } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { ConsentToggle } from '@/components/bs/consent-toggle';
import type { DataTableColumn } from '@/components/bs/data-table';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { ErrorState } from '@/components/bs/error-state';
import type { EvidenceState } from '@/components/bs/evidence-badge';
import { EvidenceBadge } from '@/components/bs/evidence-badge';
import { FileUpload } from '@/components/bs/file-upload';
import { FilterSheet } from '@/components/bs/filter-sheet';
import { KpiTile } from '@/components/bs/kpi-tile';
import { LanguageSwitcher } from '@/components/bs/language-switcher';
import { PageHeader } from '@/components/bs/page-header';
import type { SkillPassport } from '@/components/bs/skill-passport-card';
import { SkillPassportCard } from '@/components/bs/skill-passport-card';
import { SkillShowTile } from '@/components/bs/skill-show-tile';
import type { Stage } from '@/components/bs/stage-tracker';
import { StageTracker } from '@/components/bs/stage-tracker';
import { StatusChip } from '@/components/bs/status-chip';
import { ThemeToggle } from '@/components/bs/theme-toggle';
import type { Tone } from '@/components/bs/tone';
import { TruthCard } from '@/components/bs/truth-card';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import design from '@/routes/design';

type Props = {
    truthCards: App.Domain.Jobs.Data.JobTruthCardData[];
    layouts: string[];
};

// Design fixtures from prototype-v1. This page is a staff-only review tool, so its own
// headings stay in English; every component inside renders through t().
const tones: Tone[] = ['ok', 'warn', 'bad', 'brand', 'stamp', 'neutral'];
const evidenceStates: EvidenceState[] = [
    'self_declared',
    'uploaded_unreviewed',
    'verification_pending',
    'issuer_verified',
    'expired',
    'rejected',
];
const swatches = [
    'bg',
    'surface',
    'surface-2',
    'line',
    'ink',
    'muted',
    'brand',
    'brand-soft',
    'on-brand',
    'stamp',
    'stamp-soft',
    'ok',
    'ok-soft',
    'warn',
    'warn-soft',
    'bad',
    'bad-soft',
    'screen',
    'on-screen',
];

const rahim: SkillPassport = {
    code: 'BSK-0004182',
    name: 'Md Rahim Uddin',
    headline: 'Electrical Trade Professional',
    nationality: 'BGD',
    yearsExperience: 8,
    passportStatus: 'ready',
    availability: 'immediately',
    countriesWorked: [
        { code: 'BD', name: 'Bangladesh', years: 5 },
        { code: 'AE', name: 'UAE', years: 3 },
    ],
    skills: ['Industrial wiring', 'DB installation', 'Maintenance'],
    evidence: [
        { label: 'Identity', state: 'issuer_verified' },
        { label: 'Passport', state: 'uploaded_unreviewed' },
        { label: 'UAE work reference', state: 'verification_pending' },
        { label: 'Electrical training certificate', state: 'issuer_verified' },
        { label: 'Trade assessment', state: 'self_declared' },
    ],
};

const mobilityStages: Stage[] = [
    { key: 'job_selected', label: 'Job selected', status: 'done', date: '2026-09-12' },
    { key: 'contract_accepted', label: 'Contract accepted', status: 'done', date: '2026-09-20' },
    {
        key: 'passport_received',
        label: 'Passport received by agency',
        status: 'done',
        date: '2026-09-28',
    },
    { key: 'medical', label: 'Medical test', status: 'current', date: '2026-10-24' },
    { key: 'police_clearance', label: 'Police clearance', status: 'blocked' },
    { key: 'work_permit', label: 'Work permit', status: 'pending' },
    { key: 'visa', label: 'Visa', status: 'pending' },
    { key: 'flight', label: 'Flight & departure', status: 'skipped' },
    { key: 'joined', label: 'Arrival & joining', status: 'pending' },
];

const applicationStages: Stage[] = [
    { key: 'applied', label: 'Applied', status: 'done' },
    { key: 'reviewed', label: 'Profile reviewed', status: 'done' },
    { key: 'shortlisted', label: 'Shortlisted', status: 'current' },
    { key: 'interview', label: 'Interview', status: 'pending' },
    { key: 'trade_assessment', label: 'Trade assessment', status: 'pending' },
    { key: 'offer', label: 'Offer', status: 'pending' },
];

type Candidate = {
    id: string;
    name: string;
    occupation: string;
    experience: number;
    country: string;
    evidence: EvidenceState;
};

const candidates: Candidate[] = [
    {
        id: 'c1',
        name: 'Rahim Uddin',
        occupation: 'Electrician',
        experience: 8,
        country: 'Bangladesh',
        evidence: 'issuer_verified',
    },
    {
        id: 'c2',
        name: 'Karim Ahmed',
        occupation: 'Industrial Electrician',
        experience: 6,
        country: 'Bangladesh',
        evidence: 'verification_pending',
    },
    {
        id: 'c3',
        name: 'Hasan Ali',
        occupation: 'Electrical Technician',
        experience: 10,
        country: 'Pakistan',
        evidence: 'uploaded_unreviewed',
    },
];

const columns: DataTableColumn<Candidate>[] = [
    { key: 'name', header: 'Name', cell: (row) => row.name, primary: true },
    { key: 'occupation', header: 'Occupation', cell: (row) => row.occupation },
    { key: 'experience', header: 'Years', cell: (row) => row.experience, align: 'end' },
    { key: 'country', header: 'Country', cell: (row) => row.country },
    { key: 'evidence', header: 'Reference', cell: (row) => <EvidenceBadge state={row.evidence} /> },
];

export default function DesignSystem({ truthCards, layouts }: Props) {
    const { t } = useTranslation();
    const [tableState, setTableState] = useState<'rows' | 'loading' | 'empty' | 'error'>('rows');
    const [file, setFile] = useState<File | null>(null);
    const [consents, setConsents] = useState({ passport: true, skillshow: true, scan: false });

    return (
        <>
            <Head title="Design system" />
            <div className="flex flex-col gap-10">
                <PageHeader
                    eyebrow="Staff only"
                    title="BLUESKILLS design system"
                    description="Every component and layout state, from the locked prototype. Switch language and theme to review en, bn and ur in light and dark."
                    actions={
                        <>
                            <LanguageSwitcher variant="full" />
                            <ThemeToggle />
                        </>
                    }
                />

                <Section title="Layouts" id="layouts">
                    <ul className="flex flex-wrap gap-2">
                        {layouts.map((layout) => (
                            <li key={layout}>
                                <Button asChild variant="outline" className="min-h-11 capitalize">
                                    <Link href={design.layout(layout)}>{layout} layout</Link>
                                </Button>
                            </li>
                        ))}
                    </ul>
                </Section>

                <Section title="Colour tokens" id="tokens">
                    <ThemePair>
                        <ul className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                            {swatches.map((name) => (
                                <li key={name} className="flex items-center gap-2 text-xs">
                                    <span
                                        className="size-8 shrink-0 rounded-md border border-line"
                                        style={{ backgroundColor: `var(--color-${name})` }}
                                    />
                                    <code className="truncate font-mono">{name}</code>
                                </li>
                            ))}
                        </ul>
                    </ThemePair>
                </Section>

                <Section title="Typography" id="type">
                    <div className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-5">
                        <p className="eyebrow text-brand">Eyebrow · 11px</p>
                        <p className="font-display text-4xl font-extrabold">
                            Your skill is your passport.
                        </p>
                        <p className="font-display text-2xl font-bold">
                            Bricolage Grotesque display
                        </p>
                        <p>Figtree body text at 15px for readable, friendly interfaces.</p>
                        <p className="font-mono text-sm">
                            JetBrains Mono · UAE-EL-0001 · BSK-0004182
                        </p>
                        <p lang="bn">বাংলা: আপনার দক্ষতাই আপনার পাসপোর্ট।</p>
                        <p lang="hi">हिन्दी: आपका कौशल ही आपका पासपोर्ट है।</p>
                        <p lang="ur" dir="rtl">
                            اردو: آپ کی مہارت ہی آپ کا پاسپورٹ ہے۔
                        </p>
                        <p lang="si">සිංහල: ඔබේ කුසලතාව ඔබේ ගමන් බලපත්‍රයයි.</p>
                        <p lang="ta">தமிழ்: உங்கள் திறமையே உங்கள் கடவுச்சீட்டு.</p>
                    </div>
                </Section>

                <Section title="StatusChip & EvidenceBadge" id="chips">
                    <ThemePair>
                        <div className="flex flex-col gap-3">
                            <div className="flex flex-wrap gap-2">
                                {tones.map((tone) => (
                                    <StatusChip key={tone} label={`Tone ${tone}`} tone={tone} />
                                ))}
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {evidenceStates.map((state) => (
                                    <EvidenceBadge key={state} state={state} />
                                ))}
                            </div>
                        </div>
                    </ThemePair>
                </Section>

                <Section title="TruthCard" id="truth-card">
                    {truthCards.length > 0 ? (
                        <div className="grid items-start gap-4 lg:grid-cols-2 2xl:grid-cols-3">
                            {truthCards.map((job, index) => (
                                <TruthCard
                                    key={job.publicCode}
                                    job={job}
                                    preview={index === truthCards.length - 1}
                                />
                            ))}
                        </div>
                    ) : (
                        <EmptyState
                            icon={PackageOpen}
                            title="No sample jobs"
                            description="Run php artisan migrate:fresh --seed to load the prototype's sample world, then reload."
                        />
                    )}
                </Section>

                <Section title="StageTracker" id="stages">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Panel>
                            <StageTracker stages={mobilityStages} />
                        </Panel>
                        <Panel>
                            <StageTracker stages={applicationStages} variant="steps" />
                        </Panel>
                    </div>
                </Section>

                <Section title="SkillPassportCard & SkillShowTile" id="passport">
                    <div className="grid items-start gap-4 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                        <SkillPassportCard passport={rahim} />
                        <div className="grid grid-cols-2 gap-3">
                            <SkillShowTile
                                durationMs={27_000}
                                skills={['Industrial wiring', 'DB installation']}
                                moderation="approved"
                                workerName="Rahim Uddin"
                            />
                            <SkillShowTile
                                durationMs={30_000}
                                skills={['Cable termination']}
                                moderation="pending"
                            />
                            <SkillShowTile
                                durationMs={18_000}
                                skills={['Troubleshooting']}
                                moderation="rejected"
                            />
                            <SkillShowTile durationMs={null} skills={[]} moderation={null} />
                        </div>
                    </div>
                </Section>

                <Section title="KpiTile" id="kpi">
                    <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <KpiTile label="Open jobs" value="12" sub="Illustrative" />
                        <KpiTile label="Applicants" value="3,450" sub="All projects" />
                        <KpiTile label="Shortlisted" value="620" sub="18% of applicants" />
                        <KpiTile label="Joined" value="98" sub="Since Jan 2026" />
                    </div>
                </Section>

                <Section title="DataTable (cards below md) & FilterSheet" id="table">
                    <div className="flex flex-wrap gap-2">
                        {(['rows', 'loading', 'empty', 'error'] as const).map((state) => (
                            <Button
                                key={state}
                                variant={tableState === state ? 'default' : 'outline'}
                                size="sm"
                                className="min-h-11 capitalize sm:min-h-9"
                                onClick={() => setTableState(state)}
                            >
                                {state}
                            </Button>
                        ))}
                    </div>
                    <div className="grid items-start gap-4 md:grid-cols-[220px_minmax(0,1fr)]">
                        <FilterSheet
                            activeCount={2}
                            onClear={() => undefined}
                            onApply={() => undefined}
                        >
                            <div className="flex flex-col gap-3">
                                {['GCC experience', 'Passport ready', 'Has SkillShow'].map(
                                    (label, index) => (
                                        <div
                                            key={label}
                                            className="flex min-h-11 items-center gap-2"
                                        >
                                            <Checkbox
                                                id={`filter-${index}`}
                                                defaultChecked={index < 2}
                                            />
                                            <Label htmlFor={`filter-${index}`}>{label}</Label>
                                        </div>
                                    ),
                                )}
                            </div>
                        </FilterSheet>
                        <DataTable
                            caption="Sample candidates"
                            columns={columns}
                            rows={tableState === 'empty' ? [] : candidates}
                            rowKey={(row) => row.id}
                            loading={tableState === 'loading'}
                            error={
                                tableState === 'error'
                                    ? { onRetry: () => setTableState('rows') }
                                    : null
                            }
                        />
                    </div>
                </Section>

                <Section title="EmptyState & ErrorState" id="states">
                    <div className="grid gap-4 md:grid-cols-2">
                        <EmptyState title={t('state.empty.title')} description={t('table.empty')} />
                        <ErrorState onRetry={() => undefined} />
                    </div>
                </Section>

                <Section title="FileUpload & ConsentToggle" id="forms">
                    <div className="grid items-start gap-4 md:grid-cols-2">
                        <Panel>
                            <FileUpload
                                label="Passport scan"
                                value={file}
                                onChange={setFile}
                                accept={['application/pdf', 'image/jpeg', 'image/png']}
                                acceptLabel="PDF, JPG or PNG"
                                maxSizeMb={10}
                                sensitive
                            />
                            <FileUpload
                                label="Training certificate"
                                value={null}
                                onChange={() => undefined}
                                accept={['application/pdf']}
                                acceptLabel="PDF"
                                maxSizeMb={5}
                                error="This file is larger than 5 MB. Choose a smaller file."
                                className="mt-4"
                            />
                        </Panel>
                        <div className="flex flex-col gap-2">
                            <ConsentToggle
                                label="Skill Passport"
                                description="Required for every application"
                                checked={consents.passport}
                                onCheckedChange={(passport) =>
                                    setConsents({ ...consents, passport })
                                }
                            />
                            <ConsentToggle
                                label="SkillShow video"
                                description="30-second demonstration"
                                checked={consents.skillshow}
                                onCheckedChange={(skillshow) =>
                                    setConsents({ ...consents, skillshow })
                                }
                            />
                            <ConsentToggle
                                label="Passport scan"
                                description="Share only when the employer asks after selection."
                                checked={consents.scan}
                                onCheckedChange={(scan) => setConsents({ ...consents, scan })}
                                sensitive
                            />
                        </div>
                    </div>
                </Section>
            </div>
        </>
    );
}

function Section({ title, id, children }: { title: string; id: string; children: ReactNode }) {
    return (
        <section aria-labelledby={`${id}-title`} className="flex min-w-0 flex-col gap-4">
            <h2 id={`${id}-title`} className="font-display text-xl font-bold">
                {title}
            </h2>
            {children}
        </section>
    );
}

function Panel({ children, className }: { children: ReactNode; className?: string }) {
    return (
        <div className={cn('rounded-2xl border border-line bg-surface p-4', className)}>
            {children}
        </div>
    );
}

/** Renders the same content in forced light and dark scopes for side-by-side review. */
function ThemePair({ children }: { children: ReactNode }) {
    return (
        <div className="grid gap-3 md:grid-cols-2">
            {(['light', 'dark'] as const).map((theme) => (
                <div
                    key={theme}
                    className={cn(theme, 'rounded-2xl border border-line bg-bg p-4 text-ink')}
                >
                    <p className="mb-3 eyebrow text-muted">{theme}</p>
                    {children}
                </div>
            ))}
        </div>
    );
}
