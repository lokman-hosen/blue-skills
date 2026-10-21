import { Head, Link, useForm } from '@inertiajs/react';
import { BookOpen, GraduationCap, Pencil, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { EmptyState } from '@/components/bs/empty-state';
import { CohortFields } from '@/components/bs/institute/cohort-fields';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Course = App.Domain.Institute.Data.CourseData;
type Option = App.Domain.Taxonomy.Data.TaxonomyOptionData;

type Props = {
    courses: Course[];
    occupations: Option[];
    canManage: boolean;
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** The institute's courses and their cohorts (spec §7, prototype i-cohort). */
export default function Courses({ courses, occupations, canManage }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.courses')} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    title={t('nav.courses')}
                    description={t('courses.body')}
                    actions={canManage && <CourseDialog occupations={occupations} />}
                />
                {courses.length === 0 ? (
                    <EmptyState
                        icon={BookOpen}
                        title={t('courses.empty_title')}
                        description={t('courses.empty_body')}
                        action={canManage ? <CourseDialog occupations={occupations} /> : undefined}
                    />
                ) : (
                    <ul className="grid gap-4 lg:grid-cols-2">
                        {courses.map((course) => (
                            <li key={course.id}>
                                <CourseCard
                                    course={course}
                                    occupations={occupations}
                                    canManage={canManage}
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

function CourseCard({
    course,
    occupations,
    canManage,
}: {
    course: Course;
    occupations: Option[];
    canManage: boolean;
}) {
    const { t, formatDate, formatNumber } = useTranslation();

    return (
        <section className="flex h-full flex-col gap-3 rounded-2xl border border-line bg-surface p-4 sm:p-5">
            <div className="flex items-start gap-3">
                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                    <h2 className="text-lg font-semibold break-words">{course.title}</h2>
                    <p className="text-sm text-muted">
                        {[
                            course.occupation,
                            course.durationWeeks !== null &&
                                t('courses.weeks', { count: formatNumber(course.durationWeeks) }),
                        ]
                            .filter(Boolean)
                            .join(' · ')}
                    </p>
                </div>
                {canManage && <CourseDialog occupations={occupations} course={course} />}
            </div>
            {course.description && (
                <p className="text-sm whitespace-pre-line text-muted">{course.description}</p>
            )}
            <h3 className="eyebrow text-muted">{t('nav.cohorts')}</h3>
            {course.cohorts.length === 0 ? (
                <p className="text-sm text-muted">{t('courses.no_cohorts')}</p>
            ) : (
                <ul className="flex flex-col divide-y divide-line">
                    {course.cohorts.map((cohort) => (
                        <li
                            key={cohort.id}
                            className="flex flex-wrap items-center justify-between gap-2 py-2.5"
                        >
                            <Link
                                href={org.cohorts.show(cohort.id)}
                                className="min-w-0 font-semibold text-brand hover:underline"
                            >
                                {cohort.name}
                                <span className="block text-xs font-normal text-muted">
                                    {cohort.startsOn && cohort.endsOn
                                        ? t('cohorts.dates', {
                                              start: formatDate(cohort.startsOn),
                                              end: formatDate(cohort.endsOn),
                                          })
                                        : t('cohorts.no_dates')}
                                </span>
                            </Link>
                            <span className="flex items-center gap-2 text-sm">
                                <span className="text-muted tnum">
                                    {t('cohorts.joined_count', {
                                        count: formatNumber(cohort.joined),
                                    })}
                                </span>
                                <StatusChip status={cohort.status} />
                            </span>
                        </li>
                    ))}
                </ul>
            )}
            {canManage && <CohortDialog course={course} />}
        </section>
    );
}

function CourseDialog({ occupations, course }: { occupations: Option[]; course?: Course }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        title: course?.title ?? '',
        occupation_id: course?.occupationId ?? '',
        duration_weeks: course?.durationWeeks?.toString() ?? '',
        description: course?.description ?? '',
    });
    const id = course ? `course-${course.id}` : 'course-new';

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (course) {
            form.put(org.courses.update.url(course.id), options);
        } else {
            form.post(org.courses.store.url(), {
                ...options,
                onSuccess: () => {
                    setOpen(false);
                    form.reset();
                },
            });
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {course ? (
                    <Button
                        variant="ghost"
                        className="min-h-11"
                        aria-label={t('courses.edit', { course: course.title })}
                    >
                        <Pencil aria-hidden className="size-4" />
                    </Button>
                ) : (
                    <Button className="min-h-11">
                        <Plus aria-hidden className="size-4" />
                        {t('courses.add')}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{course ? t('courses.edit_title') : t('courses.add')}</DialogTitle>
                    <DialogDescription>{t('courses.form_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-title`}>{t('courses.field.title')}</Label>
                        <Input
                            id={`${id}-title`}
                            className="h-11"
                            maxLength={160}
                            value={form.data.title}
                            onChange={(event) => form.setData('title', event.target.value)}
                        />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-trade`}>
                            {t('courses.field.trade')} ({t('common.optional')})
                        </Label>
                        <NativeSelect
                            id={`${id}-trade`}
                            value={form.data.occupation_id}
                            onChange={(event) => form.setData('occupation_id', event.target.value)}
                        >
                            <option value="">{t('common.choose')}</option>
                            {occupations.map((occupation) => (
                                <option key={occupation.id} value={occupation.id}>
                                    {occupation.name}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.occupation_id} />
                    </div>
                    <div className="flex flex-col gap-1.5 sm:max-w-40">
                        <Label htmlFor={`${id}-weeks`}>
                            {t('courses.field.weeks')} ({t('common.optional')})
                        </Label>
                        <Input
                            id={`${id}-weeks`}
                            type="number"
                            inputMode="numeric"
                            min={1}
                            max={520}
                            className="h-11"
                            value={form.data.duration_weeks}
                            onChange={(event) => form.setData('duration_weeks', event.target.value)}
                        />
                        <InputError message={form.errors.duration_weeks} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-description`}>
                            {t('courses.field.description')} ({t('common.optional')})
                        </Label>
                        <textarea
                            id={`${id}-description`}
                            rows={3}
                            maxLength={2000}
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            className={TEXTAREA}
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {t('common.save')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CohortDialog({ course }: { course: Course }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ name: '', starts_on: '', ends_on: '' });
    const id = `cohort-${course.id}`;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(org.cohorts.store.url(course.id), {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="outline" className="mt-auto min-h-11 self-start">
                    <GraduationCap aria-hidden className="size-4" />
                    {t('cohorts.add')}
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('cohorts.add_title', { course: course.title })}</DialogTitle>
                    <DialogDescription>{t('cohorts.add_hint')}</DialogDescription>
                </DialogHeader>
                <CohortFields form={form} id={id} onSubmit={submit} />
            </DialogContent>
        </Dialog>
    );
}
