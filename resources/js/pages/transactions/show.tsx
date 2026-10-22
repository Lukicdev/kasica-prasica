import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import { AccountBalance } from '@/components/account-balance';
import { AccountLabel } from '@/components/account-label';
import { MoneyAmount } from '@/components/money-amount';
import { Button } from '@/components/ui/button';
import { edit as editAdjustment } from '@/routes/adjustments';
import { create, edit, index, show } from '@/routes/transactions';
import {
    create as createTransfer,
    edit as editTransfer,
} from '@/routes/transfers';
import type { TransactionDetail } from '@/types/transaction';

export default function TransactionShow({
    transaction,
}: {
    transaction: TransactionDetail;
}) {
    const editHref =
        transaction.type === 'transfer'
            ? editTransfer(transaction.id)
            : transaction.type === 'adjustment'
              ? editAdjustment(transaction.id)
              : edit(transaction.id);
    const anotherHref =
        transaction.type === 'transfer' && transaction.destination
            ? createTransfer({
                  query: {
                      from: transaction.account.id,
                      to: transaction.destination.id,
                      date: transaction.date,
                  },
              })
            : transaction.type === 'income' || transaction.type === 'expense'
              ? create({
                    query: {
                        type: transaction.type,
                        account: transaction.account.id,
                        date: transaction.date,
                    },
                })
              : null;

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
                        {transaction.repeats ? (
                            <p data-test="repeats-monthly">
                                Repeats every month. Later months stay pending
                                until confirmed.
                            </p>
                        ) : transaction.from_recurrence ? (
                            <p>Confirmed from a monthly repeat.</p>
                        ) : null}
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full sm:w-auto md:h-9"
                    >
                        <Link href={editHref}>Edit</Link>
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
                        {transaction.type === 'transfer' &&
                        transaction.destination &&
                        transaction.destination_amount
                            ? `This transfer moved ${transaction.account_amount.formatted} from ${transaction.account.name}. ${transaction.destination.name} received ${transaction.destination_amount.formatted}. It does not count as income or spending.`
                            : transaction.type === 'adjustment'
                              ? `This balancing entry changed ${transaction.account.name} by ${transaction.account_amount.formatted}. It is left out of income, spending, and the budget.`
                              : transaction.type === 'income'
                                ? `This transaction added ${transaction.account_amount.formatted}.`
                                : transaction.refund
                                  ? `This transaction returned ${transaction.account_amount.formatted}.`
                                  : `This transaction took ${transaction.account_amount.formatted}.`}
                    </p>
                </section>

                {transaction.destination && transaction.destination_balance ? (
                    <section
                        aria-label={`Balance of ${transaction.destination.name}`}
                        className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        data-test="destination-balance"
                    >
                        <p className="text-sm text-muted-foreground">
                            Balance of {transaction.destination.name}
                        </p>
                        <p className="text-2xl font-semibold tracking-tight">
                            <AccountBalance
                                balance={transaction.destination_balance}
                            />
                        </p>
                    </section>
                ) : null}

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
                    {transaction.destination ? (
                        <>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <dt className="text-muted-foreground">From</dt>
                                <dd>
                                    <AccountLabel
                                        name={transaction.account.name}
                                        icon={transaction.account.icon}
                                    />
                                </dd>
                            </div>
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <dt className="text-muted-foreground">To</dt>
                                <dd>
                                    <AccountLabel
                                        name={transaction.destination.name}
                                        icon={transaction.destination.icon}
                                    />
                                </dd>
                            </div>
                        </>
                    ) : (
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <dt className="text-muted-foreground">Account</dt>
                            <dd>
                                <AccountLabel
                                    name={transaction.account.name}
                                    icon={transaction.account.icon}
                                />
                            </dd>
                        </div>
                    )}
                    {transaction.person ? (
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <dt className="text-muted-foreground">Person</dt>
                            <dd>{transaction.person}</dd>
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
                    {anotherHref ? (
                        <Button asChild className="h-11 sm:flex-1 md:h-9">
                            <Link href={anotherHref} data-test="add-another">
                                <Plus data-icon="inline-start" />
                                Add another
                            </Link>
                        </Button>
                    ) : null}
                    <Button
                        type="button"
                        variant="destructive"
                        className="h-11 sm:flex-1 md:h-9"
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
