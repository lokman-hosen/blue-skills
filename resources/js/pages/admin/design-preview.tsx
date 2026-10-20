import { DesignPreviewContent } from '@/components/design-preview-content';

export default function DesignPreview({ layout }: { layout: string }) {
    return <DesignPreviewContent layout={layout} />;
}
