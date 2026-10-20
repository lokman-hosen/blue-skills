import { FileText, Lock, Upload, X } from 'lucide-react';
import type { DragEvent } from 'react';
import { useId, useRef, useState } from 'react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type FileUploadProps = {
    label: string;
    value: File | null;
    onChange: (file: File | null) => void;
    /** MIME types or extensions, e.g. ['application/pdf', 'image/*', '.heic']. */
    accept: string[];
    /** Human-readable list of accepted types shown to the user, e.g. "PDF, JPG or PNG". */
    acceptLabel: string;
    maxSizeMb: number;
    /** Server-side validation error. */
    error?: string;
    hint?: string;
    /** Shows the "stored privately" note for sensitive documents. */
    sensitive?: boolean;
    className?: string;
};

function matches(file: File, accept: string[]): boolean {
    return accept.some((rule) => {
        if (rule.startsWith('.')) {
            return file.name.toLowerCase().endsWith(rule.toLowerCase());
        }

        return rule.endsWith('/*') ? file.type.startsWith(rule.slice(0, -1)) : file.type === rule;
    });
}

export function FileUpload({
    label,
    value,
    onChange,
    accept,
    acceptLabel,
    maxSizeMb,
    error,
    hint,
    sensitive = false,
    className,
}: FileUploadProps) {
    const { t, formatNumber } = useTranslation();
    const inputId = useId();
    const messageId = useId();
    const input = useRef<HTMLInputElement>(null);
    const [localError, setLocalError] = useState<string | null>(null);
    const [dragging, setDragging] = useState(false);
    const message = localError ?? error;

    const select = (file: File | undefined) => {
        if (!file) {
            return;
        }

        if (!matches(file, accept)) {
            setLocalError(t('upload.wrong_type', { types: acceptLabel }));

            return;
        }

        if (file.size > maxSizeMb * 1024 * 1024) {
            setLocalError(t('upload.too_large', { size: formatNumber(maxSizeMb) }));

            return;
        }

        setLocalError(null);
        onChange(file);
    };

    const onDrop = (event: DragEvent<HTMLLabelElement>) => {
        event.preventDefault();
        setDragging(false);
        select(event.dataTransfer.files[0]);
    };

    return (
        <div className={cn('flex flex-col gap-1.5', className)}>
            <span className="text-[13px] font-semibold" id={`${inputId}-label`}>
                {label}
            </span>
            {value ? (
                <div className="flex items-center gap-3 rounded-xl border border-line bg-surface p-3">
                    <FileText aria-hidden className="size-5 shrink-0 text-brand" />
                    <div className="min-w-0 flex-1">
                        <p className="truncate text-sm font-semibold">{value.name}</p>
                        <p className="text-xs text-muted">
                            {formatNumber(Math.ceil(value.size / 1024))} KB
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={() => {
                            onChange(null);

                            if (input.current) {
                                input.current.value = '';
                            }
                        }}
                        className="grid size-11 place-items-center rounded-lg text-muted hover:bg-surface-2 hover:text-ink"
                        aria-label={`${t('upload.remove')}: ${value.name}`}
                    >
                        <X aria-hidden className="size-4" />
                    </button>
                </div>
            ) : (
                <label
                    htmlFor={inputId}
                    onDragOver={(event) => {
                        event.preventDefault();
                        setDragging(true);
                    }}
                    onDragLeave={() => setDragging(false)}
                    onDrop={onDrop}
                    className={cn(
                        'flex min-h-28 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-line bg-surface p-4 text-center transition focus-within:border-brand hover:border-brand/60',
                        dragging && 'border-brand bg-brand-soft',
                        message && 'border-bad/60',
                    )}
                >
                    <Upload aria-hidden className="size-6 text-brand" />
                    <span className="text-sm font-semibold text-brand">{t('upload.choose')}</span>
                    <span className="text-xs text-muted">{t('upload.drop')}</span>
                    <span className="text-xs text-muted">
                        {t('upload.limits', { types: acceptLabel, size: formatNumber(maxSizeMb) })}
                    </span>
                </label>
            )}
            <input
                ref={input}
                id={inputId}
                type="file"
                className="sr-only"
                accept={accept.join(',')}
                aria-labelledby={`${inputId}-label`}
                aria-describedby={messageId}
                aria-invalid={message ? true : undefined}
                onChange={(event) => select(event.target.files?.[0])}
            />
            <div id={messageId} className="flex flex-col gap-1">
                {hint && <p className="text-xs text-muted">{hint}</p>}
                {sensitive && (
                    <p className="flex items-center gap-1.5 text-xs text-muted">
                        <Lock aria-hidden className="size-3.5" />
                        {t('upload.private')}
                    </p>
                )}
                {message && (
                    <p role="alert" className="text-xs font-semibold text-bad">
                        {message}
                    </p>
                )}
            </div>
        </div>
    );
}
