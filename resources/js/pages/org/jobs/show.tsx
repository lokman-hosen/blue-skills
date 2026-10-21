import { Form, Head, Link } from '@inertiajs/react';
import { Pencil, Users } from 'lucide-react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import { TruthCard } from '@/components/bs/truth-card';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    job: App.Domain.Jobs.Data.JobTruthCardData;
    lastDecision: App.Domain.Verification.Data.ReviewDecisionData | null;
    abilities: { update: boolean; submit: boolean; manage: boolean };
    /** Number of applications, or null when the member cannot see applicants. */
    applicants: number | null;
};

/** Which availability moves each status allows (mirrors the job state machine). */
const MOVES: Record<string, ('pause' | 'resume' | 'close' | 'archive')[]> = {
    draft: ['archive'],
    published: ['pause', 'close'],
    paused: ['resume', 'close'],
    suspended: ['close'],
    closed: ['archive'],
};

/** One of the organization's jobs: its Truth Card, review outcome and actions. */
export default function OrgJob({ job, lastDecision, abilities, applicants }: Props) {
    const { t } = useTranslation();
    const status = job.status.value;
    const sentBack = status === 'more_information_requested' || status === 'rejected';

    return (
        <>
            <Head title={job.title} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={job.publicCode}
                    title={job.title}
                    back={{ href: JobController.index.url(), label: t('jobs.title') }}
                    actions={<StatusChip status={job.status} />}
                />

                {sentBack && lastDecision && (
                    <div
                        role="status"
                        className="flex flex-col gap-1 rounded-xl border border-warn/30 bg-warn-soft px-4 py-3 text-sm"
                    >
                        <p className="font-semibold">{t(`jobs.decision.${status}`)}</p>
                        {lastDecision.reasonCode && (
                            <p>{t(`review_reason.${lastDecision.reasonCode}`)}</p>
                        )}
                        {lastDecision.note && (
                            <p className="text-muted" dir="auto">
                                “{lastDecision.note}”
                            </p>
                        )}
                    </div>
                )}
                {(status === 'submitted' || status === 'under_review') && (
                    <p
                        role="status"
                        className="rounded-xl border border-line bg-surface-2 px-4 py-3 text-sm"
                    >
                        {t('jobs.in_review_note')}
                    </p>
                )}

                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,320px)] lg:items-start">
                    <TruthCard job={job} preview />
                    <aside className="flex flex-col gap-3 rounded-xl border border-line bg-surface p-4">
                        <h2 className="eyebrow text-muted">{t('jobs.actions')}</h2>
                        {applicants !== null && (
                            <Button asChild className="min-h-11">
                                <Link href={org.jobs.applicants(job.publicCode)}>
                                    <Users aria-hidden className="size-4" />
                                    {t('applicants.view', { count: applicants })}
                                </Link>
                            </Button>
                        )}
                        {abilities.update && (
                            <Button asChild variant="outline" className="min-h-11">
                                <Link
                                    href={JobController.edit.url([job.publicCode, 'requirement'])}
                                >
                                    <Pencil aria-hidden className="size-4" />
                                    {t('jobs.edit')}
                                </Link>
                            </Button>
                        )}
                        {abilities.submit &&
                            (status === 'draft' || status === 'more_information_requested') && (
                                <Button asChild className="min-h-11">
                                    <Link
                                        href={JobController.edit.url([job.publicCode, 'preview'])}
                                    >
                                        {t('jobs.review_and_submit')}
                                    </Link>
                                </Button>
                            )}
                        {abilities.manage &&
                            (MOVES[status] ?? []).map((action) => (
                                <Form
                                    key={action}
                                    {...JobController.availability.form([job.publicCode, action])}
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                className="min-h-11 w-full"
                                                disabled={processing}
                                            >
                                                {t(`jobs.availability.${action}`)}
                                            </Button>
                                            <InputError message={errors.job} />
                                        </>
                                    )}
                                </Form>
                            ))}
                        {status === 'published' && (
                            <p className="text-xs text-muted">{t('jobs.material_change_note')}</p>
                        )}
                    </aside>
                </div>
            </div>
        </>
    );
}
