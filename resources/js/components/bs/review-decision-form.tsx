import { useForm } from '@inertiajs/react';
import { Check, CircleHelp, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';

type Decision = App.Domain.Verification.Enums.VerificationDecision;

/**
 * Approve, ask for more information, or reject with a reason code and an optional note that
 * the reviewed party sees (spec §10). Used for organization and job reviews.
 */
export function ReviewDecisionForm({
    action,
    reasons,
    allowNeedsInfo = true,
}: {
    action: string;
    reasons: App.Domain.Verification.Enums.ReviewReason[];
    /** Worker evidence is verified or rejected only (D-090). */
    allowNeedsInfo?: boolean;
}) {
    const { t } = useTranslation();
    const form = useForm<{ decision: Decision | ''; reason: string; note: string }>({
        decision: '',
        reason: '',
        note: '',
    });

    const submit = (decision: Decision) => (event: FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, decision }));
        form.patch(action, { preserveScroll: true });
    };

    return (
        <form className="flex flex-col gap-3" onSubmit={submit('approved')} noValidate>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="review-reason">{t('admin.review.reason')}</Label>
                <NativeSelect
                    id="review-reason"
                    value={form.data.reason}
                    onChange={(event) => form.setData('reason', event.target.value)}
                    aria-invalid={!!form.errors.reason}
                >
                    <option value="">{t('admin.review.reason_placeholder')}</option>
                    {reasons.map((reason) => (
                        <option key={reason} value={reason}>
                            {t(`review_reason.${reason}`)}
                        </option>
                    ))}
                </NativeSelect>
                <InputError message={form.errors.reason} />
            </div>
            <div className="flex flex-col gap-1.5">
                <Label htmlFor="review-note">{t('admin.review.note')}</Label>
                <textarea
                    id="review-note"
                    value={form.data.note}
                    maxLength={1000}
                    rows={3}
                    onChange={(event) => form.setData('note', event.target.value)}
                    className="w-full rounded-md border border-input bg-surface px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm"
                    aria-describedby="review-note-hint"
                />
                <p id="review-note-hint" className="text-xs text-muted">
                    {t('admin.review.note_hint')}
                </p>
                <InputError message={form.errors.note} />
            </div>
            {/* Anything else the server refused, e.g. a job that now breaks the corridor rules. */}
            {Object.entries(form.errors as Record<string, string>)
                .filter(([key]) => key !== 'reason' && key !== 'note')
                .map(([key, message]) => (
                    <InputError key={key} message={message} />
                ))}
            <div
                className={
                    allowNeedsInfo ? 'grid gap-2 sm:grid-cols-3' : 'grid gap-2 sm:grid-cols-2'
                }
            >
                <Button type="submit" className="min-h-11" disabled={form.processing}>
                    {form.processing ? <Spinner /> : <Check aria-hidden className="size-4" />}
                    {t('admin.review.approve')}
                </Button>
                {allowNeedsInfo && (
                    <Button
                        type="button"
                        variant="outline"
                        className="min-h-11"
                        disabled={form.processing || form.data.reason === ''}
                        onClick={submit('needs_info')}
                    >
                        <CircleHelp aria-hidden className="size-4" />
                        {t('admin.review.request_info')}
                    </Button>
                )}
                <Button
                    type="button"
                    variant="outline"
                    className="min-h-11 text-bad"
                    disabled={form.processing || form.data.reason === ''}
                    onClick={submit('rejected')}
                >
                    <X aria-hidden className="size-4" />
                    {t('admin.review.reject')}
                </Button>
            </div>
        </form>
    );
}
