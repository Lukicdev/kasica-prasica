import { Head, Link } from '@inertiajs/react';
import { AccountBalance } from '@/components/account-balance';
import { EntityIcon } from '@/components/entity-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/accounts';
import type { AccountGroup, MoneyFigure } from '@/types/account';

export default function Accounts({
    groups,
    total,
}: {
    groups: AccountGroup[];
    total: MoneyFigure;
}) {
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
                            total follows the display currency in the header.
                        </p>
                    </div>
                    <Button asChild className="h-11 w-full sm:w-auto">
                        <Link href={create()} data-test="new-account">
                            New account
                        </Link>
                    </Button>
                </section>

                <section
                    aria-label={`Total in ${total.currency}`}
                    className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    data-test="account-total"
                >
                    <p className="text-sm text-muted-foreground">
                        Total in {total.currency}
                    </p>
                    <p className="text-2xl font-semibold tracking-tight">
                        <AccountBalance balance={total} />
                    </p>
                </section>

                {groups.length === 0 ? (
                    <section className="flex flex-col gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">No accounts yet</h2>
                        <p className="text-sm text-muted-foreground">
                            Create a cash wallet, bank account, or card so money
                            has a place to sit.
                        </p>
                    </section>
                ) : (
                    groups.map((group) => (
                        <section
                            key={group.type}
                            className="flex flex-col gap-3"
                        >
                            <h2 className="text-sm font-medium text-muted-foreground">
                                {group.label}
                            </h2>
                            <ul className="flex flex-col gap-3">
                                {group.accounts.map((account) => (
                                    <li key={account.id}>
                                        <Link
                                            href={show(account.id)}
                                            className="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-accent dark:border-sidebar-border"
                                        >
                                            <span className="flex flex-wrap items-center gap-2">
                                                <EntityIcon
                                                    name={account.icon}
                                                />
                                                <span className="font-medium">
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
                                            <AccountBalance
                                                balance={account.balance}
                                            />
                                            {account.note ? (
                                                <span className="text-sm text-muted-foreground">
                                                    {account.note}
                                                </span>
                                            ) : null}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </section>
                    ))
                )}
            </div>
        </>
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
