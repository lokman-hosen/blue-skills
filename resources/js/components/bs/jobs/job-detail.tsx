import { Head, Link, useForm } from '@inertiajs/react';
import { Check } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { MatchSummary } from '@/components/bs/jobs/match-summary';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { TruthCard } from '@/components/bs/truth-card';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { login } from '@/routes';
import jobs from '@/routes/jobs';
import worker from '@/routes/worker';

export type JobDetailProps = {
    job: App.Domain.Jobs.Data.JobTruthCardData;
    match: App.Domain.Jobs.Data.MatchSummaryData | null;
    reportCategories: App.Domain.Trust.Enums.ReportCategory[];
    canReport: boolean;
    /** The signed-in worker's application to this job, if any. */
    application?: { id: string } | null;
    acceptsApplications?: boolean;
};

/** The Job Truth Card page (spec §5) with matching for workers and a report control. */
export function JobDetail({
    job,
    match,
    reportCategories,
    canReport,
    application = null,
    acceptsApplications = false,
}: JobDetailProps) {
    const { t } = useTranslation();
    const [reporting, setReporting] = useState(false);
    const isWorker = match !== null;

    return (
        <>
            <Head title={job.title} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={job.publicCode}
                    title={job.title}
                    back={{ href: jobs.index(), label: t('jobs.search.title') }}
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,340px)] lg:items-start">
                    <TruthCard job={job} onReport={() => setReporting(true)} />
                    <aside className="flex flex-col gap-3 lg:sticky lg:top-20">
                        {match && <MatchSummary match={match} />}
                        {isWorker && application ? (
                            <Button asChild variant="outline" className="min-h-12 w-full text-base">
                                <Link href={worker.applications.show(application.id)}>
                                    <Check aria-hidden className="size-4" />
                                    {t('jobs.apply.applied')}
                                </Link>
                            </Button>
                        ) : isWorker && acceptsApplications ? (
                            <Button asChild className="min-h-12 w-full text-base">
                                <Link href={worker.apply.create(job.publicCode)}>
                                    <Check aria-hidden className="size-4" />
                                    {t('jobs.apply.cta')}
                                </Link>
                            </Button>
                        ) : isWorker ? (
                            <>
                                <Button disabled className="min-h-12 w-full text-base">
                                    <Check aria-hidden className="size-4" />
                                    {t('jobs.apply.cta')}
                                </Button>
                                <p className="text-xs text-muted">{t('jobs.apply.closed')}</p>
                            </>
                        ) : (
                            <Button asChild className="min-h-12 w-full text-base">
                                <Link href={worker.join()}>{t('jobs.apply.sign_up')}</Link>
                            </Button>
                        )}
                    </aside>
                </div>
            </div>
            <ReportDialog
                open={reporting}
                onOpenChange={setReporting}
                job={job}
                categories={reportCategories}
                canReport={canReport}
            />
        </>
    );
}

function ReportDialog({
    open,
    onOpenChange,
    job,
    categories,
    canReport,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    job: App.Domain.Jobs.Data.JobTruthCardData;
    categories: App.Domain.Trust.Enums.ReportCategory[];
    canReport: boolean;
}) {
    const { t } = useTranslation();
    const form = useForm({ category: '', description: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(jobs.report.url(job.publicCode), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('jobs.report.title')}</DialogTitle>
                    <DialogDescription>
                        {t('jobs.report.body', { code: job.publicCode })}
                    </DialogDescription>
                </DialogHeader>
                {canReport ? (
                    <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="report-category">{t('jobs.report.category')}</Label>
                            <NativeSelect
                                id="report-category"
                                value={form.data.category}
                                onChange={(event) => form.setData('category', event.target.value)}
                            >
                                <option value="" disabled>
                                    {t('common.choose')}
                                </option>
                                {categories.map((category) => (
                                    <option key={category} value={category}>
                                        {t(`report.category.${category}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.category} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="report-description">
                                {t('jobs.report.description')}
                            </Label>
                            <textarea
                                id="report-description"
                                value={form.data.description}
                                maxLength={1000}
                                rows={4}
                                onChange={(event) =>
                                    form.setData('description', event.target.value)
                                }
                                className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError message={form.errors.description} />
                        </div>
                        <Button
                            type="submit"
                            variant="destructive"
                            className="min-h-11 self-start"
                            disabled={form.processing || form.data.category === ''}
                        >
                            {form.processing && <Spinner />}
                            {t('jobs.report.send')}
                        </Button>
                    </form>
                ) : (
                    <Button asChild className="min-h-11 self-start">
                        <Link href={login()}>{t('jobs.report.sign_in')}</Link>
                    </Button>
                )}
            </DialogContent>
        </Dialog>
    );
}
