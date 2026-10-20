import { Form, Head, Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Minus } from 'lucide-react';
import { AccountBalance } from '@/components/account-balance';
import { MoneyAmount } from '@/components/money-amount';
import { PendingRecurrences } from '@/components/pending-recurrences';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import {
    create as createAccount,
    index as accounts,
    show as showAccount,
} from '@/routes/accounts';
import { index as budget } from '@/routes/budget';
import {
    create as createTransaction,
    show as showTransaction,
} from '@/routes/transactions';
import type { PendingRecurrence } from '@/types/recurrence';
import type {
    OverviewAccounts,
    OverviewDrilldown,
    OverviewExpenseRow,
    OverviewExpenseTotal,
    OverviewFigure,
    OverviewIncomeRow,
    OverviewMonth,
    OverviewStanding,
    OverviewStep,
    OverviewTotals,
} from '@/types/overview';

const steps: Record<
    OverviewStep['key'],
    { title: string; body: string; action: string }
> = {
    account: {
        title: 'Create an account',
        body: 'Add cash, a bank account, or a card. Its balance updates from transactions.',
        action: 'Create an account',
    },
    budget: {
        title: 'Set a budget',
        body: 'Give each expense category an amount in dinars for this month.',
        action: 'Set the budget',
    },
    transaction: {
        title: 'Add a transaction',
        body: 'Record income or something you just paid.',
        action: 'Add a transaction',
    },
};

function monthQuery(month: string, category: number | null) {
    return {
        month,
        category,
    };
}

function addTransactionHref(categoryId: number, entryDate: string) {
    return createTransaction({
        query: {
            category: categoryId,
            date: entryDate,
        },
    });
}

export default function Overview({
    month,
    first_run,
    recorded,
    budget_started,
    steps: progress,
    income,
    expenses,
    totals,
    accounts: accountPanel,
    drilldown,
    pending,
}: {
    month: OverviewMonth;
    first_run: boolean;
    recorded: boolean;
    budget_started: boolean;
    steps: OverviewStep[];
    income: { rows: OverviewIncomeRow[]; total: OverviewFigure };
    expenses: { rows: OverviewExpenseRow[]; total: OverviewExpenseTotal };
    totals: OverviewTotals;
    accounts: OverviewAccounts;
    drilldown: OverviewDrilldown | null;
    pending: PendingRecurrence[];
}) {
    const accountReady =
        progress.find((step) => step.key === 'account')?.done ?? false;

    return (
        <>
            <Head
                title={drilldown ? `${drilldown.name} · Overview` : 'Overview'}
            />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Overview
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {month.label}. Income, spending and the plan, in the
                            display currency from the header.
                        </p>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <Button asChild className="h-11 w-full sm:w-auto">
                            <Link
                                href={createTransaction({
                                    query: { date: month.entry_date },
                                })}
                                data-test="overview-add-transaction"
                            >
                                Add a transaction
                            </Link>
                        </Button>
                    </div>
                </section>

                <div className="flex items-center justify-between gap-3">
                    <Button asChild variant="outline" className="h-11">
                        <Link
                            href={dashboard({
                                query: monthQuery(
                                    month.previous,
                                    drilldown?.id ?? null,
                                ),
                            })}
                            data-test="previous-month"
                        >
                            Previous
                        </Link>
                    </Button>
                    <p className="text-sm font-medium">{month.label}</p>
                    <Button asChild variant="outline" className="h-11">
                        <Link
                            href={dashboard({
                                query: monthQuery(
                                    month.next,
                                    drilldown?.id ?? null,
                                ),
                            })}
                            data-test="next-month"
                        >
                            Next
                        </Link>
                    </Button>
                </div>

                <Form
                    {...dashboard.form()}
                    className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-end dark:border-sidebar-border"
                >
                    {drilldown ? (
                        <input
                            type="hidden"
                            name="category"
                            value={drilldown.id}
                        />
                    ) : null}
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="month">Month</Label>
                        <Input
                            id="month"
                            name="month"
                            type="month"
                            defaultValue={month.value}
                            className="h-11 md:h-9"
                        />
                    </div>
                    <Button type="submit" variant="outline" className="h-11">
                        Show month
                    </Button>
                </Form>

                <PendingRecurrences items={pending} />

                {first_run ? (
                    <FirstRun
                        month={month}
                        progress={progress}
                        accountReady={accountReady}
                    />
                ) : (
                    <>
                        <Totals totals={totals} />
                        {drilldown ? (
                            <Drilldown month={month} drilldown={drilldown} />
                        ) : (
                            <>
                                {!recorded ? (
                                    <p className="text-sm text-muted-foreground">
                                        No transactions in {month.label}.
                                    </p>
                                ) : null}
                                {!budget_started ? (
                                    <p className="text-sm text-muted-foreground">
                                        This month has no budget yet.{' '}
                                        <Link
                                            href={budget({
                                                query: { month: month.value },
                                            })}
                                            className="underline-offset-4 hover:underline"
                                        >
                                            Set one
                                        </Link>
                                        .
                                    </p>
                                ) : null}
                                <IncomeBlock
                                    month={month}
                                    rows={income.rows}
                                    total={income.total}
                                />
                                <ExpenseTable
                                    month={month}
                                    rows={expenses.rows}
                                    total={expenses.total}
                                />
                            </>
                        )}
                    </>
                )}

                <AccountsPanel panel={accountPanel} />
            </div>
        </>
    );
}

