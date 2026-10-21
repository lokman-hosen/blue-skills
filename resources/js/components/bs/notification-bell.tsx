import { Link, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import notifications from '@/routes/notifications';

/** Opens the notifications page; shows the unread count, kept live by LiveUpdates. */
export function NotificationBell({ className }: { className?: string }) {
    const { t, formatNumber } = useTranslation();
    const { unread } = usePage().props;
    const count = unread?.notifications ?? 0;

    return (
        <Link
            href={notifications.index()}
            aria-label={
                count > 0
                    ? `${t('nav.notifications')} · ${t('nav.unread', { count })}`
                    : t('nav.notifications')
            }
            className={cn(
                'relative grid size-11 shrink-0 place-items-center rounded-lg text-muted hover:bg-surface-2 hover:text-ink md:size-9',
                className,
            )}
        >
            <Bell aria-hidden className="size-5" />
            {count > 0 && (
                <span
                    aria-hidden
                    className="absolute -end-0.5 -top-0.5 min-w-4.5 rounded-full bg-bad px-1 text-center text-[10px] leading-4.5 font-bold text-on-brand"
                >
                    {count > 99 ? '99+' : formatNumber(count)}
                </span>
            )}
        </Link>
    );
}
