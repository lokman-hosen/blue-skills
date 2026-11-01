import { Head } from '@inertiajs/react';
import { MessagesSquare } from 'lucide-react';
import { ConversationList } from '@/components/bs/messages/conversation-list';
import { MessagesSplit } from '@/components/bs/messages/messages-split';
import { PageContainer } from '@/components/bs/page-container';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import worker from '@/routes/worker';

type Props = {
    conversations: App.Domain.Messaging.Data.ConversationListItemData[];
};

/** The worker's conversations, one per application (spec §9). */
export default function MessagesIndex({ conversations }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.messages')} />
            <PageContainer>
                <MessagesSplit
                    header={
                        <PageHeader
                            title={t('nav.messages')}
                            description={t('messages.worker_body')}
                        />
                    }
                    listLabel={t('messages.conversations')}
                    showListOnMobile
                    list={
                        <ConversationList
                            conversations={conversations}
                            href={(id) => worker.messages.show.url(id)}
                            emptyBody={t('messages.worker_empty')}
                        />
                    }
                >
                    {conversations.length > 0 && (
                        <div className="hidden min-h-64 flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-line p-8 text-center lg:flex">
                            <MessagesSquare aria-hidden className="size-8 text-muted" />
                            <p className="font-semibold">{t('messages.select_title')}</p>
                            <p className="max-w-sm text-sm text-muted">
                                {t('messages.select_body')}
                            </p>
                        </div>
                    )}
                </MessagesSplit>
            </PageContainer>
        </>
    );
}
