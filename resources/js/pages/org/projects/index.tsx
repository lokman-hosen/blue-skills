import { Head, Link } from '@inertiajs/react';
import { FolderKanban } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import type { Paginated } from '@/components/bs/jobs/types';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import type { ProjectFormOptions } from '@/components/bs/projects/project-dialog';
import { ProjectDialog } from '@/components/bs/projects/project-dialog';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Project = App.Domain.Jobs.Data.RecruitmentProjectListItemData;

type Props = ProjectFormOptions & {
    projects: Paginated<Project>;
    canManage: boolean;
};

/** Recruitment projects: bulk hiring across jobs toward a target headcount (spec §6). */
export default function Projects({ projects, canManage, ...options }: Props) {
    const { t, formatNumber, formatDate } = useTranslation();
    const dialog = canManage ? <ProjectDialog {...options} /> : undefined;

    return (
        <>
            <Head title={t('nav.projects')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.projects')}
                    description={t('projects.body')}
                    actions={dialog}
                />
                {projects.data.length === 0 ? (
                    <EmptyState
                        icon={FolderKanban}
                        title={t('projects.empty_title')}
                        description={t('projects.empty_body')}
                        action={dialog}
                    />
                ) : (
                    <ul className="grid gap-4 lg:grid-cols-2">
                        {projects.data.map((project) => {
                            const progress = Math.min(
                                100,
                                Math.round(
                                    (project.joined / Math.max(1, project.targetHeadcount)) * 100,
                                ),
                            );

                            return (
                                <li key={project.id}>
                                    <Link
                                        href={org.projects.show(project.id)}
                                        className="flex h-full flex-col gap-3 rounded-2xl border border-line bg-surface p-4 hover:border-brand/60 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none sm:p-5"
                                    >
                                        <span className="flex items-start justify-between gap-3">
                                            <span className="min-w-0">
                                                <span
                                                    className="block eyebrow text-muted"
                                                    dir="ltr"
                                                >
                                                    {project.reference}
                                                </span>
                                                <span className="block text-lg font-semibold break-words">
                                                    {project.name}
                                                </span>
                                            </span>
                                            <StatusChip status={project.status} />
                                        </span>
                                        <span className="text-sm text-muted">
                                            {[
                                                t('projects.jobs_count', {
                                                    count: formatNumber(project.jobs),
                                                }),
                                                project.sourceCountryCodes.length > 0 &&
                                                    t('projects.sources', {
                                                        countries:
                                                            project.sourceCountryCodes.join(', '),
                                                    }),
                                                project.targetDate &&
                                                    t('projects.by_date', {
                                                        date: formatDate(project.targetDate),
                                                    }),
                                            ]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </span>
                                        <span className="flex flex-col gap-1.5">
                                            <span className="flex items-baseline justify-between text-sm">
                                                <span>{t('projects.joined_of_target')}</span>
                                                <span className="font-semibold tnum">
                                                    {formatNumber(project.joined)} /{' '}
                                                    {formatNumber(project.targetHeadcount)}
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
                                        </span>
                                    </Link>
                                </li>
                            );
                        })}
                    </ul>
                )}
                <Pager page={projects} />
            </div>
        </>
    );
}
