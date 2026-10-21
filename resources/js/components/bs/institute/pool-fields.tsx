import type { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

export type PoolForm = { name: string; description: string; occupation_id: string };

/** Name, trade and description of a talent pool. */
export function PoolFields({
    form,
    occupations,
    id,
    onSubmit,
}: {
    form: ReturnType<typeof useForm<PoolForm>>;
    occupations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
    id: string;
    onSubmit: (event: FormEvent) => void;
}) {
    const { t } = useTranslation();

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-3" noValidate>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor={`${id}-name`}>{t('pools.field.name')}</Label>
                <Input
                    id={`${id}-name`}
                    className="h-11"
                    maxLength={120}
                    value={form.data.name}
                    onChange={(event) => form.setData('name', event.target.value)}
                />
                <InputError message={form.errors.name} />
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
            <div className="flex flex-col gap-1.5">
                <Label htmlFor={`${id}-description`}>
                    {t('pools.field.description')} ({t('common.optional')})
                </Label>
                <Input
                    id={`${id}-description`}
                    className="h-11"
                    maxLength={500}
                    value={form.data.description}
                    onChange={(event) => form.setData('description', event.target.value)}
                />
                <InputError message={form.errors.description} />
            </div>
            <Button type="submit" className="min-h-11 self-end" disabled={form.processing}>
                {form.processing && <Spinner />}
                {t('common.save')}
            </Button>
        </form>
    );
}
