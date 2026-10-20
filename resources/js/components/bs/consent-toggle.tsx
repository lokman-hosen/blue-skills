import { ShieldAlert } from 'lucide-react';
import { useId } from 'react';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

type ConsentToggleProps = {
    label: string;
    description?: string;
    checked: boolean;
    onCheckedChange: (checked: boolean) => void;
    /** Passport scans, phone numbers and similar: off by default, flagged as sensitive. */
    sensitive?: boolean;
    disabled?: boolean;
    className?: string;
};

/**
 * Per-item, explicit and revocable consent (product rule 6). The state is always spelled
 * out in words, not only by the switch position.
 */
export function ConsentToggle({
    label,
    description,
    checked,
    onCheckedChange,
    sensitive = false,
    disabled = false,
    className,
}: ConsentToggleProps) {
    const { t } = useTranslation();
    const id = useId();

    return (
        <div
            className={cn(
                'flex items-start gap-3 rounded-xl border border-line bg-surface p-3',
                sensitive && 'border-warn/40',
                className,
            )}
        >
            <div className="min-w-0 flex-1">
                <p id={`${id}-label`} className="text-sm font-semibold">
                    {label}
                </p>
                {description && (
                    <p id={`${id}-description`} className="text-xs text-muted">
                        {description}
                    </p>
                )}
                {sensitive && (
                    <p className="mt-1 flex items-center gap-1 text-xs font-semibold text-warn">
                        <ShieldAlert aria-hidden className="size-3.5" />
                        {t('consent.sensitive')}
                    </p>
                )}
            </div>
            <div className="flex shrink-0 flex-col items-end gap-1">
                <button
                    type="button"
                    role="switch"
                    aria-checked={checked}
                    aria-labelledby={`${id}-label`}
                    aria-describedby={description ? `${id}-description` : undefined}
                    disabled={disabled}
                    onClick={() => onCheckedChange(!checked)}
                    className="group grid min-h-11 min-w-11 place-items-center disabled:opacity-50"
                >
                    <span
                        aria-hidden
                        className={cn(
                            'relative inline-flex h-6 w-11 items-center rounded-full border-2 border-transparent transition-colors',
                            checked ? 'bg-brand' : 'bg-line',
                        )}
                    >
                        <span
                            className={cn(
                                'size-5 rounded-full bg-surface shadow transition-transform',
                                checked ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0',
                            )}
                        />
                    </span>
                </button>
                <span
                    className={cn(
                        'text-[11px] font-semibold',
                        checked ? 'text-brand' : 'text-muted',
                    )}
                >
                    {checked ? t('consent.shared') : t('consent.not_shared')}
                </span>
            </div>
        </div>
    );
}
