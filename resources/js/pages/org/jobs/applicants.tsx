import { Head, Link, router } from '@inertiajs/react';
import { CalendarClock, Users, Video } from 'lucide-react';
import { useState } from 'react';
import JobController from '@/actions/App/Http/Controllers/Organization/JobController';
import { DataTable } from '@/components/bs/data-table';
import type { DataTableColumn } from '@/components/bs/data-table';
import { EmptyState } from '@/components/bs/empty-state';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { StatusChip } from '@/components/bs/status-chip';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import org from '@/routes/org';

type Applicant = App.Domain.Applications.Data.ApplicantListItemData;

type Props = {
    job: { code: string; title: string; headcount: number };
    applicants: Paginated<Applicant>;
    counts: Record<string, number>;
    stage: string | null;
    filters: string[];
    movable: string[];
    canManage: boolean;
};

/**
 * A job's recruitment pipeline (spec §6, deck slide 18): stage counts, applicants with
 * "X of Y requirements met", and moving one or many applicants to the next stage.
 */
export default function Applicants({
    job,
    applicants,
    counts,
    stage,
    filters,
    movable,
    canManage,
}: Props) {
    const { t, formatDate, formatNumber } = useTranslation();
    const [selected, setSelected] = useState<string[]>([]);
    const [target, setTarget] = useState(movable[0] ?? 'reviewed');
    const [moving, setMoving] = useState(false);
    const total = Object.values(counts).reduce((sum, count) => sum + count, 0);

    const toggle = (id: string, on: boolean) =>
        setSelected((current) => (on ? [...current, id] : current.filter((value) => value !== id)));

    const move = () =>
        router.post(
            org.applicants.move.url(),
            { application_ids: selected, stage: target },
            {
                preserveScroll: true,
                onStart: () => setMoving(true),
                onFinish: () => setMoving(false),
                onSuccess: () => setSelected([]),
            },
        );

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
                        {[
                            row.occupation,
                            row.yearsExperience !== null &&
                                t('passport.years', { count: row.yearsExperience }),
                            row.currentCountry,
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </span>
                </Link>
            ),
        },
        {
            key: 'match',
            header: t('applicants.match'),
            cell: (row) => (
                <span className="flex flex-wrap items-center gap-1.5">
                    <StatusChip
                        tone="brand"
                        icon={false}
                        label={t('jobs.match.summary', {
                            met: formatNumber(row.met),
                            total: formatNumber(row.total),
                        })}
                    />
                    {row.hasSkillShow && (
                        <span className="inline-flex items-center gap-1 text-xs text-muted">
                            <Video aria-hidden className="size-3.5" />
                            {t('nav.skillshow')}
                        </span>
                    )}
                </span>
            ),
        },
        {
            key: 'status',
            header: t('applicants.stage'),
            cell: (row) => (
                <span className="flex flex-col items-start gap-1">
                    <StatusChip status={row.status} />
                    {row.upcomingInterviewAt && (
                        <span className="inline-flex items-center gap-1 text-xs text-stamp">
                            <CalendarClock aria-hidden className="size-3.5" />
                            {formatDate(row.upcomingInterviewAt, {
                                dateStyle: 'medium',
                                timeStyle: 'short',
                            })}
                        </span>
                    )}
                </span>
            ),
        },
        {
            key: 'assignee',
            header: t('applicants.handled_by'),
            cell: (row) => row.assigneeName ?? <span className="text-muted">—</span>,
        },
        {
            key: 'applied',
            header: t('applicants.applied'),
            cell: (row) => formatDate(row.appliedAt),
        },
    ];

    return (
        <>
            <Head title={t('applicants.title', { job: job.title })} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={job.code}
                    title={t('applicants.title', { job: job.title })}
                    description={t('applicants.summary', {
                        total: formatNumber(total),
                        headcount: formatNumber(job.headcount),
                    })}
                    back={{ href: JobController.show.url(job.code), label: job.title }}
                />

                <nav aria-label={t('applicants.stages')} className="flex flex-wrap gap-1.5">
                    <StageLink
                        href={org.jobs.applicants.url(job.code)}
                        active={stage === null}
                        label={t('applicants.all')}
                        count={formatNumber(total)}
                    />
                    {filters
                        .filter((value) => (counts[value] ?? 0) > 0 || movable.includes(value))
                        .map((value) => (
                            <StageLink
                                key={value}
                                href={org.jobs.applicants.url(job.code, {
                                    query: { stage: value },
                                })}
                                active={stage === value}
                                label={t(`status.${value}`)}
                                count={formatNumber(counts[value] ?? 0)}
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
                        <Button className="min-h-11" onClick={move} disabled={moving}>
                            {moving && <Spinner />}
                            {t('applicants.move')}
                        </Button>
                        <Button
                            variant="ghost"
                            className="min-h-11"
                            onClick={() => setSelected([])}
                        >
                            {t('common.cancel')}
                        </Button>
                    </div>
                )}

                <DataTable
                    caption={t('applicants.title', { job: job.title })}
                    columns={columns}
                    rows={applicants.data}
                    rowKey={(row) => row.id}
                    empty={
                        <EmptyState
                            icon={Users}
                            title={
                                stage === null
                                    ? t('applicants.empty_title')
                                    : t('applicants.empty_stage_title')
                            }
                            description={t('applicants.empty_body')}
                        />
                    }
                />
                <Pager page={applicants} />
            </div>
        </>
    );
}

function StageLink({
    href,
    active,
    label,
    count,
}: {
    href: string;
    active: boolean;
    label: string;
    count: string;
}) {
    return (
        <Link
            href={href}
            preserveScroll
            aria-current={active ? 'page' : undefined}
            className={cn(
                'inline-flex min-h-11 items-center gap-1.5 rounded-full border px-3 text-sm font-semibold',
                active
                    ? 'border-brand bg-brand text-on-brand'
                    : 'border-line bg-surface hover:border-brand/60',
            )}
        >
            {label}
            <span className={cn('text-xs tnum', active ? 'opacity-90' : 'text-muted')}>
                {count}
            </span>
        </Link>
    );
}
