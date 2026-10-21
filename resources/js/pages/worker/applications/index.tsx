import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, ChevronRight, CircleAlert, ListChecks, MailOpen } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import jobs from '@/routes/jobs';
import worker from '@/routes/worker';

type Props = {
    applications: App.Domain.Applications.Data.WorkerApplicationListItemData[];
    invitations: App.Domain.Applications.Data.JobInvitationData[];
};

/** "My applications": every application with its stage and next step (spec §6). */
export default function ApplicationsIndex({ applications, invitations }: Props) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <Head title={t('nav.applications')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    title={t('nav.applications')}
                    description={t('applications.index.body')}
                />

                {invitations.length > 0 && (
                    <section aria-labelledby="invitations-heading" className="flex flex-col gap-3">
                        <h2
                            id="invitations-heading"
                            className="flex items-center gap-2 font-semibold"
                        >
                            <MailOpen aria-hidden className="size-5 text-brand" />
                            {t('invitations.title')}
                        </h2>
                        <ul className="flex flex-col gap-3">
                            {invitations.map((invitation) => (
                                <li
                                    key={invitation.id}
                                    className="flex flex-col gap-2 rounded-2xl border-2 border-brand/40 bg-brand-soft/40 p-4"
                                >
                                    <p className="text-sm">
                                        {t('invitations.from', { org: invitation.organization })}
                                    </p>
                                    <p className="font-semibold">
                                        {invitation.job.title}
                                        <span className="block text-xs font-normal text-muted">
                                            {[
                                                invitation.job.destinationCity,
                                                invitation.job.destinationCountryName,
                                            ]
                                                .filter(Boolean)
                                                .join(', ')}{' '}
                                            · <span dir="ltr">{invitation.job.code}</span>
                                        </span>
                                    </p>
                                    {invitation.note && (
                                        <p className="rounded-lg bg-surface px-3 py-2 text-sm whitespace-pre-line">
                                            {invitation.note}
                                        </p>
                                    )}
                                    {invitation.status === 'expired' ? (
                                        <p className="text-sm text-muted">
                                            {t('invitations.expired')}
                                        </p>
                                    ) : (
                                        <div className="flex flex-wrap gap-2">
                                            <Button asChild className="min-h-11">
                                                <Link
                                                    href={worker.apply.create(invitation.job.code)}
                                                >
                                                    {t('invitations.view_and_apply')}
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                className="min-h-11"
                                                onClick={() =>
                                                    router.post(
                                                        worker.applications.invitations.decline.url(
                                                            invitation.id,
                                                        ),
                                                        {},
                                                        { preserveScroll: true },
                                                    )
                                                }
                                            >
                                                {t('invitations.decline')}
                                            </Button>
                                        </div>
                                    )}
                                    <p className="text-xs text-muted">{t('invitations.safety')}</p>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}

                {applications.length === 0 ? (
                    <EmptyState
                        icon={ListChecks}
                        title={t('applications.index.empty_title')}
                        description={t('applications.index.empty_body')}
                        action={
                            <Button asChild className="min-h-11">
                                <Link href={jobs.index()}>{t('jobs.search.title')}</Link>
                            </Button>
                        }
                    />
                ) : (
                    <ul className="flex flex-col gap-3">
                        {applications.map((application) => (
                            <li key={application.id}>
                                <Link
                                    href={worker.applications.show(application.id)}
                                    className="flex items-start gap-3 rounded-2xl border border-line bg-surface p-4 hover:border-brand/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                >
                                    <span className="flex min-w-0 flex-1 flex-col gap-1.5">
                                        <span className="flex flex-wrap items-start justify-between gap-2">
                                            <span className="min-w-0">
                                                <span className="block font-semibold">
                                                    {application.job.title}
                                                </span>
                                                <span className="block text-xs text-muted">
                                                    {[
                                                        application.job.employerName,
                                                        application.job.destinationCity,
                                                        application.job.destinationCountryName,
                                                    ]
                                                        .filter(Boolean)
                                                        .join(' · ')}
                                                </span>
                                            </span>
                                            <StatusChip status={application.status} />
                                        </span>
                                        <span className="text-[13px]">
                                            <span className="text-muted">
                                                {t('applications.next.label')}{' '}
                                            </span>
                                            {t(`applications.next.${application.nextStep}`)}
                                        </span>
                                        {application.upcomingInterviewAt && (
                                            <span className="flex items-center gap-1.5 text-[13px] font-semibold text-stamp">
                                                <CalendarClock aria-hidden className="size-4" />
                                                {t('applications.interview.on', {
                                                    date: formatDate(
                                                        application.upcomingInterviewAt,
                                                        {
                                                            dateStyle: 'medium',
                                                            timeStyle: 'short',
                                                        },
                                                    ),
                                                })}
                                            </span>
                                        )}
                                        {application.jobChanged && (
                                            <span className="flex items-center gap-1.5 text-[13px] font-semibold text-warn">
                                                <CircleAlert aria-hidden className="size-4" />
                                                {t('applications.job_changed.short')}
                                            </span>
                                        )}
                                        <span className="text-xs text-muted">
                                            {t('applications.applied_on', {
                                                date: formatDate(application.appliedAt),
                                            })}
                                        </span>
                                    </span>
                                    <ChevronRight
                                        aria-hidden
                                        className="mt-1 size-4 shrink-0 text-muted rtl:rotate-180"
                                    />
                                </Link>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}
