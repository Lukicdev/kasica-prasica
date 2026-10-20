import { Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { CurrencySwitch } from '@/components/currency-switch';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { create as createTransaction } from '@/routes/transactions';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex min-w-0 flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <Button asChild className="h-11">
                    <Link
                        href={createTransaction()}
                        data-test="add-transaction"
                    >
                        <Plus />
                        <span className="hidden sm:inline">Add</span>
                        <span className="sr-only sm:hidden">
                            Add a transaction
                        </span>
                    </Link>
                </Button>
                <CurrencySwitch className="shrink-0" />
            </div>
        </header>
    );
}
