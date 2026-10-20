import { Head, router, usePoll } from '@inertiajs/react';
import { AlertCircle, Trash2, Video } from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useMemo, useRef, useState } from 'react';
import SkillShowController from '@/actions/App/Http/Controllers/Worker/SkillShowController';
import { ConfirmDialog } from '@/components/bs/confirm-dialog';
import { NativeSelect } from '@/components/bs/native-select';
import { PageHeader } from '@/components/bs/page-header';
import { StatusChip } from '@/components/bs/status-chip';
import type { Tone } from '@/components/bs/tone';
import { ChipGroup } from '@/components/bs/voice-fields-editor';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import { optionLookup } from '@/lib/passport';
import worker from '@/routes/worker';

type SkillShow = App.Domain.Worker.Data.SkillShowData;
type Visibility = App.Domain.Worker.Enums.ProfileVisibility;

type Props = {
    skillShow: SkillShow | null;
    occupationSlug: string | null;
    options: App.Domain.Worker.Data.ProfileOptionsData;
    maxSeconds: number;
    maxSizeMb: number;
    acceptedTypes: string[];
    maxSkills: number;
};

const VISIBILITY: Visibility[] = ['employers_and_agencies', 'applications_only', 'hidden'];

const statusTone: Record<App.Domain.Worker.Enums.ModerationStatus, Tone> = {
    processing: 'neutral',
    failed: 'bad',
    pending: 'warn',
    approved: 'ok',
    rejected: 'bad',
};

/**
 * Record or upload → check → preview → tag skills → moderation → publish (spec §3). Always
 * labelled as a worker-provided demonstration, not certification (product rule 3).
 */
