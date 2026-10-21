import { Link, router, type InertiaLinkProps } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { MonthPicker } from '@/components/month-picker';
import { Button } from '@/components/ui/button';
import { toUrl } from '@/lib/utils';

type Href = NonNullable<InertiaLinkProps['href']>;

export function MonthNav({
    value,
    previousHref,
    nextHref,
    hrefFor,
}: {
    value: string;
    previousHref: Href;
    nextHref: Href;
    hrefFor: (month: string) => Href;
}) {
    return (
        <nav
            aria-label="Month"
            className="inline-flex w-fit items-center gap-1 rounded-full border border-input bg-background p-1 shadow-xs dark:bg-input/30"
        >
            <Button
                asChild
                variant="outline"
                size="icon"
                className="size-9 rounded-full"
            >
                <Link
                    href={previousHref}
                    aria-label="Previous month"
                    data-test="previous-month"
                >
                    <ChevronLeftIcon />
                </Link>
            </Button>
            <MonthPicker
                id="month"
                name="month"
                value={value}
                icon={false}
                variant="ghost"
                align="center"
                className="h-9 rounded-full px-3"
                onChange={(month) => {
                    if (month !== value) {
                        router.visit(toUrl(hrefFor(month)));
                    }
                }}
            />
            <Button
                asChild
                variant="outline"
                size="icon"
                className="size-9 rounded-full"
            >
                <Link
                    href={nextHref}
                    aria-label="Next month"
                    data-test="next-month"
                >
                    <ChevronRightIcon />
                </Link>
            </Button>
        </nav>
    );
}
