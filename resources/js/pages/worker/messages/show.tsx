import { Head, Link } from '@inertiajs/react';
import { ConversationList } from '@/components/bs/messages/conversation-list';
import { ConversationThread } from '@/components/bs/messages/conversation-thread';
import { MessagesSplit } from '@/components/bs/messages/messages-split';
import { PageContainer } from '@/components/bs/page-container';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import worker from '@/routes/worker';

type Props = {
    conversation: App.Domain.Messaging.Data.ConversationData;
    /** Shown beside the thread from `lg` (D-114). */
    conversations: App.Domain.Messaging.Data.ConversationListItemData[];
    reportCategories: App.Domain.Trust.Enums.ReportCategory[];
    maxFiles: number;
    maxSizeMb: number;
};

/** A conversation with the organization handling one application. */
export default function MessageThread({
    conversation,
    conversations,
    reportCategories,
    maxFiles,
    maxSizeMb,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={conversation.counterpart} />
            <PageContainer>
                <MessagesSplit
                    listLabel={t('messages.conversations')}
                    showListOnMobile={false}
                    list={
                        <ConversationList
                            conversations={conversations}
                            href={(id) => worker.messages.show.url(id)}
                            emptyBody={t('messages.worker_empty')}
                            activeId={conversation.id}
                        />
                    }
                >
                    <PageHeader
                        title={conversation.counterpart}
                        back={{ href: worker.messages.index(), label: t('nav.messages') }}
                    />
                    <p className="-mt-2 text-xs text-muted">
                        {t('messages.linked_to')}{' '}
                        <Link
                            href={worker.applications.show(conversation.applicationId)}
                            className="font-semibold text-brand hover:underline"
                        >
                            {conversation.jobTitle} · <span dir="ltr">{conversation.jobCode}</span>
                        </Link>{' '}
                        · {t('messages.phone_hidden')}
                    </p>
                    <ConversationThread
                        conversation={conversation}
                        postUrl={worker.messages.store.url(conversation.id)}
                        reportCategories={reportCategories}
                        maxFiles={maxFiles}
                        maxSizeMb={maxSizeMb}
                    />
                </MessagesSplit>
            </PageContainer>
        </>
    );
}
