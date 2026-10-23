import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { EntityIcon } from '@/components/entity-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/accounts';
import type {
    AccountGroup,
    AccountSummary,
    MoneyFigure,
} from '@/types/account';

export default function Accounts({
    groups,
    total,
}: {
    groups: AccountGroup[];
    total: MoneyFigure;
}) {
    const accounts = groups.flatMap((group) => group.accounts);

    return (
        <>
            <Head title="Accounts" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Accounts
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Each balance is in that account&apos;s currency. The
                            total follows the display currency in the totals
                            section.
                        </p>
                    </div>
                    <Button asChild className="h-11 w-full sm:w-auto md:h-9">
                        <Link href={create()} data-test="new-account">
                            <Plus data-icon="inline-start" />
                            New account
                        </Link>
                    </Button>
                </section>
                <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {accounts.length === 0 ? (
                        <div className="flex flex-col gap-1 px-4 py-6">
                            <h2 className="font-medium">No accounts yet</h2>
                            <p className="text-sm text-muted-foreground">
                                Create a cash wallet, bank account, or card so
                                money has a place to sit.
                            </p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            {accounts.map((account) => (
                                <AccountRow
                                    key={account.id}
                                    account={account}
                                />
                            ))}
                        </ul>
                    )}

                    <div
                        className="flex items-baseline justify-between gap-3 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                        data-test="account-total"
                    >
                        <span className="text-sm text-muted-foreground">
                            Total in {total.currency}
                        </span>
                        <span
                            className="font-medium tabular-nums"
                            data-test="account-balance"
                        >
                            {total.formatted}
                        </span>
                    </div>
                </section>
            </div>
        </>
    );
}

function AccountRow({ account }: { account: AccountSummary }) {
    return (
        <li>
            <Link
                href={show(account.id)}
                className="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
            >
                <EntityIcon name={account.icon} />
                <span className="flex min-w-0 flex-1 flex-col">
                    <span className="flex min-w-0 items-center gap-2">
                        <span className="truncate font-medium">
                            {account.name}
                        </span>
                        {account.archived ? (
                            <Badge
                                variant="outline"
                                data-test="archived-account"
                            >
                                Archived
                            </Badge>
                        ) : null}
                    </span>
                    <span className="truncate text-sm text-muted-foreground">
                        {account.note
                            ? `${account.type_label} · ${account.note}`
                            : account.type_label}
                    </span>
                </span>
                <span className="flex shrink-0 flex-col items-end">
                    <span
                        className="font-medium tabular-nums"
                        data-test="account-balance"
                    >
                        {account.balance.formatted}
                    </span>
                    {account.display_balance ? (
                        <span className="text-sm text-muted-foreground tabular-nums">
                            {account.display_balance.formatted}
                        </span>
                    ) : null}
                </span>
            </Link>
        </li>
    );
}

Accounts.layout = {
    breadcrumbs: [
        {
            title: 'Accounts',
            href: index(),
        },
    ],
};
