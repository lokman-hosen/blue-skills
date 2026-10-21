import { useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import { useState } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

const TEXTAREA =
    'w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

/**
 * A trust decision with a reason code and a note (spec §10): dismiss a report, suspend or
 * restore, close a case, decide an appeal. Reasons are omitted when the decision has none.
 */
export function ReasonDialog({
    id,
    trigger,
    title,
    description,
    action,
    reasons,
    noteRequired = false,
    submitLabel,
    destructive = false,
}: {
    id: string;
    trigger: ReactNode;
    title: string;
    description: string;
    action: string;
    reasons?: App.Domain.Trust.Enums.TrustReason[];
    noteRequired?: boolean;
    submitLabel: string;
    destructive?: boolean;
}) {
    const { t } = useTranslation();
    const [open, setOpen] = useState(false);
    const form = useForm({ reason: '', note: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(action, {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription>{description}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    {reasons && (
                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor={`${id}-reason`}>{t('trust.reason')}</Label>
                            <NativeSelect
                                id={`${id}-reason`}
                                value={form.data.reason}
                                onChange={(event) => form.setData('reason', event.target.value)}
                            >
                                <option value="" disabled>
                                    {t('common.choose')}
                                </option>
                                {reasons.map((reason) => (
                                    <option key={reason} value={reason}>
                                        {t(`trust_reason.${reason}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={form.errors.reason} />
                        </div>
                    )}
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-note`}>
                            {t('trust.note')}
                            {!noteRequired && ` (${t('common.optional')})`}
                        </Label>
                        <textarea
                            id={`${id}-note`}
                            rows={3}
                            maxLength={1000}
                            value={form.data.note}
                            onChange={(event) => form.setData('note', event.target.value)}
                            className={TEXTAREA}
                        />
                        <InputError message={form.errors.note} />
                        {Object.entries(form.errors as Record<string, string>)
                            .filter(([key]) => key !== 'reason' && key !== 'note')
                            .map(([key, message]) => (
                                <InputError key={key} message={message} />
                            ))}
                    </div>
                    <Button
                        type="submit"
                        variant={destructive ? 'destructive' : 'default'}
                        className="min-h-11 self-end"
                        disabled={
                            form.processing || (reasons !== undefined && form.data.reason === '')
                        }
                    >
                        {form.processing && <Spinner />}
                        {submitLabel}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
