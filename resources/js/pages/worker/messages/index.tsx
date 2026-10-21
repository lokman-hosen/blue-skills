import { Head } from '@inertiajs/react';
import { ConversationList } from '@/components/bs/messages/conversation-list';
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
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-5">
                <PageHeader title={t('nav.messages')} description={t('messages.worker_body')} />
                <ConversationList
                    conversations={conversations}
                    href={(id) => worker.messages.show.url(id)}
                    emptyBody={t('messages.worker_empty')}
                />
            </div>
        </>
    );
}
