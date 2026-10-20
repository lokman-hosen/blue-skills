import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, login } from '@/routes';

/** Minimal landing until the public marketplace ships in Module 3. */
export default function Home() {
    const { t } = useTranslation();
    const { auth } = usePage().props;

    return (
        <>
            <Head title={t('app.tagline')} />
            <section className="flex flex-col items-start gap-5 py-10 sm:py-16">
                <span className="stamp-mark">BLUESKILLS.AE</span>
                <h1 className="max-w-3xl font-display text-4xl leading-tight font-extrabold sm:text-5xl">
                    {t('public.hero.title')}
                </h1>
                <p className="max-w-2xl text-lg text-muted">{t('public.hero.body')}</p>
                <Button asChild size="lg" className="min-h-12">
                    <Link href={auth.user ? dashboard() : login()}>
                        {auth.user ? t('nav.dashboard') : t('common.sign_in')}
                    </Link>
                </Button>
            </section>
        </>
    );
}
