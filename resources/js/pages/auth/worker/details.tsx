import { Form, Head, setLayoutProps } from '@inertiajs/react';
import PhoneSignInController from '@/actions/App/Http/Controllers/Worker/Auth/PhoneSignInController';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

type Props = {
    countries: App.Domain.Config.Data.CountryOptionData[];
};

/** Step 4: minimal identity. Everything else is added later, progressively (spec §3). */
export default function WorkerDetails({ countries }: Props) {
    const { t } = useTranslation();

    setLayoutProps({
        title: t('auth.worker.details_title'),
        description: t('auth.worker.details_body'),
    });

    return (
        <>
            <Head title={t('auth.worker.details_title')} />
            <Form {...PhoneSignInController.register.form()} className="flex flex-col gap-5">
                {({ errors, processing }) => (
                    <>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="name">{t('auth.worker.name')}</Label>
                            <Input
                                id="name"
                                name="name"
                                autoComplete="name"
                                required
                                autoFocus
                                className="h-11 text-base"
                                aria-invalid={!!errors.name}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="country">{t('auth.worker.country')}</Label>
                            <NativeSelect
                                id="country"
                                name="country"
                                required
                                defaultValue=""
                                aria-invalid={!!errors.country}
                            >
                                <option value="" disabled>
                                    {t('auth.worker.country_placeholder')}
                                </option>
                                {countries.map((country) => (
                                    <option key={country.code} value={country.code}>
                                        {country.name}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.country} />
                        </div>
                        <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                            {t('auth.worker.privacy_note')}
                        </p>
                        <Button
                            type="submit"
                            className="min-h-12 w-full text-base"
                            disabled={processing}
                        >
                            {processing && <Spinner />}
                            {t('auth.worker.create')}
                        </Button>
                    </>
                )}
            </Form>
        </>
    );
}
