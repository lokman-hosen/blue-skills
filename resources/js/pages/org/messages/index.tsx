import { Head, router } from '@inertiajs/react';
import { ConversationList } from '@/components/bs/messages/conversation-list';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import org from '@/routes/org';

type Props = {
    conversations: App.Domain.Messaging.Data.ConversationListItemData[];
    job: string | null;
    jobs: { code: string; title: string }[];
};

/** The organization's inbox: conversations with applicants (spec §9). */
export default function OrgMessages({ conversations, job, jobs }: Props) {
    const { t } = useTranslation();

    return (
        <>
            <Head title={t('nav.messages')} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-5">
                <PageHeader title={t('nav.messages')} description={t('messages.org_body')} />
                {jobs.length > 0 && (
                    <div className="flex flex-col gap-1.5 sm:max-w-sm">
                        <Label htmlFor="inbox-job">{t('messages.filter_job')}</Label>
                        <NativeSelect
                            id="inbox-job"
                            value={job ?? ''}
                            onChange={(event) =>
                                router.get(
                                    org.messages.index.url(),
                                    event.target.value ? { job: event.target.value } : {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            <option value="">{t('messages.all_jobs')}</option>
                            {jobs.map((item) => (
                                <option key={item.code} value={item.code}>
                                    {item.title} · {item.code}
                                </option>
                            ))}
                        </NativeSelect>
                    </div>
                )}
                <ConversationList
                    conversations={conversations}
                    href={(id) => org.messages.show.url(id)}
                    emptyBody={t('messages.org_empty')}
                />
            </div>
        </>
    );
}
