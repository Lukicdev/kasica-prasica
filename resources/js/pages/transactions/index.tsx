import { Form, Head, Link } from '@inertiajs/react';
import { ArrowDown, ArrowUp } from 'lucide-react';
import { CategoryLabel } from '@/components/category-label';
import { FormSelect } from '@/components/form-select';
import { MoneyAmount, moneyDirection } from '@/components/money-amount';
import { MonthNav } from '@/components/month-nav';
import { PendingRecurrences } from '@/components/pending-recurrences';
import { TableRowLink } from '@/components/table-row-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

const columns: { column: TransactionSortColumn; label: string }[] = [
    { column: 'date', label: 'Date' },
    { column: 'type', label: 'Type' },
    { column: 'category', label: 'Category' },
    { column: 'note', label: 'Note' },
    { column: 'account', label: 'Account' },
    { column: 'person', label: 'Person' },
    { column: 'amount', label: 'Amount' },
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

                <MonthNav
                    value={month.value}
                    previousHref={index({
                        query: listQuery(month.previous, filters, sort),
                    })}
                    nextHref={index({
                        query: listQuery(month.next, filters, sort),
                    })}
                    hrefFor={(value) =>
                        index({
                            query: listQuery(value, filters, sort),
                        })
                    }
                />

                <Form
                    key={`${month.value}|${filters.type ?? ''}|${filters.category ?? ''}|${filters.account ?? ''}|${filters.person ?? ''}|${filters.currency ?? ''}|${filters.search}|${sort.column}|${sort.direction}`}
                    {...index.form()}
                    className="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-sidebar-border"
                >
                    <input type="hidden" name="month" value={month.value} />
                    <input type="hidden" name="sort" value={sort.column} />
                    <input
                        type="hidden"
                        name="direction"
                        value={sort.direction}
                    />
                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <FormSelect
                            id="type"
                            name="type"
                            defaultValue={filters.type ?? ''}
                            emptyLabel="All"
                            data-test="filter-type"
                            options={options.types.map((type) => ({
                                value: type.value,
                                label: type.label,
                            }))}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="category">Category</Label>
                        <FormSelect
                            id="category"
                            name="category"
                            defaultValue={
                                filters.category === null
                                    ? ''
                                    : String(filters.category)
                            }
                            emptyLabel="All"
                            options={options.categories.map((category) => ({
                                value: String(category.id),
                                label: category.name,
                            }))}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="account">Account</Label>
                        <FormSelect
                            id="account"
                            name="account"
                            defaultValue={
                                filters.account === null
                                    ? ''
                                    : String(filters.account)
                            }
                            emptyLabel="All"
                            options={options.accounts.map((account) => ({
                                value: String(account.id),
                                label: account.name,
                            }))}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="person">Person</Label>
                        <FormSelect
                            id="person"
                            name="person"
                            defaultValue={
                                filters.person === null
                                    ? ''
                                    : String(filters.person)
                            }
                            emptyLabel="All"
                            options={options.people.map((person) => ({
                                value: String(person.id),
                                label: person.name,
                            }))}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <FormSelect
                            id="currency"
                            name="currency"
                            defaultValue={filters.currency ?? ''}
                            emptyLabel="All"
                            options={options.currencies.map((currency) => ({
                                value: currency.value,
                                label: currency.label,
                            }))}
                        />
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

                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <Table
                        className="min-w-[52rem]"
                        data-test="transaction-table"
                    >
                        <TableCaption className="sr-only">
                            Transactions for {month.label}
                        </TableCaption>
                        <TableHeader>
                            <TableRow className="group">
                                {columns.map((column) => {
                                    const active =
                                        sort.column === column.column;
                                    const direction = nextDirection(
                                        sort,
                                        column.column,
                                    );

                                    return (
                                        <TableHead
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
                                                column.column === 'date' &&
                                                    'sticky left-0 bg-background group-hover:bg-muted/50',
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
                                        </TableHead>
                                    );
                                })}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {transactions.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={columns.length}
                                        className="py-8 whitespace-normal text-muted-foreground"
                                    >
                                        {filtered
                                            ? 'No transactions match these filters.'
                                            : `No transactions in ${month.label}.`}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                transactions.map((transaction) => {
                                    const href = show(transaction.id);

                                    return (
                                        <TableRow
                                            key={transaction.id}
                                            className="group cursor-pointer"
                                            data-test="transaction-row"
                                        >
                                            <TableHead
                                                scope="row"
                                                className="sticky left-0 bg-background group-hover:bg-muted/50"
                                            >
                                                <TableRowLink
                                                    href={href}
                                                    label={`${transaction.date_label}, ${transaction.type_label}`}
                                                />
                                                <time
                                                    dateTime={transaction.date}
                                                >
                                                    {transaction.date_label}
                                                </time>
                                            </TableHead>
                                            <TableCell className="relative">
                                                <TableRowLink href={href} />
                                                {transaction.type_label}
                                                {transaction.repeats ? (
                                                    <span className="block text-xs text-muted-foreground">
                                                        Monthly
                                                    </span>
                                                ) : null}
                                            </TableCell>
                                            <TableCell className="relative whitespace-normal">
                                                <TableRowLink href={href} />
                                                <CategoryLabel
                                                    name={transaction.category}
                                                    icon={
                                                        transaction.category_icon
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell className="relative max-w-48 whitespace-normal">
                                                <TableRowLink href={href} />
                                                {transaction.note ?? '—'}
                                            </TableCell>
                                            <TableCell className="relative">
                                                <TableRowLink href={href} />
                                                {transaction.account}
                                            </TableCell>
                                            <TableCell className="relative">
                                                <TableRowLink href={href} />
                                                {transaction.person ?? '—'}
                                            </TableCell>
                                            <TableCell className="relative text-right">
                                                <TableRowLink href={href} />
                                                <MoneyAmount
                                                    formatted={
                                                        transaction.amount
                                                            .formatted
                                                    }
                                                    originalFormatted={
                                                        transaction.amount
                                                            .original_formatted
                                                    }
                                                    direction={moneyDirection(
                                                        transaction.type,
                                                    )}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    );
                                })
                            )}
                        </TableBody>
                    </Table>
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
