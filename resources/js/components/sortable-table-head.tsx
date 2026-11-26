import { Link, type InertiaLinkProps } from '@inertiajs/react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { TableHead } from '@/components/ui/table';

export type SortDirection = 'asc' | 'desc';

export type SortState<Column extends string> = {
    column: Column;
    direction: SortDirection;
};

/**
 * The direction a column title link switches to: the opposite of the current
 * one for the active column, otherwise newest or largest first for dates and
 * amounts and A to Z for the rest.
 */
export function nextSortDirection<Column extends string>(
    sort: SortState<Column>,
    column: Column,
): SortDirection {
    if (sort.column !== column) {
        return column === 'date' || column === 'amount' ? 'desc' : 'asc';
    }

    return sort.direction === 'asc' ? 'desc' : 'asc';
}

export function SortableTableHead<Column extends string>({
    column,
    label,
    sort,
    href,
    className,
}: {
    column: Column;
    label: string;
    sort: SortState<Column>;
    href: NonNullable<InertiaLinkProps['href']>;
    className?: string;
}) {
    const active = sort.column === column;

    return (
        <TableHead
            scope="col"
            aria-sort={
                active
                    ? sort.direction === 'asc'
                        ? 'ascending'
                        : 'descending'
                    : 'none'
            }
            className={className}
        >
            <Link
                href={href}
                className="inline-flex min-h-11 items-center gap-1"
                data-test={`sort-${column}`}
            >
                {label}
                {active ? (
                    sort.direction === 'asc' ? (
                        <ArrowUp className="size-4" aria-hidden="true" />
                    ) : (
                        <ArrowDown className="size-4" aria-hidden="true" />
                    )
                ) : null}
                <span className="sr-only">
                    {active
                        ? sort.direction === 'asc'
                            ? ', sorted ascending'
                            : ', sorted descending'
                        : ', sort'}
                </span>
            </Link>
        </TableHead>
    );
}
