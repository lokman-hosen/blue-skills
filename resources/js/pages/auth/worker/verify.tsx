import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useEffect, useState } from 'react';
import PhoneSignInController from '@/actions/App/Http/Controllers/Worker/Auth/PhoneSignInController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import worker from '@/routes/worker';

type Props = {
    maskedPhone: string;
    codeLength: number;
    resendSeconds: number;
    status?: string | null;
};

/** Step 3: the WhatsApp code. */
export default function WorkerVerify({ maskedPhone, codeLength, resendSeconds, status }: Props) {
    const { t } = useTranslation();
    const [code, setCode] = useState('');
    const [wait, setWait] = useState(resendSeconds);

    setLayoutProps({
        title: t('auth.worker.verify_title', { length: codeLength }),
        description: t('auth.worker.verify_body', { phone: maskedPhone }),
    });

    useEffect(() => {
        if (wait <= 0) {
            return;
        }

        const timer = window.setTimeout(() => setWait((seconds) => seconds - 1), 1000);

        return () => window.clearTimeout(timer);
    }, [wait]);

    return (
        <>
            <Head title={t('auth.worker.verify_title', { length: codeLength })} />
            <div className="flex flex-col gap-5">
                {status && (
                    <p
                        role="status"
                        className="rounded-lg bg-ok-soft px-3 py-2 text-center text-sm font-medium text-ok"
                    >
                        {status}
                    </p>
                )}
                <Form
                    {...PhoneSignInController.confirmCode.form()}
                    resetOnError
                    className="flex flex-col items-center gap-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div dir="ltr">
                                <InputOTP
                                    name="code"
                                    maxLength={codeLength}
                                    value={code}
                                    onChange={setCode}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    inputMode="numeric"
                                    autoComplete="one-time-code"
                                    disabled={processing}
                                    autoFocus
                                    aria-label={t('auth.worker.verify_title', {
                                        length: codeLength,
                                    })}
                                >
                                    <InputOTPGroup>
                                        {Array.from({ length: codeLength }, (_, index) => (
                                            <InputOTPSlot
                                                key={index}
                                                index={index}
                                                className="size-11 text-lg"
                                            />
                                        ))}
                                    </InputOTPGroup>
                                </InputOTP>
                            </div>
                            <InputError message={errors.code} className="text-center" />
                            <Button
                                type="submit"
                                className="min-h-12 w-full text-base"
                                disabled={processing || code.length < codeLength}
                            >
                                {processing && <Spinner />}
                                {t('auth.otp.verify')}
                            </Button>
                        </>
                    )}
                </Form>
                <div className="flex flex-col items-center gap-2 text-sm">
                    <Form
                        {...PhoneSignInController.resend.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setWait(resendSeconds)}
                    >
                        {({ processing }) => (
                            <Button
                                type="submit"
                                variant="ghost"
                                className="min-h-11 text-brand"
                                disabled={processing || wait > 0}
                            >
                                {wait > 0
                                    ? t('auth.otp.resend_in', { seconds: wait })
                                    : t('auth.otp.resend')}
                            </Button>
                        )}
                    </Form>
                    <Link
                        href={worker.phone()}
                        className="inline-flex min-h-11 items-center font-semibold text-muted hover:text-ink"
                    >
                        {t('auth.otp.change_number')}
                    </Link>
                </div>
            </div>
        </>
    );
}
