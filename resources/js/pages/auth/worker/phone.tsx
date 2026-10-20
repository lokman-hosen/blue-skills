import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { MessageCircle } from 'lucide-react';
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

/** Step 2: mobile number. Workers never need a password (spec §1). */
export default function WorkerPhone({ countries }: Props) {
    const { t } = useTranslation();

    setLayoutProps({
        title: t('auth.worker.phone_title'),
        description: t('auth.worker.phone_body'),
    });

    return (
        <>
            <Head title={t('auth.worker.phone_title')} />
            <Form {...PhoneSignInController.sendCode.form()} className="flex flex-col gap-5">
                {({ errors, processing }) => (
                    <>
                        <div
                            className="grid grid-cols-[minmax(0,8.5rem)_minmax(0,1fr)] gap-2"
                            dir="ltr"
                        >
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="country">{t('auth.phone.country')}</Label>
                                <NativeSelect
                                    id="country"
                                    name="country"
                                    defaultValue={countries[0]?.code}
                                    aria-invalid={!!errors.country}
                                >
                                    {countries.map((country) => (
                                        <option key={country.code} value={country.code}>
                                            {country.dialCode} {country.code}
                                        </option>
                                    ))}
                                </NativeSelect>
                            </div>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor="phone">{t('auth.phone.number')}</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    inputMode="tel"
                                    autoComplete="tel-national"
                                    required
                                    autoFocus
                                    className="h-11 text-base"
                                    aria-invalid={!!errors.phone}
                                    aria-describedby={errors.phone ? 'phone-error' : undefined}
                                />
                            </div>
                        </div>
                        <InputError id="phone-error" message={errors.phone ?? errors.country} />
                        <Button
                            type="submit"
                            className="min-h-12 w-full text-base"
                            disabled={processing}
                        >
                            {processing ? <Spinner /> : <MessageCircle aria-hidden />}
                            {t('auth.phone.send')}
                        </Button>
                        <p className="text-center text-xs text-muted">{t('auth.otp.safety')}</p>
                    </>
                )}
            </Form>
        </>
    );
}
