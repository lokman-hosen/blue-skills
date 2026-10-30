import { useForm } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import type { FormEvent } from 'react';
import { useId, useState } from 'react';
import { NativeSelect } from '@/components/bs/native-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslation } from '@/lib/i18n';
import admin from '@/routes/admin';

type Role = App.Domain.Identity.Data.RoleSummaryData;

type Props =
    /** Create a role of this kind, starting from one of `bases`. */
    | { kind: string; bases: Role[]; role?: undefined }
    /** Rename a role the Superuser created. */
    | { role: Role; kind?: undefined; bases?: undefined };

/** New role, or the name and description of a created role (D-113). */
export function RoleDialog({ kind, bases, role }: Props) {
    const { t } = useTranslation();
    const id = useId();
    const [open, setOpen] = useState(false);
    const form = useForm({
        kind: kind ?? role?.kind ?? '',
        label: role?.label ?? '',
        description: role?.description ?? '',
        based_on: bases?.find((base) => !base.locked)?.name ?? '',
        copy_permissions: true,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (role) {
            form.patch(admin.roles.details.url(role.name), options);
        } else {
            form.post(admin.roles.store.url(), options);
        }
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);

                if (next && !role) {
                    form.reset();
                    form.clearErrors();
                }
            }}
        >
            <DialogTrigger asChild>
                {role ? (
                    <Button variant="outline" className="min-h-11">
                        <Pencil aria-hidden />
                        {t('roles.details')}
                    </Button>
                ) : (
                    <Button variant="outline" size="sm" className="min-h-11 md:min-h-9">
                        <Plus aria-hidden />
                        {t(`roles.new_for.${kind}`)}
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90dvh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {role ? t('roles.details') : t(`roles.new_for.${kind}`)}
                    </DialogTitle>
                    <DialogDescription>
                        {role ? t('roles.details_body') : t('roles.new_body')}
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="flex flex-col gap-3" noValidate>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-label`}>{t('roles.field.name')}</Label>
                        <Input
                            id={`${id}-label`}
                            className="h-11"
                            maxLength={60}
                            value={form.data.label}
                            onChange={(event) => form.setData('label', event.target.value)}
                            aria-invalid={!!form.errors.label}
                            aria-describedby={`${id}-label-hint`}
                        />
                        <p id={`${id}-label-hint`} className="text-xs text-muted">
                            {t('roles.field.name_hint')}
                        </p>
                        <InputError message={form.errors.label} />
                    </div>
                    <div className="flex flex-col gap-1.5">
                        <Label htmlFor={`${id}-description`}>{t('roles.field.description')}</Label>
                        <Input
                            id={`${id}-description`}
                            className="h-11"
                            maxLength={255}
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            aria-invalid={!!form.errors.description}
                        />
                        <InputError message={form.errors.description} />
                    </div>
                    {bases && (
                        <>
                            <div className="flex flex-col gap-1.5">
                                <Label htmlFor={`${id}-base`}>{t('roles.field.based_on')}</Label>
                                <NativeSelect
                                    id={`${id}-base`}
                                    value={form.data.based_on}
                                    onChange={(event) =>
                                        form.setData('based_on', event.target.value)
                                    }
                                    aria-invalid={!!form.errors.based_on}
                                    aria-describedby={`${id}-base-hint`}
                                >
                                    {bases
                                        .filter((base) => !base.locked)
                                        .map((base) => (
                                            <option key={base.name} value={base.name}>
                                                {base.label}
                                            </option>
                                        ))}
                                </NativeSelect>
                                <p id={`${id}-base-hint`} className="text-xs text-muted">
                                    {t('roles.field.based_on_hint')}
                                </p>
                                <InputError message={form.errors.based_on} />
                            </div>
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id={`${id}-copy`}
                                    checked={form.data.copy_permissions}
                                    onCheckedChange={(value) =>
                                        form.setData('copy_permissions', value === true)
                                    }
                                    aria-describedby={`${id}-copy-hint`}
                                    className="mt-0.5 size-5"
                                />
                                <div className="flex flex-col gap-0.5">
                                    <Label htmlFor={`${id}-copy`}>{t('roles.field.copy')}</Label>
                                    <p id={`${id}-copy-hint`} className="text-xs text-muted">
                                        {t('roles.field.copy_hint')}
                                    </p>
                                </div>
                            </div>
                        </>
                    )}
                    <Button type="submit" className="mt-1 min-h-11" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        {role ? t('roles.save_details') : t('roles.create')}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}
