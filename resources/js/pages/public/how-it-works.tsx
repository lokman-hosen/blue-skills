import { Head, Link, usePage } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Building2,
    FileText,
    Lock,
    Mic,
    Plane,
    Route,
    ShieldCheck,
    UserCheck,
    UsersRound,
    Video,
    Wallet,
} from 'lucide-react';
import { PageHeader } from '@/components/bs/page-header';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, register } from '@/routes';
import jobs from '@/routes/jobs';
import worker from '@/routes/worker';

type Step = { key: string; icon: LucideIcon };

const workerSteps: Step[] = [
    { key: 'speak', icon: Mic },
    { key: 'show', icon: Video },
    { key: 'apply', icon: FileText },
    { key: 'track', icon: Plane },
];

const employerSteps: Step[] = [
    { key: 'verify', icon: ShieldCheck },
    { key: 'post', icon: FileText },
    { key: 'find', icon: UsersRound },
    { key: 'hire', icon: Route },
];

const promises: Step[] = [
    { key: 'free', icon: Wallet },
    { key: 'owned', icon: UserCheck },
    { key: 'private', icon: Lock },
];

type Cta = { href: NonNullable<InertiaLinkProps['href']>; label: string };

/** How It Works (spec §14, D-116): the flow for workers and for employers and agencies. */
export default function HowItWorks() {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const signedIn = Boolean(auth.user);
    const toDashboard: Cta = { href: dashboard(), label: t('nav.dashboard') };

    return (
        <>
            <Head title={t('how_it_works.title')} />
            <div className="flex flex-col gap-8">
                <PageHeader
                    eyebrow={t('nav.how_it_works')}
                    title={t('how_it_works.title')}
                    description={t('how_it_works.body')}
                />

                <div className="grid gap-4 lg:grid-cols-2">
                    <Journey
                        id="workers"
                        icon={Mic}
                        steps={workerSteps}
                        prefix="how_it_works.worker"
                        primary={
                            signedIn
                                ? toDashboard
                                : { href: worker.join(), label: t('how_it_works.worker.cta') }
                        }
                        secondary={{ href: jobs.index(), label: t('nav.find_jobs') }}
                    />
                    <Journey
                        id="employers"
                        icon={Building2}
                        steps={employerSteps}
                        prefix="how_it_works.employer"
                        primary={
                            signedIn
                                ? toDashboard
                                : { href: register(), label: t('auth.register.link') }
                        }
                    />
                </div>

                <section
                    aria-labelledby="promises-title"
                    className="rounded-xl border border-line bg-surface-2 p-5 sm:p-6"
                >
                    <h2 id="promises-title" className="font-display text-xl font-extrabold">
                        {t('how_it_works.promises.title')}
                    </h2>
                    <ul className="mt-4 grid gap-4 md:grid-cols-3">
                        {promises.map(({ key, icon: Icon }) => (
                            <li key={key} className="flex gap-3">
                                <Icon aria-hidden className="mt-0.5 size-5 shrink-0 text-brand" />
                                <div className="flex min-w-0 flex-col gap-0.5">
                                    <p className="font-semibold">
                                        {t(`how_it_works.promises.${key}.title`)}
                                    </p>
                                    <p className="text-sm text-muted">
                                        {t(`how_it_works.promises.${key}.body`)}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </>
    );
}

function Journey({
    id,
    icon: Icon,
    steps,
    prefix,
    primary,
    secondary,
}: {
    id: string;
    icon: LucideIcon;
    steps: Step[];
    prefix: string;
    primary: Cta;
    secondary?: Cta;
}) {
    const { t } = useTranslation();
    const titleId = `${id}-title`;

    return (
        <section
            id={id}
            aria-labelledby={titleId}
            className="flex flex-col gap-5 rounded-xl border border-line bg-surface p-5 sm:p-6"
        >
            <div className="flex flex-col gap-1">
                <span className="inline-flex items-center gap-1.5 text-xs font-bold tracking-wide text-brand uppercase">
                    <Icon aria-hidden className="size-4" />
                    {t(`${prefix}.eyebrow`)}
                </span>
                <h2 id={titleId} className="font-display text-2xl font-extrabold">
                    {t(`${prefix}.title`)}
                </h2>
            </div>

            <ol className="flex flex-col gap-4">
                {steps.map(({ key, icon: StepIcon }, index) => (
                    <li key={key} className="flex gap-3">
                        <span
                            aria-hidden
                            className="grid size-10 shrink-0 place-items-center rounded-lg bg-brand-soft text-brand"
                        >
                            <StepIcon className="size-5" />
                        </span>
                        <div className="flex min-w-0 flex-col gap-0.5">
                            <h3 className="font-semibold">
                                <span className="me-1.5 font-mono text-xs text-muted">
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                                {t(`${prefix}.steps.${key}.title`)}
                            </h3>
                            <p className="text-sm text-muted">{t(`${prefix}.steps.${key}.body`)}</p>
                        </div>
                    </li>
                ))}
            </ol>

            <div className="mt-auto flex flex-wrap gap-2">
                <Button asChild size="lg" className="min-h-12">
                    <Link href={primary.href}>{primary.label}</Link>
                </Button>
                {secondary && (
                    <Button asChild size="lg" variant="outline" className="min-h-12">
                        <Link href={secondary.href}>{secondary.label}</Link>
                    </Button>
                )}
            </div>
        </section>
    );
}
