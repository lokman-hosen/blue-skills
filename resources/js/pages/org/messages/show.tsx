import { Head, Link } from '@inertiajs/react';
import { ConversationThread } from '@/components/bs/messages/conversation-thread';
import { PageHeader } from '@/components/bs/page-header';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    conversation: App.Domain.Messaging.Data.ConversationData;
    reportCategories: App.Domain.Trust.Enums.ReportCategory[];
    maxFiles: number;
    maxSizeMb: number;
};

/** A conversation with one applicant. */
export default function OrgMessageThread({
    conversation,
    reportCategories,
    maxFiles,
    maxSizeMb,
}: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={conversation.counterpart} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4">
                <PageHeader
                    title={conversation.counterpart}
                    back={{ href: org.messages.index(), label: t('nav.messages') }}
                />
                <p className="-mt-2 text-xs text-muted">
                    {t('messages.linked_to')}{' '}
                    <Link
                        href={org.applicants.show(conversation.applicationId)}
                        className="font-semibold text-brand hover:underline"
                    >
                        {conversation.jobTitle} · <span dir="ltr">{conversation.jobCode}</span>
                    </Link>
                </p>
                <ConversationThread
                    conversation={conversation}
                    postUrl={org.messages.store.url(conversation.id)}
                    reportCategories={reportCategories}
                    maxFiles={maxFiles}
                    maxSizeMb={maxSizeMb}
                />
            </div>
        </>
    );
}
