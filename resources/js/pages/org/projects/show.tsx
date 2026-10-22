import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, RotateCcw, Users, XCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { DataTable } from '@/components/bs/data-table';
import type { DataTableColumn } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterPill } from '@/components/bs/filter-pill';
import type { Paginated } from '@/components/bs/jobs/types';
import { KpiTile } from '@/components/bs/kpi-tile';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import type { ProjectFormOptions } from '@/components/bs/projects/project-dialog';
import { ProjectDialog } from '@/components/bs/projects/project-dialog';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Project = App.Domain.Jobs.Data.RecruitmentProjectData;
type Applicant = App.Domain.Applications.Data.ApplicantListItemData & { jobTitle: string };
type Job = Project['jobs'][number];

type Props = ProjectFormOptions & {
    project: Project;
    applicants: Paginated<Applicant>;
    stage: string | null;
    filters: string[];
    movable: string[];
    rejectionReasons: App.Domain.Applications.Enums.RejectionReason[];
    canManage: boolean;
    canMoveApplicants: boolean;
};

/**
 * A recruitment project (spec §6, deck slide 18): stage counts toward the target, its jobs and
 * team, and every applicant across those jobs with bulk shortlisting and rejection.
 */
export default function ProjectShow({
    project,
    applicants,
    stage,
    filters,
    movable,
    rejectionReasons,
    canManage,
    canMoveApplicants,
    ...options
}: Props) {
    const { t, formatNumber, formatDate } = useTranslation();
    const summary = project.summary;
    const status = summary.status.value.replace('project_', '');
    const joined = project.stages.find((item) => item.key === 'joined')?.count ?? 0;
    const progress = Math.min(
        100,
        Math.round((joined / Math.max(1, summary.targetHeadcount)) * 100),
    );
    const act = (action: 'complete' | 'cancel' | 'reopen') =>
        router.post(org.projects.status.url([summary.id, action]), {}, { preserveScroll: true });

    return (
        <>
            <Head title={summary.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={summary.reference}
                    title={summary.name}
                    description={[
                        summary.sourceCountryCodes.length > 0 &&
                            t('projects.sources', {
                                countries: summary.sourceCountryCodes.join(', '),
                            }),
                        summary.destinationCountryCode &&
                            t('projects.to', { country: summary.destinationCountryCode }),
                        summary.targetDate &&
                            t('projects.by_date', { date: formatDate(summary.targetDate) }),
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                    back={{ href: org.projects.index(), label: t('nav.projects') }}
                    actions={
                        <div className="flex flex-wrap items-center gap-2">
                            <StatusChip status={summary.status} />
                            {canManage && status === 'active' && (
                                <>
                                    <ProjectDialog project={summary} {...options} />
                                    <Button
                                        variant="outline"
                                        className="min-h-11"
                                        onClick={() => act('complete')}
                                    >
                                        <CheckCircle2 aria-hidden className="size-4" />
                                        {t('projects.complete')}
                                    </Button>
                                    <ConfirmDialog
                                        trigger={
                                            <Button variant="ghost" className="min-h-11 text-bad">
                                                <XCircle aria-hidden className="size-4" />
                                                {t('projects.cancel')}
                                            </Button>
                                        }
                                        title={t('projects.cancel_title', { name: summary.name })}
                                        description={t('projects.cancel_hint')}
                                        confirmLabel={t('projects.cancel')}
                                        destructive
                                        onConfirm={() => act('cancel')}
                                    />
                                </>
                            )}
                            {canManage && status === 'completed' && (
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() => act('reopen')}
                                >
                                    <RotateCcw aria-hidden className="size-4" />
                                    {t('projects.reopen')}
                                </Button>
                            )}
                        </div>
                    }
                />

                <section aria-labelledby="stages-heading" className="flex flex-col gap-3">
                    <h2 id="stages-heading" className="eyebrow text-muted">
                        {t('projects.stages')}
                    </h2>
                    <ol className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        {project.stages.map((item) => (
                            <li key={item.key}>
                                <KpiTile
                                    label={t(`projects.stage.${item.key}`)}
                                    value={formatNumber(item.count)}
                                />
                            </li>
                        ))}
                    </ol>
                    <div className="flex flex-col gap-1.5 rounded-xl border border-line bg-surface p-4">
                        <span className="flex items-baseline justify-between text-sm">
                            <span>{t('projects.joined_of_target')}</span>
                            <span className="font-semibold tnum">
                                {formatNumber(joined)} / {formatNumber(summary.targetHeadcount)}
                            </span>
                        </span>
                        <span
                            role="progressbar"
                            aria-valuemin={0}
                            aria-valuemax={100}
                            aria-valuenow={progress}
                            aria-label={t('projects.joined_of_target')}
                            className="h-2 overflow-hidden rounded-full bg-surface-2"
                        >
                            <span
                                className="block h-full rounded-full bg-ok"
                                style={{ width: `${progress}%` }}
                            />
                        </span>
                    </div>
                </section>

                <div className="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <Panel title={t('projects.jobs')}>
                        <DataTable<Job>
                            caption={t('projects.jobs')}
                            rows={project.jobs}
                            rowKey={(row) => row.id}
                            empty={<p className="text-sm text-muted">{t('projects.no_jobs')}</p>}
                            columns={[
                                {
                                    key: 'job',
                                    header: t('projects.column.job'),
                                    primary: true,
                                    cell: (row) => (
                                        <Link
                                            href={org.jobs.applicants.url(row.code)}
                                            className="font-semibold text-brand hover:underline"
                                        >
                                            {row.title}
                                            <span
                                                className="block font-mono text-xs font-normal text-muted"
                                                dir="ltr"
                                            >
                                                {row.code}
                                            </span>
                                        </Link>
                                    ),
                                },
                                {
                                    key: 'status',
                                    header: t('admin.organizations.status'),
                                    cell: (row) => <StatusChip status={row.status} />,
                                },
                                {
                                    key: 'headcount',
                                    header: t('projects.column.headcount'),
                                    align: 'end',
                                    cell: (row) => formatNumber(row.headcount),
                                },
                                {
                                    key: 'applicants',
                                    header: t('projects.column.applicants'),
                                    align: 'end',
                                    cell: (row) => formatNumber(row.applicants),
                                },
                                {
                                    key: 'joined',
                                    header: t('projects.stage.joined'),
                                    align: 'end',
                                    cell: (row) => formatNumber(row.joined),
                                },
                            ]}
                        />
                    </Panel>
                    <Panel title={t('projects.team')}>
                        {project.team.length === 0 ? (
                            <p className="text-sm text-muted">{t('projects.no_team')}</p>
                        ) : (
                            <ul className="flex flex-col gap-1.5 text-sm">
                                {project.team.map((member) => (
                                    <li key={member.id}>{member.name}</li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                <ProjectApplicants
                    projectId={summary.id}
                    applicants={applicants}
                    stage={stage}
                    filters={filters}
                    movable={movable}
                    rejectionReasons={rejectionReasons}
                    canManage={canMoveApplicants}
                />
            </div>
        </>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <h2 className="eyebrow text-muted">{title}</h2>
            {children}
        </section>
    );
}

function ProjectApplicants({
    projectId,
    applicants,
    stage,
    filters,
    movable,
    rejectionReasons,
    canManage,
}: {
    projectId: string;
    applicants: Paginated<Applicant>;
    stage: string | null;
    filters: string[];
    movable: string[];
    rejectionReasons: App.Domain.Applications.Enums.RejectionReason[];
    canManage: boolean;
}) {
    const { t, formatDate, formatNumber } = useTranslation();
    const [selected, setSelected] = useState<string[]>([]);
    const [target, setTarget] = useState(movable[2] ?? movable[0] ?? 'shortlisted');
    const [reason, setReason] = useState<string>(rejectionReasons[0] ?? '');
    const [busy, setBusy] = useState(false);

    const toggle = (id: string, on: boolean) =>
        setSelected((current) => (on ? [...current, id] : current.filter((value) => value !== id)));
    const options = {
        preserveScroll: true,
        onStart: () => setBusy(true),
        onFinish: () => setBusy(false),
        onSuccess: () => setSelected([]),
    };

    const columns: DataTableColumn<Applicant>[] = [
        ...(canManage
            ? [
                  {
                      key: 'select',
                      header: t('applicants.select'),
                      cell: (row: Applicant) => (
                          <input
                              type="checkbox"
                              aria-label={t('applicants.select_name', { name: row.name })}
                              checked={selected.includes(row.id)}
                              onChange={(event) => toggle(row.id, event.target.checked)}
                              className="size-5 accent-[var(--color-brand)]"
                          />
                      ),
                      className: 'w-12',
                  },
              ]
            : []),
        {
            key: 'name',
            header: t('applicants.name'),
            primary: true,
            cell: (row) => (
                <Link
                    href={org.applicants.show(row.id)}
                    className="font-semibold text-brand hover:underline"
                >
                    {row.name}
                    <span className="block text-xs font-normal text-muted">
                        {[row.occupation, row.currentCountry].filter(Boolean).join(' · ')}
                    </span>
                </Link>
            ),
        },
        { key: 'job', header: t('projects.column.job'), cell: (row) => row.jobTitle },
        {
            key: 'match',
            header: t('applicants.match'),
            cell: (row) => (
                <StatusChip
                    tone="brand"
                    icon={false}
                    label={t('jobs.match.summary', {
                        met: formatNumber(row.met),
                        total: formatNumber(row.total),
                    })}
                />
            ),
        },
        {
            key: 'status',
            header: t('applicants.stage'),
            cell: (row) => <StatusChip status={row.status} />,
        },
        {
            key: 'applied',
            header: t('applicants.applied'),
            cell: (row) => formatDate(row.appliedAt),
        },
    ];

    return (
        <section aria-labelledby="applicants-heading" className="flex flex-col gap-3">
            <h2 id="applicants-heading" className="eyebrow text-muted">
                {t('projects.applicants')}
            </h2>
            <nav aria-label={t('applicants.stages')} className="flex flex-wrap gap-1.5">
                <FilterPill
                    href={org.projects.show.url(projectId)}
                    active={stage === null}
                    label={t('applicants.all')}
                />
                {filters.map((value) => (
                    <FilterPill
                        key={value}
                        href={org.projects.show.url(projectId, { query: { stage: value } })}
                        active={stage === value}
                        label={t(`status.${value}`)}
                    />
                ))}
            </nav>

            {canManage && selected.length > 0 && (
                <div
                    role="region"
                    aria-label={t('applicants.bulk')}
                    className="sticky top-16 z-10 flex flex-wrap items-end gap-2 rounded-xl border border-brand/40 bg-brand-soft p-3"
                >
                    <p className="me-auto self-center text-sm font-semibold">
                        {t('applicants.selected', { count: formatNumber(selected.length) })}
                    </p>
                    <div className="flex flex-col gap-1">
                        <Label htmlFor="bulk-stage" className="text-xs">
                            {t('applicants.move_to')}
                        </Label>
                        <NativeSelect
                            id="bulk-stage"
                            value={target}
                            onChange={(event) => setTarget(event.target.value)}
                        >
                            {movable.map((value) => (
                                <option key={value} value={value}>
                                    {t(`status.${value}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <Button
                        className="min-h-11"
                        disabled={busy}
                        onClick={() =>
                            router.post(
                                org.applicants.move.url(),
                                { application_ids: selected, stage: target },
                                options,
                            )
                        }
                    >
                        {busy && <Spinner />}
                        {t('applicants.move')}
                    </Button>
                    <div className="flex flex-col gap-1">
                        <Label htmlFor="bulk-reason" className="text-xs">
                            {t('projects.reject_reason')}
                        </Label>
                        <NativeSelect
                            id="bulk-reason"
                            value={reason}
                            onChange={(event) => setReason(event.target.value)}
                        >
                            {rejectionReasons.map((value) => (
                                <option key={value} value={value}>
                                    {t(`rejection_reason.${value}`)}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <ConfirmDialog
                        trigger={
                            <Button variant="outline" className="min-h-11 text-bad" disabled={busy}>
                                {t('projects.reject_selected')}
                            </Button>
                        }
                        title={t('projects.reject_title', { count: formatNumber(selected.length) })}
                        description={t('projects.reject_hint')}
                        confirmLabel={t('projects.reject_selected')}
                        destructive
                        onConfirm={() =>
                            router.post(
                                org.applicants.rejectMany.url(),
                                { application_ids: selected, reason },
                                options,
                            )
                        }
                    />
                    <Button variant="ghost" className="min-h-11" onClick={() => setSelected([])}>
                        {t('common.cancel')}
                    </Button>
                </div>
            )}

            <DataTable
                caption={t('projects.applicants')}
                columns={columns}
                rows={applicants.data}
                rowKey={(row) => row.id}
                empty={
                    <EmptyState
                        icon={Users}
                        title={t('projects.applicants_empty_title')}
                        description={t('projects.applicants_empty_body')}
                    />
                }
            />
            <Pager page={applicants} />
        </section>
    );
}