export default function SkillShowPage({
    skillShow,
    occupationSlug,
    options,
    maxSeconds,
    maxSizeMb,
    acceptedTypes,
    maxSkills,
}: Props) {
    const { t, formatNumber } = useTranslation();
    const lookup = useMemo(() => optionLookup(options), [options]);
    const skillIds = useMemo(
        () => new Map(options.skills.map((skill) => [skill.slug, skill.id])),
        [options.skills],
    );
    const editable =
        skillShow !== null && skillShow.status !== 'failed' && skillShow.status !== 'processing';
    const [replacing, setReplacing] = useState(skillShow === null || skillShow.status === 'failed');
    const [file, setFile] = useState<File | null>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const [durationMs, setDurationMs] = useState<number | null>(null);
    const [selected, setSelected] = useState<string[]>(
        skillShow?.skillSlugs.map((slug) => skillIds.get(slug) ?? slug) ?? [],
    );
    const [visibility, setVisibility] = useState<Visibility>(
        skillShow?.visibility ?? 'applications_only',
    );
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);
    const input = useRef<HTMLInputElement>(null);

    const { start, stop } = usePoll(3000, { only: ['skillShow'] }, { autoStart: false });

    useEffect(() => {
        if (skillShow?.status === 'processing') {
            start();
        } else {
            stop();
        }
    }, [skillShow?.status, start, stop]);

    useEffect(
        () => () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        },
        [previewUrl],
    );

    const choose = (picked: File | undefined) => {
        setErrors({});
        setFile(null);
        setDurationMs(null);
        setPreviewUrl(null);

        if (!picked) {
            return;
        }

        if (!picked.type.startsWith('video/')) {
            setErrors({ video: t('skillshow.error.type') });

            return;
        }

        if (picked.size > maxSizeMb * 1024 * 1024) {
            setErrors({ video: t('skillshow.error.size', { max: formatNumber(maxSizeMb) }) });

            return;
        }

        const url = URL.createObjectURL(picked);
        const probe = document.createElement('video');
        probe.preload = 'metadata';
        probe.onloadedmetadata = () => {
            const ms = Math.round(probe.duration * 1000);

            // The server checks again from the file itself; this only saves a wasted upload.
            if (Number.isFinite(ms) && ms > maxSeconds * 1000 + 500) {
                setErrors({
                    video: t('skillshow.error.too_long', {
                        seconds: formatNumber(Math.round(ms / 1000)),
                        max: formatNumber(maxSeconds),
                    }),
                });
                URL.revokeObjectURL(url);

                return;
            }

            setFile(picked);
            setDurationMs(Number.isFinite(ms) ? ms : null);
            setPreviewUrl(url);
        };
        probe.onerror = () => {
            // Some formats (e.g. HEVC on desktop) do not preview; let the server decide.
            setFile(picked);
            setPreviewUrl(null);
        };
        probe.src = url;
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const data = { skill_ids: selected, visibility };
        const callbacks = {
            preserveScroll: true,
            onStart: () => setProcessing(true),
            onFinish: () => setProcessing(false),
            onError: (bag: Record<string, string>) => setErrors(bag),
            onSuccess: () => {
                setFile(null);
                setPreviewUrl(null);
                setReplacing(false);
            },
        };

        if (replacing) {
            router.post(
                SkillShowController.store.url(),
                { ...data, video: file },
                { ...callbacks, forceFormData: true },
            );
        } else if (skillShow) {
            router.put(SkillShowController.update.url(skillShow.id), data, callbacks);
        }
    };

    const skillError = Object.entries(errors).find(([key]) => key.startsWith('skill_ids'))?.[1];

    return (
        <>
            <Head title={t('skillshow.page_title')} />
            <div className="mx-auto flex w-full max-w-xl flex-col gap-5">
                <PageHeader
                    title={t('skillshow.page_title')}
                    description={t('skillshow.page_body', { seconds: formatNumber(maxSeconds) })}
                    back={{ href: worker.passport.show(), label: t('passport.title') }}
                />

                {skillShow && !replacing && (
                    <section className="flex flex-col gap-3 rounded-2xl border border-line bg-surface p-4">
                        <div className="aspect-[9/12] overflow-hidden rounded-xl border border-line bg-screen">
                            {skillShow.videoUrl ? (
                                <video
                                    src={skillShow.videoUrl}
                                    poster={skillShow.posterUrl ?? undefined}
                                    controls
                                    playsInline
                                    preload="metadata"
                                    className="size-full object-contain"
                                >
                                    <track kind="captions" />
                                </video>
                            ) : (
                                <div className="grid size-full place-items-center text-on-screen">
                                    {skillShow.status === 'processing' ? (
                                        <Spinner className="size-8" />
                                    ) : (
                                        <Video aria-hidden className="size-10" />
                                    )}
                                </div>
                            )}
                        </div>
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <p className="text-[13px] font-semibold">{t('skillshow.title')}</p>
                            <StatusChip
                                label={t(`skillshow.moderation.${skillShow.status}`)}
                                tone={statusTone[skillShow.status]}
                            />
                        </div>
                        <p className="text-sm text-muted" aria-live="polite">
                            {t(`skillshow.status_note.${skillShow.status}`)}
                        </p>
                        {skillShow.reasonCode && (
                            <p
                                role="alert"
                                className="flex items-start gap-2 rounded-lg bg-bad-soft px-3 py-2 text-sm text-bad"
                            >
                                <AlertCircle aria-hidden className="mt-0.5 size-4 shrink-0" />
                                {t(`skillshow.reason.${skillShow.reasonCode}`)}
                            </p>
                        )}
                        <p className="rounded-lg bg-surface-2 px-2.5 py-1.5 text-[11.5px] text-muted">
                            {t('skillshow.disclaimer')}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                variant="outline"
                                className="min-h-11"
                                onClick={() => setReplacing(true)}
                            >
                                <Video aria-hidden className="size-4" />
                                {t('skillshow.replace')}
                            </Button>
                            <ConfirmDialog
                                trigger={
                                    <Button variant="outline" className="min-h-11 text-bad">
                                        <Trash2 aria-hidden className="size-4" />
                                        {t('skillshow.delete')}
                                    </Button>
                                }
                                title={t('skillshow.delete')}
                                description={t('skillshow.delete_body')}
                                confirmLabel={t('skillshow.delete')}
                                destructive
                                onConfirm={() =>
                                    router.delete(SkillShowController.destroy.url(skillShow.id))
                                }
                            />
                        </div>
                    </section>
                )}

                {skillShow?.status === 'failed' && replacing && (
                    <p
                        role="alert"
                        className="flex items-start gap-2 rounded-lg bg-bad-soft px-3 py-2 text-sm text-bad"
                    >
                        <AlertCircle aria-hidden className="mt-0.5 size-4 shrink-0" />
                        {t(`skillshow.reason.${skillShow.reasonCode ?? 'unreadable'}`, {
                            max: formatNumber(maxSeconds),
                        })}
                    </p>
                )}

                {(replacing || editable) && (
                    <form
                        onSubmit={submit}
                        className="flex flex-col gap-5 rounded-2xl border border-line bg-surface p-5"
                        noValidate
                    >
                        {replacing && (
                            <div className="flex flex-col gap-2">
                                <div className="aspect-[9/12] overflow-hidden rounded-xl border border-line bg-surface-2">
                                    {previewUrl ? (
                                        <video
                                            src={previewUrl}
                                            controls
                                            playsInline
                                            className="size-full bg-screen object-contain"
                                        >
                                            <track kind="captions" />
                                        </video>
                                    ) : (
                                        <div className="grid size-full place-items-center text-center text-muted">
                                            <div className="flex flex-col items-center gap-2">
                                                <Video aria-hidden className="size-10" />
                                                <p className="font-mono text-xs" dir="ltr">
                                                    00:00 / 00:{String(maxSeconds).padStart(2, '0')}
                                                </p>
                                                {file && <p className="text-xs">{file.name}</p>}
                                            </div>
                                        </div>
                                    )}
                                </div>
                                {durationMs !== null && (
                                    <StatusChip
                                        className="self-start"
                                        tone="brand"
                                        label={t('skillshow.ready', {
                                            seconds: formatNumber(Math.round(durationMs / 1000)),
                                        })}
                                    />
                                )}
                                <label className="inline-flex min-h-12 cursor-pointer items-center justify-center gap-2 rounded-xl bg-brand px-4 text-sm font-semibold text-on-brand focus-within:ring-[3px] focus-within:ring-ring/50">
                                    <Video aria-hidden className="size-4" />
                                    {file ? t('skillshow.retake') : t('skillshow.record_or_upload')}
                                    <input
                                        ref={input}
                                        type="file"
                                        accept={['video/*', ...acceptedTypes].join(',')}
                                        className="sr-only"
                                        onChange={(event) => choose(event.target.files?.[0])}
                                    />
                                </label>
                                <p className="text-xs text-muted">
                                    {t('skillshow.tips', {
                                        seconds: formatNumber(maxSeconds),
                                        size: formatNumber(maxSizeMb),
                                    })}
                                </p>
                                <InputError message={errors.video} />
                            </div>
                        )}

                        <fieldset className="flex flex-col gap-2">
                            <legend className="mb-1.5 text-[13px] font-semibold">
                                {t('skillshow.tag_skills', { max: formatNumber(maxSkills) })}
                            </legend>
                            <ChipGroup
                                items={lookup.skillsFor(occupationSlug).map((skill) => ({
                                    value: skillIds.get(skill.slug) ?? skill.slug,
                                    label: skill.name,
                                }))}
                                selected={selected}
                                onToggle={(value) =>
                                    setSelected(
                                        selected.includes(value)
                                            ? selected.filter((item) => item !== value)
                                            : selected.length < maxSkills
                                              ? [...selected, value]
                                              : selected,
                                    )
                                }
                            />
                            <InputError message={skillError} />
                        </fieldset>

                        <div className="flex flex-col gap-1.5">
                            <Label htmlFor="skillshow-visibility">
                                {t('skillshow.who_can_see')}
                            </Label>
                            <NativeSelect
                                id="skillshow-visibility"
                                value={visibility}
                                onChange={(event) =>
                                    setVisibility(event.target.value as Visibility)
                                }
                            >
                                {VISIBILITY.map((value) => (
                                    <option key={value} value={value}>
                                        {t(`visibility.${value}`)}
                                    </option>
                                ))}
                            </NativeSelect>
                            <InputError message={errors.visibility} />
                        </div>

                        <p className="rounded-lg bg-surface-2 px-3 py-2 text-xs text-muted">
                            {t('skillshow.moderation_note')}
                        </p>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                className="min-h-12 sm:min-w-48"
                                disabled={
                                    processing || (replacing && !file) || selected.length === 0
                                }
                            >
                                {processing && <Spinner />}
                                {replacing ? t('skillshow.submit') : t('common.save')}
                            </Button>
                            {replacing && skillShow && skillShow.status !== 'failed' && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="min-h-12"
                                    onClick={() => setReplacing(false)}
                                >
                                    {t('common.cancel')}
                                </Button>
                            )}
                        </div>
                        {replacing && skillShow && skillShow.status !== 'failed' && (
                            <p className="text-xs text-muted">{t('skillshow.replace_note')}</p>
                        )}
                    </form>
                )}
            </div>
        </>
    );
}
