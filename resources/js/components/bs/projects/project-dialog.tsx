import { useForm } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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

type Summary = App.Domain.Jobs.Data.RecruitmentProjectListItemData;

export type ProjectFormOptions = {
    jobOptions: { id: string; label: string; projectId: string | null }[];
    memberOptions: { id: string; name: string }[];
    countries: { code: string; name: string }[];
    selected: { jobIds: string[]; memberIds: string[] };
};

/** Create or edit a recruitment project: target, countries, jobs and team (spec §6). */
export function ProjectDialog({
    project,
    jobOptions,
    memberOptions,
    countries,
    selected,
}: ProjectFormOptions & { project?: Summary }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: project?.name ?? '',
        target_headcount: project ? String(project.targetHeadcount) : '',
        destination_country_code: project?.destinationCountryCode ?? 'AE',
        source_country_codes: project?.sourceCountryCodes ?? [],
        target_date: project?.targetDate ?? '',
        job_ids: selected.jobIds,
        member_ids: selected.memberIds,
    });

    const toggle = (
        key: 'source_country_codes' | 'job_ids' | 'member_ids',
        id: string,
        on: boolean,
    ) =>
        form.setData(
            key,
            on ? [...form.data[key], id] : form.data[key].filter((value) => value !== id),
        );

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (project) {
            form.put(org.projects.update.url(project.id), options);
        } else {
            form.post(org.projects.store.url(), options);
        }
    };

    const checkList = (
        key: 'source_country_codes' | 'job_ids' | 'member_ids',
        items: { id: string; label: string }[],
        empty: string,
    ) =>
        items.length === 0 ? (
            <p className="text-sm text-muted">{empty}</p>
        ) : (
            <ul className="flex max-h-48 flex-col overflow-y-auto rounded-md border border-line">
                {items.map((item) => (
                    <li key={item.id} className="flex min-h-11 items-center gap-2 px-3">
                        <Checkbox
                            id={`${key}-${item.id}`}
                            checked={form.data[key].includes(item.id)}
                            onCheckedChange={(checked) => toggle(key, item.id, checked === true)}
                        />
                        <Label htmlFor={`${key}-${item.id}`} className="font-normal">
                            {item.label}
                        </Label>
                    </li>
                ))}
            </ul>
        );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {project ? (
                    <Button variant="outline" className="min-h-11">
                        <Pencil aria-hidden className="size-4" />
                        {t('projects.edit')}
                    </Button>
                ) : (
                    <Button className="min-h-11">
                        <Plus aria-hidden className="size-4" />
                        {t('projects.new')}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{project ? t('projects.edit') : t('projects.new')}</DialogTitle>
                    <DialogDescription>{t('projects.new_hint')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="project-name">{t('projects.field.name')}</Label>
                        <Input
                            id="project-name"
                            className="h-11"
                            placeholder={t('projects.field.name_example')}
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            aria-invalid={!!form.errors.name}
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="project-target">{t('projects.field.target')}</Label>
                            <Input
                                id="project-target"
                                type="number"
                                inputMode="numeric"
                                min={1}
                                className="h-11"
                                value={form.data.target_headcount}
                                onChange={(event) =>
                                    form.setData('target_headcount', event.target.value)
                                }
                                aria-invalid={!!form.errors.target_headcount}
                            />
                            <InputError message={form.errors.target_headcount} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="project-date">{t('projects.field.target_date')}</Label>
                            <Input
                                id="project-date"
                                type="date"
                                className="h-11"
                                value={form.data.target_date}
                                onChange={(event) =>
                                    form.setData('target_date', event.target.value)
                                }
                                aria-invalid={!!form.errors.target_date}
                            />
                            <InputError message={form.errors.target_date} />
                        </div>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="project-destination">
                            {t('projects.field.destination')}
                        </Label>
                        <NativeSelect
                            id="project-destination"
                            value={form.data.destination_country_code}
                            onChange={(event) =>
                                form.setData('destination_country_code', event.target.value)
                            }
                        >
                            {countries.map((country) => (
                                <option key={country.code} value={country.code}>
                                    {country.name}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                    <fieldset className="flex flex-col gap-1.5">
                        <legend className="mb-1.5 text-sm font-medium">
                            {t('projects.field.sources')}
                        </legend>
                        {checkList(
                            'source_country_codes',
                            countries.map((country) => ({ id: country.code, label: country.name })),
                            '',
                        )}
                        <InputError message={form.errors.source_country_codes} />
                    </fieldset>
                    <fieldset className="flex flex-col gap-1.5">
                        <legend className="mb-1.5 text-sm font-medium">
                            {t('projects.field.jobs')}
                        </legend>
                        {checkList('job_ids', jobOptions, t('projects.field.no_jobs'))}
                        <InputError message={form.errors.job_ids} />
                    </fieldset>
                    <fieldset className="flex flex-col gap-1.5">
                        <legend className="mb-1.5 text-sm font-medium">
                            {t('projects.field.team')}
                        </legend>
                        {checkList(
                            'member_ids',
                            memberOptions.map((member) => ({ id: member.id, label: member.name })),
                            '',
                        )}
                    </fieldset>
                    <Button
                        type="submit"
                        className="min-h-11 self-start"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {project ? t('common.save') : t('projects.create')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
