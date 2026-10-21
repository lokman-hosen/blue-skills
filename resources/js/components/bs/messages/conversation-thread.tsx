import { router, useForm } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { Flag, Lock, Paperclip, Send, ShieldAlert, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import messageRoutes from '@/routes/messages';

type Conversation = App.Domain.Messaging.Data.ConversationData;
type Message = App.Domain.Messaging.Data.MessageData;

/**
 * One application's secure conversation (spec §9): live through Reverb, a fixed safety line,
 * attachments, and a report control on every message from the other side.
 */
export function ConversationThread({
    conversation,
    postUrl,
    reportCategories,
    maxFiles,
    maxSizeMb,
}: {
    conversation: Conversation;
    postUrl: string;
    reportCategories: App.Domain.Trust.Enums.ReportCategory[];
    maxFiles: number;
    maxSizeMb: number;
}) {
    const { t } = useTranslation();
    const [reporting, setReporting] = useState<Message | null>(null);
    const end = useRef<HTMLDivElement>(null);

    useEcho(`conversations.${conversation.id}`, '.message.sent', () =>
        router.reload({ only: ['conversation'] }),
    );

    useEffect(() => {
        end.current?.scrollIntoView({ block: 'end' });
    }, [conversation.messages.length]);

    return (
        <div className="flex flex-col gap-3">
            <p className="flex items-start gap-2 rounded-xl border border-warn/40 bg-warn-soft px-3 py-2 text-[13px]">
                <ShieldAlert aria-hidden className="mt-0.5 size-4 shrink-0 text-warn" />
                {t('messages.safety')}
            </p>

            <ol aria-live="polite" className="flex flex-col gap-3">
                {conversation.messages.length === 0 && (
                    <li className="py-6 text-center text-sm text-muted">{t('messages.start')}</li>
                )}
                {conversation.messages.map((message) => (
                    <li
                        key={message.id}
                        className={cn(
                            'flex flex-col gap-1',
                            message.mine ? 'items-end' : 'items-start',
                        )}
                    >
                        <div
                            className={cn(
                                'max-w-[85%] rounded-2xl px-3.5 py-2.5 break-words',
                                message.mine
                                    ? 'rounded-ee-sm bg-brand text-on-brand'
                                    : 'rounded-es-sm bg-surface-2',
                            )}
                        >
                            {!message.mine && (
                                <p className="mb-0.5 text-[11px] font-semibold text-brand">
                                    {message.senderName}
                                </p>
                            )}
                            <p className="text-[14px] whitespace-pre-line">{message.body}</p>
                            {message.attachments.length > 0 && (
                                <ul className="mt-2 flex flex-col gap-1">
                                    {message.attachments.map((attachment) => (
                                        <li key={attachment.id}>
                                            <a
                                                href={attachment.url ?? undefined}
                                                target="_blank"
                                                rel="noreferrer"
                                                className={cn(
                                                    'inline-flex min-h-9 items-center gap-1.5 text-[13px] font-semibold underline',
                                                    message.mine ? 'text-on-brand' : 'text-brand',
                                                )}
                                            >
                                                <Paperclip aria-hidden className="size-3.5" />
                                                {attachment.name}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                        <p className="flex items-center gap-2 px-1 text-[11px] text-muted">
                            <TimeStamp at={message.at} />
                            {!message.mine && message.side !== 'system' && (
                                <button
                                    type="button"
                                    onClick={() => setReporting(message)}
                                    className="inline-flex min-h-9 items-center gap-1 hover:text-bad"
                                >
                                    <Flag aria-hidden className="size-3" />
                                    {t('messages.report')}
                                </button>
                            )}
                        </p>
                    </li>
                ))}
            </ol>
            <div ref={end} />

            {conversation.canPost ? (
                <Composer postUrl={postUrl} maxFiles={maxFiles} maxSizeMb={maxSizeMb} />
            ) : (
                <p className="flex items-center gap-2 rounded-xl bg-surface-2 px-3 py-2 text-sm text-muted">
                    <Lock aria-hidden className="size-4" />
                    {conversation.writable ? t('messages.read_only_role') : t('messages.closed')}
                </p>
            )}

            <ReportDialog
                message={reporting}
                categories={reportCategories}
                onClose={() => setReporting(null)}
            />
        </div>
    );
}

function TimeStamp({ at }: { at: string }) {
    const { formatDate } = useTranslation();

    return (
        <time dateTime={at}>
            {formatDate(at, { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' })}
        </time>
    );
}

function Composer({
    postUrl,
    maxFiles,
    maxSizeMb,
}: {
    postUrl: string;
    maxFiles: number;
    maxSizeMb: number;
}) {
    const { t } = useTranslation();
    const form = useForm<{ body: string; attachments: File[] }>({ body: '', attachments: [] });
    const fileInput = useRef<HTMLInputElement>(null);

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(postUrl, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const attachmentError = Object.entries(form.errors).find(([key]) =>
        key.startsWith('attachments'),
    )?.[1];

    return (
        <form
            onSubmit={submit}
            noValidate
            className="sticky bottom-20 flex flex-col gap-2 rounded-2xl border border-line bg-surface p-3 lg:bottom-4"
        >
            <Label htmlFor="message-body" className="sr-only">
                {t('messages.write')}
            </Label>
            <textarea
                id="message-body"
                rows={2}
                maxLength={2000}
                value={form.data.body}
                onChange={(event) => form.setData('body', event.target.value)}
                placeholder={t('messages.write')}
                className="w-full resize-y rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
            />
            {form.data.attachments.length > 0 && (
                <ul className="flex flex-wrap gap-1.5">
                    {form.data.attachments.map((file, index) => (
                        <li
                            key={`${file.name}-${index}`}
                            className="inline-flex items-center gap-1 rounded-full bg-surface-2 ps-2.5 text-xs"
                        >
                            <span className="max-w-40 truncate">{file.name}</span>
                            <button
                                type="button"
                                aria-label={t('messages.remove_file', { name: file.name })}
                                onClick={() =>
                                    form.setData(
                                        'attachments',
                                        form.data.attachments.filter((_, i) => i !== index),
                                    )
                                }
                                className="grid size-8 place-items-center rounded-full hover:text-bad"
                            >
                                <X aria-hidden className="size-3.5" />
                            </button>
                        </li>
                    ))}
                </ul>
            )}
            <InputError message={form.errors.body ?? attachmentError} />
            <div className="flex items-center justify-between gap-2">
                <input
                    ref={fileInput}
                    type="file"
                    multiple
                    accept="application/pdf,image/jpeg,image/png"
                    className="sr-only"
                    tabIndex={-1}
                    onChange={(event) => {
                        const files = Array.from(event.target.files ?? []);
                        form.setData(
                            'attachments',
                            [...form.data.attachments, ...files].slice(0, maxFiles),
                        );
                        event.target.value = '';
                    }}
                />
                <Button
                    type="button"
                    variant="ghost"
                    className="min-h-11"
                    disabled={form.data.attachments.length >= maxFiles}
                    onClick={() => fileInput.current?.click()}
                    title={t('messages.attach_hint', { max: maxFiles, size: maxSizeMb })}
                >
                    <Paperclip aria-hidden className="size-4" />
                    {t('messages.attach')}
                </Button>
                <Button
                    type="submit"
                    className="min-h-11"
                    disabled={form.processing || form.data.body.trim() === ''}
                >
                    {form.processing ? (
                        <Spinner />
                    ) : (
                        <Send aria-hidden className="size-4 rtl:-scale-x-100" />
                    )}
                    {t('messages.send')}
                </Button>
            </div>
        </form>
    );
}

function ReportDialog({
    message,
    categories,
    onClose,
}: {
    message: Message | null;
    categories: App.Domain.Trust.Enums.ReportCategory[];
    onClose: () => void;
}) {
    const { t } = useTranslation();
    const form = useForm({ category: '', description: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        if (!message) {
            return;
        }

        form.post(messageRoutes.report.url(message.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onClose();
            },
        });
    };

    return (
        <Dialog open={message !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{t('messages.report_title')}</DialogTitle>
                    <DialogDescription>{t('messages.report_body')}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-4" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="message-report-category">{t('jobs.report.category')}</Label>
                        <NativeSelect
                            id="message-report-category"
                            value={form.data.category}
                            onChange={(event) => form.setData('category', event.target.value)}
                        >
                            <option value="">{t('common.choose')}</option>
                            {categories.map((category) => (
                                <option key={category} value={category}>
                                    {t(`report.category.${category}`)}
                                </option>
                            ))}
                        </NativeSelect>
                        <InputError message={form.errors.category} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor="message-report-description">
                            {t('messages.report_details')}
                        </Label>
                        <textarea
                            id="message-report-description"
                            rows={3}
                            maxLength={500}
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    <Button
                        type="submit"
                        variant="destructive"
                        className="min-h-11"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        {t('messages.report_send')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
