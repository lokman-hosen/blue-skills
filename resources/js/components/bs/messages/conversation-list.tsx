import { Link } from '@inertiajs/react';
import { ChevronRight, MessageSquare } from 'lucide-react';
import { EmptyState } from '@/components/bs/empty-state';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type Item = App.Domain.Messaging.Data.ConversationListItemData;

/** Conversations, newest first, with unread counts. */
export function ConversationList({
    conversations,
    href,
    emptyBody,
}: {
    conversations: Item[];
    href: (id: string) => string;
    emptyBody: string;
}) {
    const { t, formatDate, formatNumber } = useTranslation();

    if (conversations.length === 0) {
        return (
            <EmptyState
                icon={MessageSquare}
                title={t('messages.empty_title')}
                description={emptyBody}
            />
        );
    }

    return (
        <ul className="flex flex-col divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface">
            {conversations.map((conversation) => (
                <li key={conversation.id}>
                    <Link
                        href={href(conversation.id)}
                        className="flex items-start gap-3 p-4 hover:bg-surface-2 focus-visible:bg-surface-2 focus-visible:outline-none"
                    >
                        <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                            <span className="flex items-baseline justify-between gap-2">
                                <span
                                    className={cn(
                                        'truncate',
                                        conversation.unread > 0 ? 'font-bold' : 'font-semibold',
                                    )}
                                >
                                    {conversation.counterpart}
                                </span>
                                {conversation.lastMessageAt && (
                                    <span className="shrink-0 text-xs text-muted">
                                        {formatDate(conversation.lastMessageAt, {
                                            day: 'numeric',
                                            month: 'short',
                                        })}
                                    </span>
                                )}
                            </span>
                            <span className="truncate text-xs text-muted">
                                {conversation.jobTitle} ·{' '}
                                <span dir="ltr">{conversation.jobCode}</span>
                            </span>
                            <span
                                className={cn(
                                    'truncate text-sm',
                                    conversation.unread > 0 ? 'text-ink' : 'text-muted',
                                )}
                            >
                                {conversation.preview ?? t('messages.no_messages')}
                            </span>
                        </span>
                        {conversation.unread > 0 ? (
                            <span className="mt-1 rounded-full bg-bad px-1.5 text-[11px] leading-5 font-bold text-on-brand">
                                <span aria-hidden>{formatNumber(conversation.unread)}</span>
                                <span className="sr-only">
                                    {t('nav.unread', { count: conversation.unread })}
                                </span>
                            </span>
                        ) : (
                            <ChevronRight
                                aria-hidden
                                className="mt-1 size-4 shrink-0 text-muted rtl:rotate-180"
                            />
                        )}
                    </Link>
                </li>
            ))}
        </ul>
    );
}
