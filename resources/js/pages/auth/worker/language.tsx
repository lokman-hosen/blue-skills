import { Head, Link, router, setLayoutProps, usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import locale from '@/routes/locale';
import worker from '@/routes/worker';

/** Step 1: language first (deck slide 5). */
export default function WorkerLanguage() {
    const { t, locale: current } = useTranslation();
    const { locales } = usePage().props;

    setLayoutProps({ title: t('language.choose'), description: t('auth.worker.language_body') });

    const choose = (code: string) => {
        router.post(
            locale.update.url(),
            { locale: code },
            { onSuccess: () => router.visit(worker.phone()) },
        );
    };

    return (
        <>
            <Head title={t('language.choose')} />
            <ul className="flex flex-col gap-2">
                {locales.map((option) => (
                    <li key={option.code}>
                        <button
                            type="button"
                            onClick={() => choose(option.code)}
                            className={cn(
                                'flex min-h-14 w-full items-center gap-3 rounded-xl border border-line px-4 text-start transition hover:border-brand hover:bg-brand-soft',
                                option.code === current && 'border-brand bg-brand-soft',
                            )}
                        >
                            <span
                                lang={option.code}
                                dir={option.rtl ? 'rtl' : 'ltr'}
                                className="text-lg font-semibold"
                            >
                                {option.nativeName}
                            </span>
                            <span className="text-sm text-muted">{option.englishName}</span>
                            {option.code === current && (
                                <Check aria-hidden className="ms-auto size-5 text-brand" />
                            )}
                        </button>
                    </li>
                ))}
            </ul>
            <p className="mt-6 text-center text-sm text-muted">
                <Link href={login()} className="font-semibold text-brand hover:underline">
                    {t('auth.worker.org_link')}
                </Link>
            </p>
        </>
    );
}