function FirstRun({
    month,
    progress,
    accountReady,
}: {
    month: OverviewMonth;
    progress: OverviewStep[];
    accountReady: boolean;
}) {
    return (
        <section
            className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test="first-run"
        >
            <div className="flex flex-col gap-1">
                <h2 className="font-medium">Nothing recorded yet</h2>
                <p className="text-sm text-muted-foreground">
                    Three steps get {month.label} started. You can do them in
                    any order, but a transaction needs an account.
                </p>
            </div>
            <ol className="flex flex-col gap-3">
                {progress.map((step, index) => {
                    const copy = steps[step.key];
                    const href =
                        step.key === 'account'
                            ? createAccount()
                            : step.key === 'budget'
                              ? budget({ query: { month: month.value } })
                              : accountReady
                                ? createTransaction({
                                      query: { date: month.entry_date },
                                  })
                                : createAccount();
                    const action =
                        step.key === 'transaction' && !accountReady
                            ? 'Create an account first'
                            : copy.action;

                    return (
                        <li
                            key={step.key}
                            className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border"
                            data-test={`first-run-${step.key}`}
                        >
                            <div className="flex flex-col gap-1">
                                <span className="font-medium">
                                    {index + 1}. {copy.title}
                                </span>
                                <span className="text-sm text-muted-foreground">
                                    {copy.body}
                                </span>
                                <span className="text-sm">
                                    {step.done ? 'Done' : 'Not done yet'}
                                </span>
                            </div>
                            {step.done ? null : (
                                <Button
                                    asChild
                                    className="h-11 w-full sm:w-auto"
                                >
                                    <Link href={href}>{action}</Link>
                                </Button>
                            )}
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}

function Totals({ totals }: { totals: OverviewTotals }) {
    return (
        <section
            aria-label="Month totals"
            className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
        >
            <TotalCard
                testId="overview-income"
                label="Income"
                figure={totals.income}
                caption="Income recorded this month."
            />
            <TotalCard
                testId="overview-spent"
                label="Spent"
                figure={totals.spent}
                caption="Expenses recorded this month."
            />
            <TotalCard
                testId="overview-budget"
                label="Budget"
                figure={totals.budget}
                caption="Sum of the category budgets."
            />
            <TotalCard
                testId="overview-difference"
                label="Difference"
                figure={totals.difference}
                caption="Spent minus budget. Positive means over budget."
            />
            <TotalCard
                testId="overview-leftover"
                label="Leftover"
                figure={totals.leftover}
                caption="Income minus spent."
                markNegative
            />
            <TotalCard
                testId="overview-unallocated"
                label="Unallocated"
                figure={totals.unallocated}
                caption="Income minus the budget."
                markNegative
            />
        </section>
    );
}

function TotalCard({
    testId,
    label,
    figure,
    caption,
    markNegative = false,
}: {
    testId: string;
    label: string;
    figure: OverviewFigure;
    caption: string;
    markNegative?: boolean;
}) {
    return (
        <div
            className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test={testId}
        >
            <span className="text-sm text-muted-foreground">{label}</span>
            <span className="text-lg font-semibold tracking-tight">
                <MoneyAmount
                    formatted={figure.formatted}
                    originalFormatted={figure.original_formatted}
                />
            </span>
            {markNegative && figure.negative ? (
                <Badge variant="outline">Negative</Badge>
            ) : null}
            <span className="text-sm text-muted-foreground">{caption}</span>
        </div>
    );
}

function IncomeBlock({
    month,
    rows,
    total,
}: {
    month: OverviewMonth;
    rows: OverviewIncomeRow[];
    total: OverviewFigure;
}) {
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-sm font-medium text-muted-foreground">
                Income
            </h2>
            <ul
                className="flex flex-col gap-3"
                aria-label={`Income in ${month.label}`}
            >
                {rows.map((row) => (
                    <li
                        key={row.id ?? row.name}
                        className="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        data-test="income-row"
                    >
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                            <div className="flex flex-col gap-1">
                                <CategoryName row={row} month={month} />
                                {row.description ? (
                                    <span className="text-sm text-muted-foreground">
                                        {row.description}
                                    </span>
                                ) : null}
                            </div>
                            <span className="font-medium">
                                <MoneyAmount
                                    formatted={row.amount.formatted}
                                    originalFormatted={
                                        row.amount.original_formatted
                                    }
                                />
                            </span>
                        </div>
                        {row.people.length > 0 ? (
                            <ul className="flex flex-col gap-1 text-sm text-muted-foreground">
                                {row.people.map((person) => (
                                    <li
                                        key={`${row.id ?? 'none'}-${person.id ?? person.name}`}
                                    >
                                        {person.name}: {person.amount.formatted}
                                    </li>
                                ))}
                            </ul>
                        ) : null}
                        {row.id !== null ? (
                            <AddLink
                                categoryId={row.id}
                                name={row.name}
                                entryDate={month.entry_date}
                            />
                        ) : null}
                    </li>
                ))}
            </ul>
            <p
                className="flex items-center justify-between gap-3 rounded-xl border border-sidebar-border/70 px-4 py-3 font-medium dark:border-sidebar-border"
                data-test="income-total"
            >
                <span>Total income</span>
                <MoneyAmount
                    formatted={total.formatted}
                    originalFormatted={total.original_formatted}
                />
            </p>
        </section>
    );
}

function ExpenseTable({
    month,
    rows,
    total,
}: {
    month: OverviewMonth;
    rows: OverviewExpenseRow[];
    total: OverviewExpenseTotal;
}) {
    return (
        <section className="flex flex-col gap-3">
            <h2 className="text-sm font-medium text-muted-foreground">
                Expenses
            </h2>
            <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table
                    className="w-full min-w-[44rem] border-collapse text-left text-sm"
                    data-test="expense-table"
                >
                    <caption className="sr-only">
                        Expenses for {month.label}. Difference is spent minus
                        budget.
                    </caption>
                    <thead className="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border">
                        <tr>
                            <th
                                scope="col"
                                className="sticky left-0 bg-background px-3 py-3 font-medium"
                            >
                                Category
                            </th>
                            <th
                                scope="col"
                                className="px-3 py-3 text-right font-medium"
                            >
                                Spent
                            </th>
                            <th
                                scope="col"
                                className="px-3 py-3 text-right font-medium"
                            >
                                Budget
                            </th>
                            <th
                                scope="col"
                                className="px-3 py-3 text-right font-medium"
                            >
                                Difference
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr
                                key={row.id ?? row.name}
                                className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                                data-test="expense-row"
                            >
                                <th
                                    scope="row"
                                    className="sticky left-0 bg-background px-3 py-3 font-medium"
                                >
                                    <div className="flex flex-col items-start gap-1">
                                        <CategoryName row={row} month={month} />
                                        {row.description ? (
                                            <span className="text-sm font-normal text-muted-foreground">
                                                {row.description}
                                            </span>
                                        ) : null}
                                        {row.id !== null ? (
                                            <AddLink
                                                categoryId={row.id}
                                                name={row.name}
                                                entryDate={month.entry_date}
                                            />
                                        ) : null}
                                    </div>
                                </th>
                                <td className="px-3 py-3 text-right whitespace-nowrap">
                                    <MoneyAmount
                                        formatted={row.spent.formatted}
                                        originalFormatted={
                                            row.spent.original_formatted
                                        }
                                    />
                                </td>
                                <td className="px-3 py-3 text-right whitespace-nowrap">
                                    <MoneyAmount
                                        formatted={row.budget.formatted}
                                        originalFormatted={
                                            row.budget.original_formatted
                                        }
                                    />
                                </td>
                                <td className="px-3 py-3 text-right">
                                    <div className="flex flex-col items-end gap-1">
                                        <MoneyAmount
                                            formatted={row.difference.formatted}
                                            originalFormatted={
                                                row.difference
                                                    .original_formatted
                                            }
                                        />
                                        <Standing
                                            status={row.status}
                                            label={row.status_label}
                                        />
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot className="border-t border-sidebar-border/70 font-medium dark:border-sidebar-border">
                        <tr data-test="expense-total">
                            <th
                                scope="row"
                                className="sticky left-0 bg-background px-3 py-3"
                            >
                                Total
                            </th>
                            <td className="px-3 py-3 text-right whitespace-nowrap">
                                <MoneyAmount
                                    formatted={total.spent.formatted}
                                    originalFormatted={
                                        total.spent.original_formatted
                                    }
                                />
                            </td>
                            <td className="px-3 py-3 text-right whitespace-nowrap">
                                <MoneyAmount
                                    formatted={total.budget.formatted}
                                    originalFormatted={
                                        total.budget.original_formatted
                                    }
                                />
                            </td>
                            <td className="px-3 py-3 text-right">
                                <div className="flex flex-col items-end gap-1">
                                    <MoneyAmount
                                        formatted={total.difference.formatted}
                                        originalFormatted={
                                            total.difference.original_formatted
                                        }
                                    />
                                    <Standing
                                        status={total.status}
                                        label={total.status_label}
                                    />
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    );
}

function Drilldown({
    month,
    drilldown,
}: {
    month: OverviewMonth;
    drilldown: OverviewDrilldown;
}) {
    const totalLabel = drilldown.kind === 'income' ? 'Received' : 'Spent';

    return (
        <section className="flex flex-col gap-4" data-test="drilldown">
            <div className="flex flex-col gap-3">
                <Button
                    asChild
                    variant="outline"
                    className="h-11 w-full sm:w-auto"
                >
                    <Link
                        href={dashboard({ query: { month: month.value } })}
                        data-test="back-to-overview"
                    >
                        Back to {month.label}
                    </Link>
                </Button>
                <div className="flex flex-col gap-1">
                    <h2 className="text-lg font-semibold tracking-tight">
                        {drilldown.name}
                    </h2>
                    {drilldown.description ? (
                        <p className="text-sm text-muted-foreground">
                            {drilldown.description}
                        </p>
                    ) : null}
                    <p className="text-sm text-muted-foreground">
                        {drilldown.transactions.length === 1
                            ? '1 transaction'
                            : `${drilldown.transactions.length} transactions`}{' '}
                        add up to this {totalLabel.toLowerCase()} figure.
                    </p>
                </div>
            </div>

            <div className="grid gap-3 sm:grid-cols-3">
                <TotalCard
                    testId="drilldown-total"
                    label={totalLabel}
                    figure={drilldown.total}
                    caption="Sum of the transactions below, in the display currency."
                />
                {drilldown.budget ? (
                    <TotalCard
                        testId="drilldown-budget"
                        label="Budget"
                        figure={drilldown.budget}
                        caption="The plan for this category."
                    />
                ) : null}
                {drilldown.difference &&
                drilldown.status &&
                drilldown.status_label ? (
                    <div
                        className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        data-test="drilldown-difference"
                    >
                        <span className="text-sm text-muted-foreground">
                            Difference
                        </span>
                        <span className="text-lg font-semibold tracking-tight">
                            <MoneyAmount
                                formatted={drilldown.difference.formatted}
                                originalFormatted={
                                    drilldown.difference.original_formatted
                                }
                            />
                        </span>
                        <Standing
                            status={drilldown.status}
                            label={drilldown.status_label}
                        />
                    </div>
                ) : null}
            </div>

            {drilldown.transactions.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No transactions in {drilldown.name} for {month.label}.
                </p>
            ) : (
                <ul className="flex flex-col gap-3" aria-label={drilldown.name}>
                    {drilldown.transactions.map((transaction) => (
                        <li key={transaction.id}>
                            <Link
                                href={showTransaction(transaction.id)}
                                className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 transition-colors hover:bg-accent dark:border-sidebar-border"
                                data-test="drilldown-transaction"
                            >
                                <span className="flex flex-wrap items-baseline justify-between gap-2">
                                    <time
                                        dateTime={transaction.date}
                                        className="font-medium"
                                    >
                                        {transaction.date_label}
                                    </time>
                                    <MoneyAmount
                                        formatted={transaction.amount.formatted}
                                        originalFormatted={
                                            transaction.amount
                                                .original_formatted
                                        }
                                    />
                                </span>
                                <span className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                                    <span>
                                        {transaction.account}
                                        {transaction.person
                                            ? ` · ${transaction.person}`
                                            : ''}
                                    </span>
                                </span>
                                {transaction.note ? (
                                    <span className="text-sm">
                                        {transaction.note}
                                    </span>
                                ) : null}
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

function AccountsPanel({ panel }: { panel: OverviewAccounts }) {
    return (
        <section
            aria-label="Accounts"
            className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test="overview-accounts"
        >
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 className="font-medium">Accounts</h2>
                <Button
                    asChild
                    variant="outline"
                    className="h-11 w-full sm:w-auto"
                >
                    <Link href={accounts()} data-test="overview-all-accounts">
                        All accounts
                    </Link>
                </Button>
            </div>
            {panel.items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No accounts yet.{' '}
                    <Link
                        href={createAccount()}
                        className="underline-offset-4 hover:underline"
                    >
                        Create one
                    </Link>
                    .
                </p>
            ) : (
                <ul className="flex flex-col divide-y divide-sidebar-border/70">
                    {panel.items.map((account) => (
                        <li key={account.id}>
                            <Link
                                href={showAccount(account.id)}
                                className="flex min-h-11 flex-wrap items-center justify-between gap-2 py-3"
                            >
                                <span className="flex flex-wrap items-center gap-2">
                                    <span className="font-medium">
                                        {account.name}
                                    </span>
                                    {account.archived ? (
                                        <Badge variant="outline">
                                            Archived
                                        </Badge>
                                    ) : null}
                                </span>
                                <AccountBalance balance={account.balance} />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
            <p
                className="flex flex-wrap items-baseline justify-between gap-2 border-t border-sidebar-border/70 pt-3"
                data-test="overview-account-total"
            >
                <span className="text-sm text-muted-foreground">
                    Total in {panel.total.currency}
                </span>
                <span className="font-semibold">
                    <AccountBalance balance={panel.total} />
                </span>
            </p>
        </section>
    );
}

function CategoryName({
    row,
    month,
}: {
    row: { id: number | null; name: string; archived: boolean };
    month: OverviewMonth;
}) {
    return (
        <span className="flex flex-wrap items-center gap-2">
            {row.id === null ? (
                <span>{row.name}</span>
            ) : (
                <Link
                    href={dashboard({
                        query: monthQuery(month.value, row.id),
                    })}
                    className="underline-offset-4 hover:underline"
                    data-test="open-category"
                >
                    {row.name}
                </Link>
            )}
            {row.archived ? <Badge variant="outline">Archived</Badge> : null}
        </span>
    );
}

function AddLink({
    categoryId,
    name,
    entryDate,
}: {
    categoryId: number;
    name: string;
    entryDate: string;
}) {
    return (
        <Link
            href={addTransactionHref(categoryId, entryDate)}
            className="inline-flex min-h-11 items-center text-sm font-normal underline-offset-4 hover:underline"
            aria-label={`Add a ${name} transaction`}
            data-test="add-category-transaction"
        >
            Add
        </Link>
    );
}

function Standing({
    status,
    label,
}: {
    status: OverviewStanding;
    label: string;
}) {
    const icon =
        status === 'over' ? (
            <ArrowUp aria-hidden="true" />
        ) : status === 'under' ? (
            <ArrowDown aria-hidden="true" />
        ) : (
            <Minus aria-hidden="true" />
        );

    return (
        <Badge
            variant={status === 'over' ? 'destructive' : 'outline'}
            data-test="budget-status"
        >
            {icon}
            {label}
        </Badge>
    );
}

Overview.layout = {
    breadcrumbs: [
        {
            title: 'Overview',
            href: dashboard(),
        },
    ],
};
