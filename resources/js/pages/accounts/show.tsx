import { Head, Link } from '@inertiajs/react';
import { AccountBalance } from '@/components/account-balance';
import { MoneyAmount } from '@/components/money-amount';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit, index, reconcile, show } from '@/routes/accounts';
import { create as createTransfer } from '@/routes/transfers';
import type { AccountDetail, StatementLine } from '@/types/account';

export default function AccountShow({
    account,
    lines,
}: {
    account: AccountDetail;
    lines: StatementLine[];
}) {
    const transactions = lines.filter((line) => line.kind === 'transaction');

    return (
        <>
            <Head title={account.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            {account.name}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <span>{account.type_label}</span>
                            <span aria-hidden="true">·</span>
                            <span>{account.currency}</span>
                            {account.archived ? (
                                <Badge variant="outline">Archived</Badge>
                            ) : null}
                        </div>
                        {account.note ? (
                            <p className="text-sm text-muted-foreground">
                                {account.note}
                            </p>
                        ) : null}
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        {account.can_transfer ? (
                            <Button
                                asChild
                                variant="outline"
                                className="h-11 w-full sm:w-auto"
                            >
                                <Link
                                    href={createTransfer({
                                        query: { from: account.id },
                                    })}
                                >
                                    Transfer
                                </Link>
                            </Button>
                        ) : null}
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 w-full sm:w-auto"
                        >
                            <Link
                                href={reconcile(account.id)}
                                data-test="reconcile-account"
                            >
                                Reconcile
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 w-full sm:w-auto"
                        >
                            <Link href={edit(account.id)}>Edit</Link>
                        </Button>
                    </div>
                </section>

                <section
                    aria-label="Current balance"
                    className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p className="text-sm text-muted-foreground">
                        Current balance
                    </p>
                    <p className="text-2xl font-semibold tracking-tight">
                        <AccountBalance balance={account.balance} />
                    </p>
                </section>

                <section className="flex flex-col gap-3" aria-label="Activity">
                    <h2 className="font-medium">Activity</h2>
                    {transactions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No transactions yet. The balance is the opening
                            balance.
                        </p>
                    ) : null}
                    <ol className="flex flex-col gap-3">
                        {lines.map((line) => (
                            <li
                                key={line.id ?? 'opening'}
                                className="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                            >
                                <div className="flex flex-wrap items-baseline justify-between gap-2">
                                    <span className="font-medium">
                                        {line.type_label}
                                    </span>
                                    <time
                                        dateTime={line.date}
                                        className="text-sm text-muted-foreground"
                                    >
                                        {line.date_label}
                                    </time>
                                </div>
                                {line.note ? <p>{line.note}</p> : null}
                                {line.person ? (
                                    <p className="text-sm text-muted-foreground">
                                        {line.person}
                                    </p>
                                ) : null}
                                {line.amount ? (
                                    <p className="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                        <span className="text-muted-foreground">
                                            Amount
                                        </span>
                                        <MoneyAmount
                                            formatted={line.amount.formatted}
                                            originalFormatted={
                                                line.amount.original_formatted
                                            }
                                        />
                                    </p>
                                ) : null}
                                <p className="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <span className="text-muted-foreground">
                                        Balance
                                    </span>
                                    <AccountBalance
                                        balance={line.running_balance}
                                    />
                                </p>
                            </li>
                        ))}
                    </ol>
                </section>

                {account.created_by || account.updated_by ? (
                    <p className="text-sm text-muted-foreground">
                        {account.created_by
                            ? `Added by ${account.created_by}.`
                            : null}{' '}
                        {account.updated_by
                            ? `Last changed by ${account.updated_by}.`
                            : null}
                    </p>
                ) : null}
            </div>
        </>
    );
}

AccountShow.layout = (props: {
    account: AccountDetail;
}): { breadcrumbs: { title: string; href: ReturnType<typeof show> }[] } => ({
    breadcrumbs: [
        { title: 'Accounts', href: index() },
        { title: props.account.name, href: show(props.account.id) },
    ],
});
