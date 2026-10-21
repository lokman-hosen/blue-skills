import { Head, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import type { FormEvent, ReactNode } from 'react';
import { PageHeader } from '@/components/bs/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Stage = { key: string; label: string; done_on_open: boolean };

type Rules = {
    agency_eligibility: string;
    worker_cost_policy: string;
    local_notice: string;
    languages: string[];
    allowed_worker_cost_categories: string[];
    mandatory_disclosures: string[];
    document_types: string[];
    mobility_stage_template: Stage[];
};

type Props = {
    country: App.Domain.Config.Data.CountryConfigData;
    options: {
        languages: { code: string; name: string }[];
        costCategories: string[];
        disclosures: string[];
        documentTypes: string[];
    };
};

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/** One country: its settings and corridor rules (prototype x-config, D-093). */
export default function ConfigCountry({ country, options }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={country.name} />
            <div className="flex flex-col gap-5">
                <PageHeader
                    eyebrow={`${country.code} · ${country.currency}`}
                    title={country.name}
                    description={t('config.country_body')}
                    back={{ href: admin.config.index(), label: t('nav.configuration') }}
                />
                <div className="grid gap-5 lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)]">
                    <Settings country={country} />
                    <RulesForm country={country} options={options} />
                </div>
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

function Settings({ country }: { country: App.Domain.Config.Data.CountryConfigData }) {
    const { t, formatDate } = useTranslation();
    const form = useForm({
        currency: country.currency,
        enabled_as_source: country.source,
        enabled_as_destination: country.destination,
        legal_reviewed: country.legalReviewedAt !== null,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(admin.config.countries.update.url(country.code), { preserveScroll: true });
    };

    const box = (
        field: 'enabled_as_source' | 'enabled_as_destination' | 'legal_reviewed',
        label: string,
    ) => (
        <div className="flex min-h-11 items-start gap-2">
            <Checkbox
                id={field}
                checked={form.data[field]}
                onCheckedChange={(checked) => form.setData(field, checked === true)}
                className="mt-0.5"
            />
            <Label htmlFor={field} className="font-normal">
                {label}
            </Label>
        </div>
    );

    return (
        <Panel title={t('config.settings')}>
            <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                <div className="flex flex-col gap-1.5 sm:max-w-32">
                    <Label htmlFor="currency">{t('config.currency')}</Label>
                    <Input
                        id="currency"
                        maxLength={3}
                        className="h-11 font-mono uppercase"
                        value={form.data.currency}
                        onChange={(event) => form.setData('currency', event.target.value)}
                    />
                    <InputError message={form.errors.currency} />
                </div>
                {box('enabled_as_source', t('config.source_hint'))}
                {box('enabled_as_destination', t('config.destination_hint'))}
                {box('legal_reviewed', t('config.legal_hint'))}
                {country.legalReviewedAt && (
                    <p className="text-xs text-muted">
                        {t('config.legal_by', {
                            name: country.legalReviewedBy ?? '—',
                            date: formatDate(country.legalReviewedAt),
                        })}
                    </p>
                )}
                <Button
                    type="submit"
                    variant="outline"
                    className="min-h-11 self-start"
                    disabled={form.processing}
                >
                    {form.processing && <Spinner />}
                    {t('common.save')}
                </Button>
            </form>
        </Panel>
    );
}

function RulesForm({ country, options }: Props) {
    const { t } = useTranslation();
    const form = useForm<Rules>(country.rules as Rules);
    const stages = form.data.mobility_stage_template;
    const errors = form.errors as Record<string, string>;

    const toggle = (
        field:
            | 'languages'
            | 'allowed_worker_cost_categories'
            | 'mandatory_disclosures'
            | 'document_types',
        value: string,
        on: boolean,
    ) =>
        form.setData(
            field,
            on ? [...form.data[field], value] : form.data[field].filter((item) => item !== value),
        );

    const setStages = (next: Stage[]) => form.setData('mobility_stage_template', next);
    const move = (index: number, by: number) => {
        const next = [...stages];
        const [stage] = next.splice(index, 1);
        next.splice(index + by, 0, stage);
        setStages(next);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(admin.config.countries.rules.url(country.code), { preserveScroll: true });
    };

    const checkboxes = (
        field:
            | 'languages'
            | 'allowed_worker_cost_categories'
            | 'mandatory_disclosures'
            | 'document_types',
        items: { value: string; label: string }[],
    ) => (
        <fieldset className="flex flex-col gap-1.5">
            <legend className="mb-1 text-sm font-medium">{t(`config.rule.${field}`)}</legend>
            <div className="grid gap-x-4 sm:grid-cols-2">
                {items.map((item) => (
                    <div key={item.value} className="flex min-h-11 items-center gap-2">
                        <Checkbox
                            id={`${field}-${item.value}`}
                            checked={form.data[field].includes(item.value)}
                            onCheckedChange={(checked) =>
                                toggle(field, item.value, checked === true)
                            }
                        />
                        <Label htmlFor={`${field}-${item.value}`} className="font-normal">
                            {item.label}
                        </Label>
                    </div>
                ))}
            </div>
            <InputError message={errors[field]} />
        </fieldset>
    );

    return (
        <Panel title={t('config.rules')}>
            <form onSubmit={submit} className="flex flex-col gap-5" noValidate>
                {(['agency_eligibility', 'worker_cost_policy', 'local_notice'] as const).map(
                    (field) => (
                        <div key={field} className="flex flex-col gap-1.5">
                            <Label htmlFor={field}>{t(`config.rule.${field}`)}</Label>
                            <textarea
                                id={field}
                                rows={2}
                                maxLength={field === 'local_notice' ? 1000 : 500}
                                value={form.data[field]}
                                onChange={(event) => form.setData(field, event.target.value)}
                                className={TEXTAREA}
                            />
                            <InputError message={errors[field]} />
                        </div>
                    ),
                )}
                {checkboxes(
                    'languages',
                    options.languages.map((language) => ({
                        value: language.code,
                        label: language.name,
                    })),
                )}
                {checkboxes(
                    'allowed_worker_cost_categories',
                    options.costCategories.map((value) => ({
                        value,
                        label: t(`truth_card.cost.${value}`),
                    })),
                )}
                {checkboxes(
                    'mandatory_disclosures',
                    options.disclosures.map((value) => ({
                        value,
                        label: t(`config.disclosure.${value}`),
                    })),
                )}
                {checkboxes(
                    'document_types',
                    options.documentTypes.map((value) => ({
                        value,
                        label: t(`mobility.document.type.${value}`),
                    })),
                )}

                <fieldset className="flex flex-col gap-2">
                    <legend className="mb-1 text-sm font-medium">
                        {t('config.rule.mobility_stage_template')}
                    </legend>
                    <p className="text-xs text-muted">{t('config.stages_hint')}</p>
                    <ol className="flex flex-col gap-2">
                        {stages.map((stage, index) => (
                            <li
                                key={index}
                                className="flex flex-wrap items-center gap-2 rounded-lg border border-line p-2"
                            >
                                <span className="w-6 text-center text-sm text-muted tnum">
                                    {index + 1}
                                </span>
                                <Label htmlFor={`stage-${index}`} className="sr-only">
                                    {t('config.stage_label', { number: index + 1 })}
                                </Label>
                                <Input
                                    id={`stage-${index}`}
                                    className="h-11 min-w-40 flex-1"
                                    maxLength={80}
                                    value={stage.label}
                                    onChange={(event) =>
                                        setStages(
                                            stages.map((item, i) =>
                                                i === index
                                                    ? { ...item, label: event.target.value }
                                                    : item,
                                            ),
                                        )
                                    }
                                />
                                <div className="flex min-h-11 items-center gap-1.5">
                                    <Checkbox
                                        id={`stage-open-${index}`}
                                        checked={stage.done_on_open}
                                        onCheckedChange={(checked) =>
                                            setStages(
                                                stages.map((item, i) =>
                                                    i === index
                                                        ? {
                                                              ...item,
                                                              done_on_open: checked === true,
                                                          }
                                                        : item,
                                                ),
                                            )
                                        }
                                    />
                                    <Label
                                        htmlFor={`stage-open-${index}`}
                                        className="text-xs font-normal"
                                    >
                                        {t('config.done_on_open')}
                                    </Label>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="size-11"
                                    disabled={index === 0}
                                    onClick={() => move(index, -1)}
                                    aria-label={t('config.move_up')}
                                >
                                    <ArrowUp aria-hidden className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="size-11"
                                    disabled={index === stages.length - 1}
                                    onClick={() => move(index, 1)}
                                    aria-label={t('config.move_down')}
                                >
                                    <ArrowDown aria-hidden className="size-4" />
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="size-11 text-bad"
                                    onClick={() => setStages(stages.filter((_, i) => i !== index))}
                                    aria-label={t('config.remove_stage')}
                                >
                                    <Trash2 aria-hidden className="size-4" />
                                </Button>
                                <InputError
                                    message={errors[`mobility_stage_template.${index}.label`]}
                                />
                            </li>
                        ))}
                    </ol>
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11 self-start"
                        onClick={() =>
                            setStages([...stages, { key: '', label: '', done_on_open: false }])
                        }
                    >
                        <Plus aria-hidden className="size-4" />
                        {t('config.add_stage')}
                    </Button>
                </fieldset>

                <Button type="submit" className="min-h-11 self-start" disabled={form.processing}>
                    {form.processing && <Spinner />}
                    {t('config.save_rules')}
                </Button>
            </form>
        </Panel>
    );
}
