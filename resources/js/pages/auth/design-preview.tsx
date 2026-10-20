import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';
import design from '@/routes/design';

export default function DesignPreview() {
    const { t } = useTranslation();

    return (
        <>
            <Head title="auth layout preview" />
            <form className="flex flex-col gap-4" onSubmit={(event) => event.preventDefault()}>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="preview-email">Email</Label>
                    <Input
                        id="preview-email"
                        type="email"
                        autoComplete="off"
                        className="min-h-11"
                    />
                </div>
                <div className="flex flex-col gap-1.5">
                    <Label htmlFor="preview-password">Password</Label>
                    <Input
                        id="preview-password"
                        type="password"
                        autoComplete="off"
                        className="min-h-11"
                    />
                </div>
                <Button type="submit" className="min-h-11">
                    {t('common.sign_in')}
                </Button>
                <Link
                    href={design.index()}
                    className="text-center text-sm font-semibold text-brand hover:underline"
                >
                    {t('nav.design_system')}
                </Link>
            </form>
        </>
    );
}

DesignPreview.layout = {
    title: 'Sign in to BLUESKILLS',
    description: 'Auth layout preview',
};
