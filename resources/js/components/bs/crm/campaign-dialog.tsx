import { useForm } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
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
import admin from '@/routes/admin';

type Campaign = App.Domain.Crm.Data.CampaignData;

export type CampaignFormOptions = {
    channels: App.Domain.Crm.Enums.CampaignChannel[];
    partners: { id: string; name: string }[];
    countries: string[];
};

/** Create or edit a campaign. The code is set once from the name and never changes. */
export function CampaignDialog({
    campaign,
    channels,
    partners,
    countries,
}: CampaignFormOptions & { campaign?: Campaign }) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({
        name: campaign?.name ?? '',
        channel: (campaign?.channel ?? 'job_fair') as string,
        source_country_code: campaign?.sourceCountryCode ?? countries[0] ?? '',
        partner_organization_id: campaign?.partnerId ?? '',
        landing: (campaign?.landing ?? 'join') as string,
        starts_on: campaign?.startsOn ?? new Date().toISOString().slice(0, 10),
        ends_on: campaign?.endsOn ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (campaign) {
            form.put(admin.marketing.update.url(campaign.id), options);
        } else {
            form.post(admin.marketing.store.url(), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {campaign ? (
                    <Button variant="outline" className="min-h-11">
                        <Pencil aria-hidden className="size-4" />
                        {t('marketing.edit')}
                    </Button>
                ) : (
                    <Button className="min-h-11">
                        <Plus aria-hidden className="size-4" />
                        {t('marketing.new')}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{campaign ? t('marketing.edit') : t('marketing.new')}</DialogTitle>
                    <DialogDescription>
                        {campaign
                            ? t('marketing.code_fixed', { code: campaign.code })
                            : t('marketing.new_hint')}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="campaign-name">{t('marketing.field.name')}</Label>
                        <Input
                            id="campaign-name"
                            className="h-11"
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            aria-invalid={!!form.errors.name}
                        />
                        <InputError message={form.errors.name} />
                    </div>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="campaign-channel">{t('marketing.field.channel')}</Label>
                            <NativeSelect
                                id="campaign-channel"
                                value={form.data.channel}
                                onChange={(event) => form.setData('channel', event.target.value)}
                            >
                                {channels.map((value) => (
                                    <option key={value} value={value}>
                                        {t(`campaign_channel.${value}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="campaign-country">{t('marketing.field.country')}</Label>
                            <NativeSelect
                                id="campaign-country"
                                value={form.data.source_country_code}
                                onChange={(event) =>
                                    form.setData('source_country_code', event.target.value)
                                }
                            >
                                <option value="">{t('marketing.field.any_country')}</option>
                                {countries.map((code) => (
                                    <option key={code} value={code}>
                                        {code}
                                    </option>
                                ))}
                            </NativeSelect>
                        </div>
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="campaign-partner">{t('marketing.field.partner')}</Label>
                        <NativeSelect
                            id="campaign-partner"
                            value={form.data.partner_organization_id}
                            onChange={(event) =>
                                form.setData('partner_organization_id', event.target.value)
                            }
                            aria-invalid={!!form.errors.partner_organization_id}
                        >
                            <option value="">{t('marketing.field.no_partner')}</option>
                            {partners.map((partner) => (
                                <option key={partner.id} value={partner.id}>
                                    {partner.name}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.partner_organization_id} />
                    </div>
                    <fieldset className="flex flex-col gap-1.5">
                        <legend className="mb-1.5 text-sm font-medium">
                            {t('marketing.field.landing')}
                        </legend>
                        {(['join', 'jobs'] as const).map((value) => (
                            <label
                                key={value}
                                className="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    type="radio"
                                    name="landing"
                                    value={value}
                                    checked={form.data.landing === value}
                                    onChange={() => form.setData('landing', value)}
                                    className="size-4 accent-[var(--color-brand)]"
                                />
                                {t(`campaign_landing.${value}`)}
                            </label>
                        ))}
                    </fieldset>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="campaign-start">{t('marketing.field.starts_on')}</Label>
                            <Input
                                id="campaign-start"
                                type="date"
                                className="h-11"
                                value={form.data.starts_on}
                                onChange={(event) => form.setData('starts_on', event.target.value)}
                                aria-invalid={!!form.errors.starts_on}
                            />
                            <InputError message={form.errors.starts_on} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="campaign-end">{t('marketing.field.ends_on')}</Label>
                            <Input
                                id="campaign-end"
                                type="date"
                                className="h-11"
                                value={form.data.ends_on}
                                min={form.data.starts_on}
                                onChange={(event) => form.setData('ends_on', event.target.value)}
                                aria-invalid={!!form.errors.ends_on}
                            />
                            <InputError message={form.errors.ends_on} />
                        </div>
                    </div>
                    <Button
                        type="submit"
                        className="min-h-11 self-start"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {campaign ? t('common.save') : t('marketing.create')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
