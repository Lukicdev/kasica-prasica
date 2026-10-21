import { Form, Head, Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { MoneyAmount } from '@/components/money-amount';
import { MonthPicker } from '@/components/month-picker';
import { PendingRecurrences } from '@/components/pending-recurrences';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { create, index, show } from '@/routes/transactions';
import { create as createTransfer } from '@/routes/transfers';
import type { PendingRecurrence } from '@/types/recurrence';
import type {
    TransactionListFilters,
    TransactionListOptions,
    TransactionMonth,
    TransactionRow,
    TransactionSort,
    TransactionSortColumn,
    TransactionTotal,
} from '@/types/transaction';

const fieldClassName =
    'border-input h-11 w-full rounded-md border bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm';

const columns: { column: TransactionSortColumn; label: string }[] = [
    { column: 'date', label: 'Date' },
    { column: 'type', label: 'Type' },
    { column: 'category', label: 'Category' },
    { column: 'note', label: 'Note' },
    { column: 'account', label: 'Account' },
    { column: 'person', label: 'Person' },
    { column: 'amount', label: 'Amount' },
    { column: 'added_by', label: 'Added by' },
];

function listQuery(
    month: string,
    filters: TransactionListFilters,
    sort: TransactionSort,
) {
    return {
        month,
        type: filters.type,
        category: filters.category,
        account: filters.account,
        person: filters.person,
        currency: filters.currency,
        search: filters.search === '' ? null : filters.search,
        sort: sort.column,
        direction: sort.direction,
    };
}

function nextDirection(
    sort: TransactionSort,
    column: TransactionSortColumn,
): 'asc' | 'desc' {
    if (sort.column !== column) {
        return column === 'date' || column === 'amount' ? 'desc' : 'asc';
    }

    return sort.direction === 'asc' ? 'desc' : 'asc';
}

export default function Transactions({
    month,
    filters,
    sort,
    filtered,
    options,
    transactions,
    total,
    pending,
}: {
    month: TransactionMonth;
    filters: TransactionListFilters;
    sort: TransactionSort;
    filtered: boolean;
    options: TransactionListOptions;
    transactions: TransactionRow[];
    total: TransactionTotal;
    pending: PendingRecurrence[];
}) {
    return (
        <>
            <Head title="Transactions" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Transactions
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {month.label}. Click a column title to sort.
                        </p>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <Button asChild className="h-11 w-full sm:w-auto">
                            <Link href={create()} data-test="new-transaction">
                                Add a transaction
                            </Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 w-full sm:w-auto"
                        >
                            <Link
                                href={createTransfer()}
                                data-test="new-transfer"
                            >
                                Transfer
                            </Link>
                        </Button>
                    </div>
                </section>

                <div className="flex items-center justify-between gap-3">
                    <Button asChild variant="outline" className="h-11">
                        <Link
                            href={index({
                                query: listQuery(month.previous, filters, sort),
                            })}
                            data-test="previous-month"
                        >
                            Previous
                        </Link>
                    </Button>
                    <p className="text-sm font-medium">{month.label}</p>
                    <Button asChild variant="outline" className="h-11">
                        <Link
                            href={index({
                                query: listQuery(month.next, filters, sort),
                            })}
                            data-test="next-month"
                        >
                            Next
                        </Link>
                    </Button>
                </div>

                <Form
                    key={`${month.value}|${filters.type ?? ''}|${filters.category ?? ''}|${filters.account ?? ''}|${filters.person ?? ''}|${filters.currency ?? ''}|${filters.search}|${sort.column}|${sort.direction}`}
                    {...index.form()}
                    className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-sidebar-border"
                >
                    <input type="hidden" name="sort" value={sort.column} />
                    <input
                        type="hidden"
                        name="direction"
                        value={sort.direction}
                    />
                    <div className="grid gap-2">
                        <Label htmlFor="month">Month</Label>
                        <MonthPicker
                            id="month"
                            name="month"
                            value={month.value}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <select
                            id="type"
                            name="type"
                            defaultValue={filters.type ?? ''}
                            className={fieldClassName}
                            data-test="filter-type"
                        >
                            <option value="">All</option>
                            {options.types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="category">Category</Label>
                        <select
                            id="category"
                            name="category"
                            defaultValue={filters.category ?? ''}
                            className={fieldClassName}
                        >
                            <option value="">All</option>
                            {options.categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="account">Account</Label>
                        <select
                            id="account"
                            name="account"
                            defaultValue={filters.account ?? ''}
                            className={fieldClassName}
                        >
                            <option value="">All</option>
                            {options.accounts.map((account) => (
                                <option key={account.id} value={account.id}>
                                    {account.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="person">Person</Label>
                        <select
                            id="person"
                            name="person"
                            defaultValue={filters.person ?? ''}
                            className={fieldClassName}
                        >
                            <option value="">All</option>
                            {options.people.map((person) => (
                                <option key={person.id} value={person.id}>
                                    {person.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <select
                            id="currency"
                            name="currency"
                            defaultValue={filters.currency ?? ''}
                            className={fieldClassName}
                        >
                            <option value="">All</option>
                            {options.currencies.map((currency) => (
                                <option
                                    key={currency.value}
                                    value={currency.value}
                                >
                                    {currency.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="search">Note</Label>
                        <Input
                            id="search"
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search notes"
                            className="h-11 md:h-9"
                            data-test="filter-search"
                        />
                    </div>
                    <div className="flex flex-col gap-3 sm:col-span-2 sm:flex-row lg:col-span-3">
                        <Button type="submit" className="h-11 sm:w-auto">
                            Apply
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 sm:w-auto"
                        >
                            <Link href={index()} data-test="clear-filters">
                                Clear
                            </Link>
                        </Button>
                    </div>
                </Form>

                <PendingRecurrences items={pending} />

                <section
                    aria-label={total.caption}
                    className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                    data-test="transaction-total"
                >
                    <p className="text-sm text-muted-foreground">
                        {total.caption} in {total.currency}
                    </p>
                    <p className="text-2xl font-semibold tracking-tight">
                        {total.formatted}
                    </p>
                </section>

                <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table
                        className="w-full min-w-[52rem] border-collapse text-left text-sm"
                        data-test="transaction-table"
                    >
                        <caption className="sr-only">
                            Transactions for {month.label}
                        </caption>
                        <thead className="border-b border-sidebar-border/70 text-muted-foreground dark:border-sidebar-border">
                            <tr>
                                {columns.map((column) => {
                                    const active =
                                        sort.column === column.column;
                                    const direction = nextDirection(
                                        sort,
                                        column.column,
                                    );

                                    return (
                                        <th
                                            key={column.column}
                                            scope="col"
                                            aria-sort={
                                                active
                                                    ? sort.direction === 'asc'
                                                        ? 'ascending'
                                                        : 'descending'
                                                    : 'none'
                                            }
                                            className={cn(
                                                'px-3 py-3 font-medium whitespace-nowrap',
                                                column.column === 'date' &&
                                                    'sticky left-0 bg-background',
                                                column.column === 'amount' &&
                                                    'text-right',
                                            )}
                                        >
                                            <Link
                                                href={index({
                                                    query: listQuery(
                                                        month.value,
                                                        filters,
                                                        {
                                                            column: column.column,
                                                            direction,
                                                        },
                                                    ),
                                                })}
                                                className="inline-flex min-h-11 items-center gap-1"
                                                data-test={`sort-${column.column}`}
                                            >
                                                {column.label}
                                                {active ? (
                                                    sort.direction === 'asc' ? (
                                                        <ArrowUp
                                                            className="size-4"
                                                            aria-hidden="true"
                                                        />
                                                    ) : (
                                                        <ArrowDown
                                                            className="size-4"
                                                            aria-hidden="true"
                                                        />
                                                    )
                                                ) : null}
                                                <span className="sr-only">
                                                    {active
                                                        ? sort.direction ===
                                                          'asc'
                                                            ? ', sorted ascending'
                                                            : ', sorted descending'
                                                        : ', sort'}
                                                </span>
                                            </Link>
                                        </th>
                                    );
                                })}
                            </tr>
                        </thead>
                        <tbody>
                            {transactions.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={columns.length}
                                        className="px-3 py-8 text-muted-foreground"
                                    >
                                        {filtered
                                            ? 'No transactions match these filters.'
                                            : `No transactions in ${month.label}.`}
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((transaction) => (
                                    <tr
                                        key={transaction.id}
                                        className="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                                        data-test="transaction-row"
                                    >
                                        <th
                                            scope="row"
                                            className="sticky left-0 bg-background px-3 py-3 font-medium whitespace-nowrap"
                                        >
                                            <Link
                                                href={show(transaction.id)}
                                                className="underline-offset-4 hover:underline"
                                            >
                                                <time
                                                    dateTime={transaction.date}
                                                >
                                                    {transaction.date_label}
                                                </time>
                                            </Link>
                                        </th>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            {transaction.type_label}
                                            {transaction.repeats ? (
                                                <span className="block text-xs text-muted-foreground">
                                                    Monthly
                                                </span>
                                            ) : null}
                                        </td>
                                        <td className="px-3 py-3">
                                            {transaction.category ?? '—'}
                                        </td>
                                        <td className="max-w-48 px-3 py-3">
                                            {transaction.note ?? '—'}
                                        </td>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            {transaction.account}
                                        </td>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            {transaction.person ?? '—'}
                                        </td>
                                        <td className="px-3 py-3 text-right whitespace-nowrap">
                                            <MoneyAmount
                                                formatted={
                                                    transaction.amount.formatted
                                                }
                                                originalFormatted={
                                                    transaction.amount
                                                        .original_formatted
                                                }
                                            />
                                        </td>
                                        <td className="px-3 py-3 whitespace-nowrap">
                                            {transaction.added_by ?? '—'}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

Transactions.layout = {
    breadcrumbs: [
        {
            title: 'Transactions',
            href: index(),
        },
    ],
};
