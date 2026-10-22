import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { ArrowLeft, Lock, SearchX, ServerCrash, Timer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { dashboard, home } from '@/routes';

type Props = {
    status: 403 | 404 | 429 | 500 | 503;
};

const ICONS: Record<Props['status'], LucideIcon> = {
    403: Lock,
    404: SearchX,
    429: Timer,
    500: ServerCrash,
    503: ServerCrash,
};

/** Error page in the site's design (D-107): what went wrong and what to do next. */
export default function ErrorPage({ status }: Props) {
    const { t } = useTranslation();
    const { auth } = usePage().props;
    const Icon = ICONS[status] ?? ServerCrash;

    return (
        <>
            <Head title={t(`errors.${status}.title`)} />
            <div className="mx-auto flex w-full max-w-md flex-col items-center gap-4 px-4 py-16 text-center">
                <span className="grid size-14 place-items-center rounded-2xl bg-brand-soft text-brand">
                    <Icon aria-hidden className="size-7" />
                </span>
                <p className="eyebrow text-muted">{t('errors.code', { code: String(status) })}</p>
                <h1 className="font-display text-2xl font-bold">{t(`errors.${status}.title`)}</h1>
                <p className="text-muted">{t(`errors.${status}.body`)}</p>
                <div className="mt-2 flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                    <Button asChild className="min-h-11">
                        <Link href={auth.user ? dashboard() : home()}>
                            {t(auth.user ? 'errors.go_dashboard' : 'errors.go_home')}
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11"
                        onClick={() => window.history.back()}
                    >
                        <ArrowLeft aria-hidden className="size-4 rtl:rotate-180" />
                        {t('errors.go_back')}
                    </Button>
                </div>
            </div>
        </>
    );
}
