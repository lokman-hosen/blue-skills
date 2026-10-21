import { Head, Link, useForm } from '@inertiajs/react';
import { FolderOpen, Flag } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { DataTable } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterPill } from '@/components/bs/filter-pill';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { StatusChip } from '@/components/bs/status-chip';
import { ReasonDialog } from '@/components/bs/trust/reason-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type ReportRow = App.Domain.Trust.Data.ReportData;

type Props = {
    reports: Paginated<ReportRow>;
    state: 'open' | 'all';
    dismissReasons: App.Domain.Trust.Enums.TrustReason[];
    priorities: App.Domain.Trust.Enums.CasePriority[];
};

/** Reports from workers and organizations (prototype x-trust, spec §10). */
export default function TrustReports({ reports, state, dismissReasons, priorities }: Props) {
    const { t, formatDate } = useTranslation();

    return (
        <>
            <Head title={t('trust.reports.title')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('trust.reports.title')}
                    description={t('trust.reports.body')}
                />
                <nav aria-label={t('trust.reports.title')} className="flex flex-wrap gap-1.5">
                    <FilterPill
                        href={admin.trust.reports.index()}
                        active={state === 'open'}
                        label={t('trust.reports.state.open')}
                    />
                    <FilterPill
                        href={admin.trust.reports.index({ query: { state: 'all' } })}
                        active={state === 'all'}
                        label={t('trust.reports.state.all')}
                    />
                </nav>
                <DataTable<ReportRow>
                    caption={t('trust.reports.title')}
                    rows={reports.data}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={Flag}
                            title={t('trust.reports.empty_title')}
                            description={t('trust.reports.empty_body')}
                        />
                    }
                    columns={[
                        {
                            key: 'report',
                            header: t('trust.reports.column.report'),
                            primary: true,
                            cell: (row) => (
                                <span className="flex flex-col gap-1">
                                    <span className="font-semibold">
                                        {t(`report.category.${row.category}`)}
                                    </span>
                                    <span className="text-xs text-muted">
                                        {t(`trust.about.${row.about}`)} · {row.subject}
                                    </span>
                                    {row.excerpt && (
                                        <span className="text-xs italic">“{row.excerpt}”</span>
                                    )}
                                    {row.description && (
                                        <span className="text-sm whitespace-pre-line">
                                            {row.description}
                                        </span>
                                    )}
                                </span>
                            ),
                        },
                        {
                            key: 'reporter',
                            header: t('trust.reports.column.reporter'),
                            cell: (row) => (
                                <span>
                                    {row.reporter ?? '—'}
                                    <span className="block text-xs text-muted">
                                        {formatDate(row.createdAt)}
                                    </span>
                                </span>
                            ),
                        },
                        {
                            key: 'status',
                            header: t('admin.organizations.status'),
                            cell: (row) => (
                                <span className="flex flex-col items-start gap-1">
                                    <StatusChip status={row.status} />
                                    {row.caseId && row.caseReference && (
                                        <Link
                                            href={admin.trust.cases.show(row.caseId)}
                                            className="font-mono text-xs text-brand hover:underline"
                                            dir="ltr"
                                        >
                                            {row.caseReference}
                                        </Link>
                                    )}
                                    {row.reasonCode && (
                                        <span className="text-xs text-muted">
                                            {t(`trust_reason.${row.reasonCode}`)}
                                        </span>
                                    )}
                                </span>
                            ),
                        },
                        {
                            key: 'actions',
                            header: t('cohorts.column.actions'),
                            cell: (row) =>
                                row.status.value === 'report_open' ? (
                                    <span className="flex flex-wrap gap-2">
                                        <OpenCaseDialog report={row} priorities={priorities} />
                                        <ReasonDialog
                                            id={`dismiss-${row.id}`}
                                            trigger={
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="min-h-11"
                                                >
                                                    {t('trust.reports.dismiss')}
                                                </Button>
                                            }
                                            title={t('trust.reports.dismiss_title')}
                                            description={t('trust.reports.dismiss_hint')}
                                            action={admin.trust.reports.dismiss.url(row.id)}
                                            reasons={dismissReasons}
                                            submitLabel={t('trust.reports.dismiss')}
                                        />
                                    </span>
                                ) : null,
                        },
                    ]}
                />
                <Pager page={reports} />
            </div>
        </>
    );
}

function OpenCaseDialog({
    report,
    priorities,
}: {
    report: ReportRow;
    priorities: App.Domain.Trust.Enums.CasePriority[];
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        report_id: report.id,
        priority: 'normal',
        summary: report.description.slice(0, 500),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(admin.trust.cases.store.url(), { onSuccess: () => setOpen(false) });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" className="min-h-11">
                    <FolderOpen aria-hidden className="size-4" />
                    {t('trust.reports.open_case')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('trust.reports.open_case')}</DialogTitle>
                    <DialogDescription>
                        {t('trust.reports.open_case_hint', { subject: report.subject })}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5 sm:max-w-56">
                        <Label htmlFor={`priority-${report.id}`}>{t('trust.priority')}</Label>
                        <NativeSelect
                            id={`priority-${report.id}`}
                            value={form.data.priority}
                            onChange={(event) => form.setData('priority', event.target.value)}
                        >
                            {priorities.map((priority) => (
                                <option key={priority} value={priority}>
                                    {t(`status.priority_${priority}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`summary-${report.id}`}>{t('trust.summary')}</Label>
                        <textarea
                            id={`summary-${report.id}`}
                            rows={3}
                            maxLength={500}
                            value={form.data.summary}
                            onChange={(event) => form.setData('summary', event.target.value)}
                            className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        />
                        <InputError message={form.errors.summary ?? form.errors.report_id} />
                    </div>
                    <Button
                        type="submit"
                        className="min-h-11 self-end"
                        disabled={form.processing || form.data.summary.trim() === ''}
                    >
                        {form.processing && <Spinner />}
                        {t('trust.reports.open_case')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
