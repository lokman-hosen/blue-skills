import { CalendarClock, CircleAlert, FileText } from 'lucide-react';
import type { ReactNode } from 'react';
import { StatusChip } from '@/components/bs/status-chip';
import { useTranslation } from '@/lib/i18n';

export type CaseDocument = App.Domain.Mobility.Data.CaseDocumentData;

export const DOCUMENT_ACCEPT = ['application/pdf', 'image/jpeg', 'image/png'];

/** One checklist item: who can see it, its state, the file and why it came back (D-079). */
export function CaseDocumentRow({
    document,
    viewer,
    children,
}: {
    document: CaseDocument;
    viewer: 'worker' | 'organization';
    children?: ReactNode;
}) {
    const { t, formatDate } = useTranslation();
    const fromWorker = document.providedBy === 'worker';
    const visibleTo =
        document.visibleTo.length > 0
            ? document.visibleTo.join(', ')
            : viewer === 'worker'
              ? t('mobility.document.only_you')
              : t('mobility.document.not_shared');

    return (
        <li className="flex flex-col gap-2 py-3">
            <div className="flex items-start gap-3">
                <div className="flex min-w-0 flex-1 flex-col gap-0.5">
                    <p className="text-sm font-semibold break-words">{document.title}</p>
                    <p className="text-xs text-muted">
                        {fromWorker
                            ? t('mobility.document.from_worker')
                            : t('mobility.document.from_organization')}
                        {' · '}
                        {t('mobility.document.visible_to', { names: visibleTo })}
                    </p>
                </div>
                <StatusChip status={document.status} />
            </div>

            {document.dueOn && document.status.value !== 'doc_accepted' && (
                <p className="flex items-center gap-1.5 text-xs font-semibold text-stamp">
                    <CalendarClock aria-hidden className="size-3.5" />
                    {t('mobility.due', { date: formatDate(document.dueOn) })}
                </p>
            )}

            {document.status.value === 'doc_returned' && (
                <p className="flex items-start gap-1.5 rounded-lg bg-bad-soft px-3 py-2 text-xs">
                    <CircleAlert aria-hidden className="mt-0.5 size-3.5 shrink-0 text-bad" />
                    <span>
                        {document.returnReason && (
                            <span className="font-semibold">
                                {t(`mobility.document.return_reason.${document.returnReason}`)}
                                {document.note ? ': ' : ''}
                            </span>
                        )}
                        {document.note}
                    </span>
                </p>
            )}
            {document.status.value !== 'doc_returned' && document.note && (
                <p className="text-xs whitespace-pre-line text-muted">{document.note}</p>
            )}

            {document.fileUrl && (
                <a
                    href={document.fileUrl}
                    target="_blank"
                    rel="noreferrer"
                    className="inline-flex min-h-11 items-center gap-1.5 self-start text-sm font-semibold text-brand underline-offset-4 hover:underline"
                >
                    <FileText aria-hidden className="size-4" />
                    <span className="break-all">{document.fileName ?? t('documents.open')}</span>
                </a>
            )}

            {children}
        </li>
    );
}
