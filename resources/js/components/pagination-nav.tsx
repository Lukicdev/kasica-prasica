import { Link, type InertiaLinkProps } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Href = NonNullable<InertiaLinkProps['href']>;

export type Pagination = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

function pageItems(current: number, last: number): Array<number | null> {
    const pages = new Set<number>([1, last]);

    for (let page = current - 1; page <= current + 1; page += 1) {
        if (page >= 1 && page <= last) {
            pages.add(page);
        }
    }

    const sorted = [...pages].sort((left, right) => left - right);
    const items: Array<number | null> = [];

    sorted.forEach((page, index) => {
        const previous = sorted[index - 1];

        if (previous !== undefined && page - previous > 1) {
            items.push(null);
        }

        items.push(page);
    });

    return items;
}

export function PaginationNav({
    pagination,
    hrefFor,
}: {
    pagination: Pagination;
    hrefFor: (page: number) => Href;
}) {
    if (
        pagination.last_page <= 1 ||
        pagination.from === null ||
        pagination.to === null
    ) {
        return null;
    }

    const items = pageItems(pagination.current_page, pagination.last_page);

    return (
        <nav
            aria-label="Pagination"
            data-test="pagination"
            className="flex flex-col gap-3 border-t border-sidebar-border/70 px-4 py-3 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
        >
            <p className="text-sm text-muted-foreground">
                {pagination.from}–{pagination.to} of {pagination.total}
            </p>
            <div className="flex flex-wrap items-center gap-1">
                {pagination.current_page > 1 ? (
                    <Button
                        asChild
                        variant="outline"
                        size="icon"
                        className="size-11 md:size-9"
                    >
                        <Link
                            href={hrefFor(pagination.current_page - 1)}
                            aria-label="Previous page"
                            data-test="previous-page"
                        >
                            <ChevronLeftIcon />
                        </Link>
                    </Button>
                ) : (
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-11 md:size-9"
                        disabled
                        aria-label="Previous page"
                    >
                        <ChevronLeftIcon />
                    </Button>
                )}
                {items.map((page, index) =>
                    page === null ? (
                        <span
                            key={`gap-${items[index - 1] ?? 'start'}`}
                            className="px-1 text-muted-foreground"
                            aria-hidden="true"
                        >
                            …
                        </span>
                    ) : (
                        <Button
                            key={page}
                            asChild
                            variant={
                                page === pagination.current_page
                                    ? 'default'
                                    : 'outline'
                            }
                            size="icon"
                            className="size-11 md:size-9"
                        >
                            <Link
                                href={hrefFor(page)}
                                aria-label={`Page ${page}`}
                                aria-current={
                                    page === pagination.current_page
                                        ? 'page'
                                        : undefined
                                }
                                data-test={`page-${page}`}
                            >
                                {page}
                            </Link>
                        </Button>
                    ),
                )}
                {pagination.current_page < pagination.last_page ? (
                    <Button
                        asChild
                        variant="outline"
                        size="icon"
                        className="size-11 md:size-9"
                    >
                        <Link
                            href={hrefFor(pagination.current_page + 1)}
                            aria-label="Next page"
                            data-test="next-page"
                        >
                            <ChevronRightIcon />
                        </Link>
                    </Button>
                ) : (
                    <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        className="size-11 md:size-9"
                        disabled
                        aria-label="Next page"
                    >
                        <ChevronRightIcon />
                    </Button>
                )}
            </div>
        </nav>
    );
}
