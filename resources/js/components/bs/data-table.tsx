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
    /**
     * Number of the first row. Paginated lists pass the paginator's `from`, so numbering
     * carries on from the previous page.
     */
    startIndex?: number;
    /** Set to false to leave out the serial number column. */
    serial?: boolean;
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
    startIndex = 1,
    serial = true,
}: DataTableProps<Row>) {
    const { t, formatNumber } = useTranslation();
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
                            {serial && (
                                <th scope="col" className="w-px py-2.5 ps-4 text-start font-medium">
                                    <abbr title={t('table.serial')} className="no-underline">
                                        #
                                    </abbr>
                                </th>
                            )}
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
                                      {serial && <td className="py-3 ps-4" />}
                                      {columns.map((column) => (
                                          <td key={column.key} className="px-4 py-3">
                                              <Skeleton className="h-4 w-3/4" />
                                          </td>
                                      ))}
                                  </tr>
                              ))
                            : rows.map((row, index) => (
                                  <tr key={rowKey(row)} className="border-t border-line align-top">
                                      {serial && (
                                          <td className="py-2.5 ps-4 whitespace-nowrap text-muted tnum">
                                              {formatNumber(startIndex + index)}
                                          </td>
                                      )}
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
                    : rows.map((row, index) => (
                          <li
                              key={rowKey(row)}
                              className="rounded-xl border border-line bg-surface p-4"
                          >
                              <div className="flex items-baseline gap-2 text-sm font-semibold">
                                  {serial && (
                                      <span className="shrink-0 font-normal text-muted tnum">
                                          <span className="sr-only">{t('table.serial')} </span>
                                          {formatNumber(startIndex + index)}.
                                      </span>
                                  )}
                                  <div className="min-w-0 flex-1">{primary.cell(row)}</div>
                              </div>
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
