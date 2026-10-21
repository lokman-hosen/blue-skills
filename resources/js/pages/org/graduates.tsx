import { Head, router } from '@inertiajs/react';
import { GraduationCap } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { FilterPill } from '@/components/bs/filter-pill';
import { GraduateTable } from '@/components/bs/institute/graduate-table';
import type { GraduateAbilities } from '@/components/bs/institute/graduate-table';
import type { Paginated } from '@/components/bs/jobs/types';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Filters = { cohort: string | null; state: 'joined' | 'waiting' | 'all' };

type Props = {
    graduates: Paginated<App.Domain.Institute.Data.CohortMemberData>;
    cohorts: App.Domain.Institute.Data.CohortSummaryData[];
    filters: Filters;
    abilities: GraduateAbilities & { manage: boolean };
    instituteVerified: boolean;
};

/** Graduates across all cohorts, with training claims and assessments (prototype i-grads). */
export default function Graduates({
    graduates,
    cohorts,
    filters,
    abilities,
    instituteVerified,
}: Props) {
    const { t } = useTranslation();
    const query = (next: Partial<Filters>) => {
        const merged = { ...filters, ...next };

        return {
            query: {
                cohort: merged.cohort ?? undefined,
                state: merged.state === 'joined' ? undefined : merged.state,
            },
        };
    };

    return (
        <>
            <Head title={t('nav.graduates')} />
            <div className="flex flex-col gap-5">
                <PageHeader title={t('nav.graduates')} description={t('graduates.body')} />
                {!instituteVerified && (
                    <p className="rounded-xl border border-warn/40 bg-warn-soft px-4 py-3 text-sm">
                        {t('institute.records.needs_verification')}
                    </p>
                )}
                <div className="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <nav aria-label={t('graduates.states')} className="flex flex-wrap gap-1.5">
                        {(['joined', 'waiting', 'all'] as const).map((state) => (
                            <FilterPill
                                key={state}
                                href={org.graduates.index(query({ state }))}
                                active={filters.state === state}
                                label={t(`graduates.state.${state}`)}
                            />
                        ))}
                    </nav>
                    <div className="flex flex-col gap-1.5 md:w-72">
                        <Label htmlFor="graduates-cohort">{t('cohorts.column.cohort')}</Label>
                        <NativeSelect
                            id="graduates-cohort"
                            value={filters.cohort ?? ''}
                            onChange={(event) =>
                                router.get(
                                    org.graduates.index.url(
                                        query({ cohort: event.target.value || null }),
                                    ),
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <option value="">{t('graduates.all_cohorts')}</option>
                            {cohorts.map((cohort) => (
                                <option key={cohort.id} value={cohort.id}>
                                    {cohort.courseTitle} · {cohort.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                </div>
                <GraduateTable
                    members={graduates.data}
                    abilities={abilities}
                    showCohort
                    empty={
                        <EmptyState
                            icon={GraduationCap}
                            title={t('graduates.empty_title')}
                            description={t('graduates.empty_body')}
                        />
                    }
                />
                <Pager page={graduates} />
                <p className="text-xs text-muted">{t('institute.records.note')}</p>
            </div>
        </>
    );
}
