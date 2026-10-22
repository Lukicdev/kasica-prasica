import { Head, Link } from '@inertiajs/react';
import { AccountBalance } from '@/components/account-balance';
import { CategoryLabel } from '@/components/category-label';
import { EntityIcon } from '@/components/entity-icon';
import { MoneyAmount, moneyDirection } from '@/components/money-amount';
import { PaginationNav, type Pagination } from '@/components/pagination-nav';
import { TableRowLink } from '@/components/table-row-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { edit, index, reconcile, show } from '@/routes/accounts';
import { show as showTransaction } from '@/routes/transactions';
import { create as createTransfer } from '@/routes/transfers';
import type { AccountDetail, StatementLine } from '@/types/account';

export default function AccountShow({
    account,
    lines,
    pagination,
}: {
    account: AccountDetail;
    lines: StatementLine[];
    pagination: Pagination;
}) {
    const transactions = lines.filter((line) => line.kind === 'transaction');

    return (
        <>
            <Head title={account.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="flex items-center gap-2 text-lg font-semibold tracking-tight">
                            <EntityIcon name={account.icon} />
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
                                className="h-11 w-full sm:w-auto md:h-9"
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
                            className="h-11 w-full sm:w-auto md:h-9"
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
                            className="h-11 w-full sm:w-auto md:h-9"
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
                    <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <Table
                            className="min-w-[52rem]"
                            data-test="account-activity"
                        >
                            <TableCaption className="sr-only">
                                Activity for {account.name}
                            </TableCaption>
                            <TableHeader>
                                <TableRow className="group">
                                    <TableHead
                                        scope="col"
                                        className="sticky left-0 bg-background group-hover:bg-muted/50"
                                    >
                                        Date
                                    </TableHead>
                                    <TableHead scope="col">Type</TableHead>
                                    <TableHead scope="col">Category</TableHead>
                                    <TableHead scope="col">Note</TableHead>
                                    <TableHead scope="col">Person</TableHead>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Amount
                                    </TableHead>
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Balance
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lines.map((line) => {
                                    const href =
                                        line.id === null
                                            ? null
                                            : showTransaction(line.id);

                                    return (
                                        <TableRow
                                            key={line.id ?? 'opening'}
                                            className={
                                                href
                                                    ? 'group cursor-pointer'
                                                    : 'group'
                                            }
                                            data-test="activity-row"
                                        >
                                            <TableHead
                                                scope="row"
                                                className="sticky left-0 bg-background group-hover:bg-muted/50"
                                            >
                                                {href ? (
                                                    <TableRowLink
                                                        href={href}
                                                        label={`${line.date_label}, ${line.type_label}`}
                                                    />
                                                ) : null}
                                                <time dateTime={line.date}>
                                                    {line.date_label}
                                                </time>
                                            </TableHead>
                                            <TableCell className="relative">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                {line.type_label}
                                                {line.repeats ? (
                                                    <span className="block text-xs text-muted-foreground">
                                                        Monthly
                                                    </span>
                                                ) : null}
                                            </TableCell>
                                            <TableCell className="relative whitespace-normal">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                <CategoryLabel
                                                    name={line.category}
                                                    icon={line.category_icon}
                                                    color={line.category_color}
                                                />
                                            </TableCell>
                                            <TableCell className="relative max-w-48 whitespace-normal">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                {line.note ?? '—'}
                                            </TableCell>
                                            <TableCell className="relative">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                {line.person ?? '—'}
                                            </TableCell>
                                            <TableCell className="relative text-right">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                {line.amount ? (
                                                    <MoneyAmount
                                                        formatted={
                                                            line.amount
                                                                .formatted
                                                        }
                                                        originalFormatted={
                                                            line.amount
                                                                .original_formatted
                                                        }
                                                        direction={moneyDirection(
                                                            line.type,
                                                        )}
                                                    />
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell className="relative text-right">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                <AccountBalance
                                                    balance={
                                                        line.running_balance
                                                    }
                                                />
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                        <PaginationNav
                            pagination={pagination}
                            hrefFor={(page) =>
                                show(account.id, {
                                    query: { page: page > 1 ? page : null },
                                })
                            }
                        />
                    </div>
                </section>

                {account.updated_by ? (
                    <p className="text-sm text-muted-foreground">
                        Last changed by {account.updated_by}.
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
