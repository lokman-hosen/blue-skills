import { Head, Link, router } from '@inertiajs/react';
import { Bell, Settings } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import type { Paginated } from '@/components/bs/jobs/types';
import { PageHeader } from '@/components/bs/page-header';
import { Pager } from '@/components/bs/pager';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import notificationRoutes from '@/routes/notifications';

type Props = {
    notifications: Paginated<App.Domain.Messaging.Data.NotificationData>;
};

/** Every alert, newest first. Opening one marks it read and follows its link. */
export default function Notifications({ notifications }: Props) {
    const { t, formatDate } = useTranslation();
    const hasUnread = notifications.data.some((notification) => !notification.read);

    return (
        <>
            <Head title={t('nav.notifications')} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    title={t('nav.notifications')}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="ghost" className="min-h-11">
                                <Link href={notificationRoutes.preferences()}>
                                    <Settings aria-hidden className="size-4" />
                                    {t('notifications.preferences')}
                                </Link>
                            </Button>
                            {hasUnread && (
                                <Button
                                    variant="outline"
                                    className="min-h-11"
                                    onClick={() =>
                                        router.post(
                                            notificationRoutes.readAll.url(),
                                            {},
                                            { preserveScroll: true },
                                        )
                                    }
                                >
                                    {t('notifications.read_all')}
                                </Button>
                            )}
                        </div>
                    }
                />
                {notifications.data.length === 0 ? (
                    <EmptyState
                        icon={Bell}
                        title={t('notifications.empty_title')}
                        description={t('notifications.empty_body')}
                    />
                ) : (
                    <ul className="flex flex-col divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                        {notifications.data.map((notification) => (
                            <li key={notification.id}>
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(notificationRoutes.open.url(notification.id))
                                    }
                                    className={cn(
                                        'flex w-full items-start gap-3 p-4 text-start hover:bg-surface-2 focus-visible:bg-surface-2 focus-visible:outline-none',
                                        !notification.read && 'bg-brand-soft/40',
                                    )}
                                >
                                    <span
                                        aria-hidden
                                        className={cn(
                                            'mt-1.5 size-2.5 shrink-0 rounded-full',
                                            notification.read ? 'bg-transparent' : 'bg-brand',
                                        )}
                                    />
                                    <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                        <span
                                            className={cn(
                                                'text-sm',
                                                !notification.read && 'font-semibold',
                                            )}
                                        >
                                            {notification.title}
                                            {!notification.read && (
                                                <span className="sr-only">
                                                    {' '}
                                                    · {t('notifications.unread')}
                                                </span>
                                            )}
                                        </span>
                                        <span className="text-[13px] break-words text-muted">
                                            {notification.body}
                                        </span>
                                        <span className="text-xs text-muted">
                                            {formatDate(notification.createdAt, {
                                                dateStyle: 'medium',
                                                timeStyle: 'short',
                                            })}
                                        </span>
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                )}
                <Pager page={notifications} />
            </div>
        </>
    );
}
