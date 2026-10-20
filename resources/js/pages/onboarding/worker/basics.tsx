import { Head, setLayoutProps, useForm } from '@inertiajs/react';
import { Keyboard, Mic } from 'lucide-react';
import type { FormEvent } from 'react';
import BasicsController from '@/actions/App/Http/Controllers/Worker/BasicsController';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Props = {
    occupations: App.Domain.Taxonomy.Data.OccupationOptionData[];
    occupationId: string | null;
    specializationId: string | null;
    yearsExperience: number | null;
};

type Intent = 'voice' | 'type' | 'later';

/** Onboarding phase A: trade and experience, then voice or typing (spec §3). */
export default function WorkerBasics({
    occupations,
    occupationId,
    specializationId,
    yearsExperience,
}: Props) {
    const { t } = useTranslation();
    const form = useForm({
        occupation_id: occupationId ?? '',
        specialization_id: specializationId ?? '',
        years_experience: yearsExperience?.toString() ?? '',
        intent: 'voice' as Intent,
    });
    const selected = occupations.find((item) => item.id === form.data.occupation_id);

    setLayoutProps({
        title: t('worker.basics.title'),
        description: t('worker.basics.body'),
        wide: true,
    });

    const submit = (intent: Intent) => (event?: FormEvent) => {
        event?.preventDefault();
        form.transform((data) => ({ ...data, intent }));
        form.put(BasicsController.update.url());
    };

    return (
        <>
            <Head title={t('worker.basics.title')} />
            <form onSubmit={submit('voice')} className="flex flex-col gap-5" noValidate>
                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-2 text-[13px] font-semibold">
                        {t('worker.basics.trade')}
                    </legend>
                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        {occupations.map((occupation) => (
                            <label
                                key={occupation.id}
                                className={cn(
                                    'flex min-h-12 cursor-pointer items-center rounded-xl border px-3 py-2.5 text-[13.5px] font-semibold focus-within:ring-[3px] focus-within:ring-ring/50',
                                    form.data.occupation_id === occupation.id
                                        ? 'border-brand bg-brand-soft text-brand'
                                        : 'border-line hover:border-brand/60',
                                )}
                            >
                                <input
                                    type="radio"
                                    name="occupation_id"
                                    value={occupation.id}
                                    checked={form.data.occupation_id === occupation.id}
                                    onChange={() => {
                                        form.setData((data) => ({
                                            ...data,
                                            occupation_id: occupation.id,
                                            specialization_id: '',
                                        }));
                                    }}
                                    className="sr-only"
                                />
                                {occupation.name}
                            </label>
                        ))}
                    </div>
                    <InputError message={form.errors.occupation_id} />
                </fieldset>

                {selected && selected.specializations.length > 0 && (
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="specialization_id">
                            {t('worker.basics.specialization')}
                        </Label>
                        <NativeSelect
                            id="specialization_id"
                            value={form.data.specialization_id}
                            onChange={(event) =>
                                form.setData('specialization_id', event.target.value)
                            }
                        >
                            <option value="">{t('common.optional')}</option>
                            {selected.specializations.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.name}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.specialization_id} />
                    </div>
                )}

                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="years_experience">{t('worker.basics.years')}</Label>
                    <Input
                        id="years_experience"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        max={60}
                        value={form.data.years_experience}
                        onChange={(event) => form.setData('years_experience', event.target.value)}
                        className="h-11 max-w-40 text-base"
                        aria-invalid={!!form.errors.years_experience}
                    />
                    <InputError message={form.errors.years_experience} />
                </div>

                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {t('worker.basics.next_note')}
                </p>

                <div className="flex flex-col gap-2">
                    <Button
                        type="submit"
                        className="min-h-12 w-full text-base"
                        disabled={form.processing}
                    >
                        {form.processing ? <Spinner /> : <Mic aria-hidden className="size-4" />}
                        {t('worker.basics.by_voice')}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-12 w-full"
                        disabled={form.processing}
                        onClick={submit('type')}
                    >
                        <Keyboard aria-hidden className="size-4" />
                        {t('worker.basics.by_typing')}
                    </Button>
                    <button
                        type="button"
                        onClick={submit('later')}
                        disabled={form.processing}
                        className="min-h-11 text-[13px] font-semibold text-muted hover:text-ink"
                    >
                        {t('worker.basics.later')}
                    </button>
                </div>
            </form>
        </>
    );
}
