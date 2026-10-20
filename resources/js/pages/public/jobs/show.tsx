import { JobDetail } from '@/components/bs/jobs/job-detail';
import type { JobDetailProps } from '@/components/bs/jobs/job-detail';

/** The Job Truth Card page (spec §5). */
export default function JobsShow(props: JobDetailProps) {
    return <JobDetail {...props} />;
}
