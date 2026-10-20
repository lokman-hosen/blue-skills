import { Form, Head, setLayoutProps } from '@inertiajs/react';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const { t } = useTranslation();

    setLayoutProps({
        title: t('auth.verify_email.title'),
        description: t('auth.verify_email.body'),
    });

    return (
        <>
            <Head title={t('auth.verify_email.title')} />

            {status === 'verification-link-sent' && (
                <p
                    role="status"
                    className="mb-4 rounded-lg bg-ok-soft px-3 py-2 text-center text-sm font-medium text-ok"
                >
                    {t('auth.verify_email.sent')}
                </p>
            )}

            <Form {...send.form()} className="flex flex-col items-center gap-4 text-center">
                {({ processing }) => (
                    <>
                        <Button disabled={processing} variant="secondary" className="min-h-11">
                            {processing && <Spinner />}
                            {t('auth.verify_email.resend')}
                        </Button>
                        <TextLink
                            href={logout()}
                            className="inline-flex min-h-11 items-center text-sm"
                        >
                            {t('common.sign_out')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}
