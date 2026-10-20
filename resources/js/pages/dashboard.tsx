import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';
import { create as createTransaction } from '@/routes/transactions';

export default function Dashboard() {
    const { auth } = usePage().props;
    const workspaceName = auth.user.workspace?.name ?? 'Household';
    const role = auth.user.is_admin
        ? `You are the admin of ${workspaceName}.`
        : `You are a member of ${workspaceName}.`;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section
                    aria-label="Workspace"
                    className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
                >
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            {workspaceName}
                        </h1>
                        <p
                            className="text-sm text-muted-foreground"
                            data-test="workspace-role"
                        >
                            {role}
                        </p>
                    </div>
                    <Button asChild className="h-11 w-full sm:w-auto">
                        <Link
                            href={createTransaction()}
                            data-test="dashboard-add-transaction"
                        >
                            Add a transaction
                        </Link>
                    </Button>
                </section>
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
