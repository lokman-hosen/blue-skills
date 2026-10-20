import type { ReactNode } from 'react';
import { EmptyState } from './empty-state';
import { ErrorState } from './error-state';
import { Skeleton } from '@/components/ui/skeleton';
import { useTranslation } from '@/lib/i18n';
import { cn } from '@/lib/utils';

export type DataTableColumn<Row> = {
    key: string;
    header: string;
    cell: (row: Row) => ReactNode;
    align?: 'start' | 'end';
    /** The column used as the card title below `md`. Defaults to the first column. */
    primary?: boolean;
    className?: string;
};

type DataTableProps<Row> = {
    columns: DataTableColumn<Row>[];
    rows: Row[];
    rowKey: (row: Row) => string;
    /** Visually hidden caption that names the table for screen readers. */
    caption: string;
    loading?: boolean;
    error?: { message?: string; onRetry?: () => void } | null;
    empty?: ReactNode;
    className?: string;
};

/**
 * A table on `md` and up; stacked cards on smaller screens (CLAUDE.md mobile rules).
 * Covers loading, empty and error states.
 */
export function DataTable<Row>({
    columns,
    rows,
    rowKey,
    caption,
    loading = false,
    error = null,
    empty,
    className,
}: DataTableProps<Row>) {
    const { t } = useTranslation();
    const primary = columns.find((column) => column.primary) ?? columns[0];
    const secondary = columns.filter((column) => column !== primary);

    if (error) {
        return (
            <ErrorState description={error.message} onRetry={error.onRetry} className={className} />
        );
    }

    if (!loading && rows.length === 0) {
        return <div className={className}>{empty ?? <EmptyState title={t('table.empty')} />}</div>;
    }

    return (
        <div className={cn('min-w-0', className)} aria-busy={loading}>
            <div className="hidden overflow-x-auto rounded-xl border border-line bg-surface md:block">
                <table className="w-full text-[13px]">
                    <caption className="sr-only">{caption}</caption>
                    <thead className="text-muted">
                        <tr>
                            {columns.map((column) => (
                                <th
                                    key={column.key}
                                    scope="col"
                                    className={cn(
                                        'px-4 py-2.5 font-medium',
                                        column.align === 'end' ? 'text-end' : 'text-start',
                                    )}
                                >
                                    {column.header}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {loading
                            ? Array.from({ length: 3 }, (_, index) => (
                                  <tr key={index} className="border-t border-line">
                                      {columns.map((column) => (
                                          <td key={column.key} className="px-4 py-3">
                                              <Skeleton className="h-4 w-3/4" />
                                          </td>
                                      ))}
                                  </tr>
                              ))
                            : rows.map((row) => (
                                  <tr key={rowKey(row)} className="border-t border-line align-top">
                                      {columns.map((column) => (
                                          <td
                                              key={column.key}
                                              className={cn(
                                                  'px-4 py-2.5',
                                                  column.align === 'end' && 'text-end tnum',
                                                  column.className,
                                              )}
                                          >
                                              {column.cell(row)}
                                          </td>
                                      ))}
                                  </tr>
                              ))}
                    </tbody>
                </table>
            </div>

            <ul className="flex flex-col gap-2 md:hidden" aria-label={caption}>
                {loading
                    ? Array.from({ length: 3 }, (_, index) => (
                          <li key={index} className="rounded-xl border border-line bg-surface p-4">
                              <Skeleton className="mb-3 h-4 w-1/2" />
                              <Skeleton className="h-3 w-3/4" />
                          </li>
                      ))
                    : rows.map((row) => (
                          <li
                              key={rowKey(row)}
                              className="rounded-xl border border-line bg-surface p-4"
                          >
                              <div className="text-sm font-semibold">{primary.cell(row)}</div>
                              <dl className="mt-2 flex flex-col gap-1.5">
                                  {secondary.map((column) => (
                                      <div
                                          key={column.key}
                                          className="flex items-start justify-between gap-3 text-[13px]"
                                      >
                                          <dt className="shrink-0 text-muted">{column.header}</dt>
                                          <dd className="min-w-0 text-end">{column.cell(row)}</dd>
                                      </div>
                                  ))}
                              </dl>
                          </li>
                      ))}
            </ul>
        </div>
    );
}
