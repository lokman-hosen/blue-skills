import type { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

export type CohortForm = { name: string; starts_on: string; ends_on: string };

/** Name and dates of a cohort, for adding or editing one. */
export function CohortFields({
    form,
    id,
    onSubmit,
}: {
    form: ReturnType<typeof useForm<CohortForm>>;
    id: string;
    onSubmit: (event: FormEvent) => void;
}) {
    const { t } = useTranslation();

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-3" noValidate>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor={`${id}-name`}>{t('cohorts.field.name')}</Label>
                <Input
                    id={`${id}-name`}
                    className="h-11"
                    maxLength={120}
                    placeholder={t('cohorts.field.name_example')}
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                />
                <InputError message={form.errors.name} />
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor={`${id}-start`}>
                        {t('cohorts.field.starts_on')} ({t('common.optional')})
                    </Label>
                    <Input
                        id={`${id}-start`}
                        type="date"
                        className="h-11"
                        value={form.data.starts_on}
                        onChange={(event) => form.setData('starts_on', event.target.value)}
                    />
                    <InputError message={form.errors.starts_on} />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor={`${id}-end`}>
                        {t('cohorts.field.ends_on')} ({t('common.optional')})
                    </Label>
                    <Input
                        id={`${id}-end`}
                        type="date"
                        className="h-11"
                        value={form.data.ends_on}
                        onChange={(event) => form.setData('ends_on', event.target.value)}
                    />
                    <InputError message={form.errors.ends_on} />
                </div>
            </div>
            <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                {form.processing && <Spinner />}
                {t('common.save')}
            </Button>
        </form>
    );
}
