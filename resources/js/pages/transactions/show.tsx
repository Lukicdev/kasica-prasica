import { Head, Link, router } from '@inertiajs/react';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import { AccountBalance } from '@/components/account-balance';
import { MoneyAmount } from '@/components/money-amount';
import { Button } from '@/components/ui/button';
import { create, edit, index, show } from '@/routes/transactions';
import type { TransactionDetail } from '@/types/transaction';

export default function TransactionShow({
    transaction,
}: {
    transaction: TransactionDetail;
}) {
    const remove = (): void => {
        if (!confirm('Delete this transaction? This cannot be undone.')) {
            return;
        }

        router.delete(TransactionController.destroy.url(transaction.id));
    };

    return (
        <>
            <Head title={transaction.type_label} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            {transaction.type_label}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            <time dateTime={transaction.date}>
                                {transaction.date_label}
                            </time>
                            {transaction.category
                                ? ` · ${transaction.category}`
                                : ''}
                        </p>
                        {transaction.note ? <p>{transaction.note}</p> : null}
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full sm:w-auto"
                    >
                        <Link href={edit(transaction.id)}>Edit</Link>
                    </Button>
                </section>

                <section
                    aria-label={`Balance of ${transaction.account.name}`}
                    className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    data-test="account-balance-confirmation"
                >
                    <p className="text-sm text-muted-foreground">
                        Balance of {transaction.account.name}
                    </p>
                    <p className="text-2xl font-semibold tracking-tight">
                        <AccountBalance balance={transaction.balance} />
                    </p>
                    <p className="text-sm text-muted-foreground">
                        This transaction{' '}
                        {transaction.type === 'income'
                            ? 'added'
                            : transaction.refund
                              ? 'returned'
                              : 'took'}{' '}
                        {transaction.account_amount.formatted}.
                    </p>
                </section>

                <dl className="flex flex-col gap-3 text-sm">
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <dt className="text-muted-foreground">Amount</dt>
                        <dd>
                            <MoneyAmount
                                formatted={transaction.amount.formatted}
                                originalFormatted={
                                    transaction.amount.original_formatted
                                }
                            />
                        </dd>
                    </div>
                    <div className="flex flex-wrap items-baseline justify-between gap-2">
                        <dt className="text-muted-foreground">Account</dt>
                        <dd>{transaction.account.name}</dd>
                    </div>
                    {transaction.person ? (
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <dt className="text-muted-foreground">Person</dt>
                            <dd>{transaction.person}</dd>
                        </div>
                    ) : null}
                    {transaction.added_by ? (
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <dt className="text-muted-foreground">Added by</dt>
                            <dd data-test="added-by">{transaction.added_by}</dd>
                        </div>
                    ) : null}
                    {transaction.updated_by ? (
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <dt className="text-muted-foreground">
                                Last changed by
                            </dt>
                            <dd>{transaction.updated_by}</dd>
                        </div>
                    ) : null}
                </dl>

                <div className="flex flex-col gap-3 sm:flex-row">
                    <Button asChild className="h-11 sm:flex-1">
                        <Link
                            href={create({
                                query: {
                                    type: transaction.type,
                                    account: transaction.account.id,
                                    date: transaction.date,
                                },
                            })}
                            data-test="add-another"
                        >
                            Add another
                        </Link>
                    </Button>
                    <Button
                        type="button"
                        variant="destructive"
                        className="h-11 sm:flex-1"
                        onClick={remove}
                        data-test="delete-transaction"
                    >
                        Delete
                    </Button>
                </div>
            </div>
        </>
    );
}

TransactionShow.layout = (props: {
    transaction: TransactionDetail;
}): { breadcrumbs: { title: string; href: ReturnType<typeof show> }[] } => ({
    breadcrumbs: [
        { title: 'Transactions', href: index() },
        {
            title: props.transaction.type_label,
            href: show(props.transaction.id),
        },
    ],
});
