import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { PageHeader } from '@/components/bs/page-header';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import notificationRoutes from '@/routes/notifications';

type Row = App.Domain.Messaging.Data.PreferenceRowData;

type Props = {
    rows: Row[];
    quietHours: { from: number; until: number } | null;
};

/** Which alerts also come by WhatsApp, SMS or email. In-app notifications are always on. */
export default function NotificationPreferences({ rows, quietHours }: Props) {
    const { t } = useTranslation();
    const form = useForm<{ preferences: Record<string, Record<string, boolean>> }>({
        preferences: Object.fromEntries(rows.map((row) => [row.event, { ...row.channels }])),
    });

    const toggle = (event: string, channel: string, on: boolean) =>
        form.setData('preferences', {
            ...form.data.preferences,
            [event]: { ...form.data.preferences[event], [channel]: on },
        });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.put(notificationRoutes.preferences.update.url(), { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('notifications.preferences')} />
            <form onSubmit={submit} className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader
                    title={t('notifications.preferences')}
                    description={t('notifications.preferences_body')}
                    back={{ href: notificationRoutes.index(), label: t('nav.notifications') }}
                />
                <ul className="flex flex-col divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
                    {rows.map((row) => (
                        <li
                            key={row.event}
                            className="flex flex-col gap-2 p-4 sm:flex-row sm:items-center"
                        >
                            <span className="flex-1 text-sm font-semibold">
                                {t(`notification_event.${row.event}`)}
                            </span>
                            <span className="flex flex-wrap gap-x-4">
                                {Object.keys(row.channels).map((channel) => (
                                    <label
                                        key={channel}
                                        className="flex min-h-11 cursor-pointer items-center gap-2 text-sm"
                                    >
                                        <input
                                            type="checkbox"
                                            checked={
                                                form.data.preferences[row.event]?.[channel] ?? false
                                            }
                                            onChange={(event) =>
                                                toggle(row.event, channel, event.target.checked)
                                            }
                                            className="size-5 accent-[var(--color-brand)]"
                                        />
                                        {t(`delivery_channel.${channel}`)}
                                    </label>
                                ))}
                            </span>
                        </li>
                    ))}
                </ul>
                <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                    {quietHours
                        ? t('notifications.worker_note', {
                              from: `${quietHours.from}:00`,
                              until: `${String(quietHours.until).padStart(2, '0')}:00`,
                          })
                        : t('notifications.org_note')}
                </p>
                <Button
                    type="submit"
                    className="min-h-11 self-start"
                    disabled={form.processing || !form.isDirty}
                >
                    {form.processing && <Spinner />}
                    {t('common.save')}
                </Button>
            </form>
        </>
    );
}
