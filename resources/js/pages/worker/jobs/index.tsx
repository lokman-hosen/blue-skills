import { JobSearch } from '@/components/bs/jobs/job-search';
import type { JobSearchProps } from '@/components/bs/jobs/job-search';

/** Find Jobs (spec §14). */
export default function JobsIndex(props: JobSearchProps) {
    return <JobSearch {...props} />;
}
