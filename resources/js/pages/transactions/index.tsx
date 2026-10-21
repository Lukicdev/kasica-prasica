import { Form, Head, Link, router } from '@inertiajs/react';
import { format, lastDayOfMonth, parse } from 'date-fns';
import {
    ArrowDown,
    ArrowLeftRight,
    ArrowUp,
    CalendarIcon,
    ListFilter,
    Plus,
    SearchIcon,
    XIcon,
} from 'lucide-react';
import { useRef, useState, type ReactNode } from 'react';
import { CategoryLabel } from '@/components/category-label';
import { FormSelect } from '@/components/form-select';
import { MoneyAmount, moneyDirection } from '@/components/money-amount';
import { MonthNav } from '@/components/month-nav';
import { PendingRecurrences } from '@/components/pending-recurrences';
import { TableRowLink } from '@/components/table-row-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverHeader,
    PopoverTitle,
    PopoverTrigger,
} from '@/components/ui/popover';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn, toUrl } from '@/lib/utils';
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

const pinnedColumn =
    'sticky left-0 z-20 bg-background shadow-[1px_0_0_0_var(--border)] group-hover:bg-[color-mix(in_oklch,var(--muted)_50%,var(--background))]';

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

function monthSpan(value: string): string {
    const start = parse(value, 'yyyy-MM', new Date());
    const end = lastDayOfMonth(start);

    return `${format(start, 'dd MMM')} — ${format(end, 'dd MMM')}`;
}

function FilterChip({
    icon,
    label,
    href,
    removeLabel,
    test,
}: {
    icon?: ReactNode;
    label: string;
    href: string;
    removeLabel: string;
    test?: string;
}) {
    return (
        <Badge variant="outline" className="h-7 px-2" data-test={test}>
            {icon}
            {label}
            <Link
                href={href}
                aria-label={removeLabel}
                className="inline-flex size-5 items-center justify-center rounded-sm"
            >
                <XIcon className="size-3" />
            </Link>
        </Badge>
    );
}

function TransactionToolbar({
    month,
    filters,
    sort,
    options,
}: {
    month: TransactionMonth;
    filters: TransactionListFilters;
    sort: TransactionSort;
    options: TransactionListOptions;
}) {
    const searchRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(filters);
    const today = format(new Date(), 'yyyy-MM');
    const typeLabel = options.types.find(
        (type) => type.value === filters.type,
    )?.label;
    const categoryLabel = options.categories.find(
        (category) => category.id === filters.category,
    )?.name;
    const accountLabel = options.accounts.find(
        (account) => account.id === filters.account,
    )?.name;
    const personLabel = options.people.find(
        (person) => person.id === filters.person,
    )?.name;

    const clearHref = index({
        query: listQuery(
            month.value,
            {
                type: null,
                category: null,
                account: null,
                person: null,
                currency: null,
                search: '',
            },
            sort,
        ),
    });

    const without = (
        key: 'type' | 'category' | 'account' | 'person' | 'currency',
    ) =>
        index({
            query: listQuery(month.value, { ...filters, [key]: null }, sort),
        });

    const applyDraft = (): void => {
        const search = searchRef.current?.value ?? filters.search;

        setOpen(false);
        router.visit(
            toUrl(
                index({
                    query: listQuery(month.value, { ...draft, search }, sort),
                }),
            ),
        );
    };

    return (
        <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h1 className="text-lg font-semibold tracking-tight">
                    Transactions
                </h1>
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
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
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full sm:w-auto md:h-9"
                    >
                        <Link href={createTransfer()} data-test="new-transfer">
                            <ArrowLeftRight data-icon="inline-start" />
                            Transfer
                        </Link>
                    </Button>
                    <Button asChild className="h-11 w-full sm:w-auto md:h-9">
                        <Link href={create()} data-test="new-transaction">
                            <Plus data-icon="inline-start" />
                            Add a transaction
                        </Link>
                    </Button>
                </div>
            </div>

            <Form {...index.form()} className="flex flex-col gap-3">
                <input type="hidden" name="month" value={month.value} />
                <input type="hidden" name="sort" value={sort.column} />
                <input type="hidden" name="direction" value={sort.direction} />
                {filters.type !== null ? (
                    <input type="hidden" name="type" value={filters.type} />
                ) : null}
                {filters.category !== null ? (
                    <input
                        type="hidden"
                        name="category"
                        value={filters.category}
                    />
                ) : null}
                {filters.account !== null ? (
                    <input
                        type="hidden"
                        name="account"
                        value={filters.account}
                    />
                ) : null}
                {filters.person !== null ? (
                    <input type="hidden" name="person" value={filters.person} />
                ) : null}
                {filters.currency !== null ? (
                    <input
                        type="hidden"
                        name="currency"
                        value={filters.currency}
                    />
                ) : null}
                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <InputGroup className="h-11 min-w-0 flex-1 md:h-9">
                        <InputGroupAddon>
                            <SearchIcon />
                        </InputGroupAddon>
                        <InputGroupInput
                            ref={searchRef}
                            id="search"
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Search transactions..."
                            className="h-11 md:h-9"
                            data-test="filter-search"
                        />
                    </InputGroup>
                    <div className="flex items-center justify-end gap-2">
                        <Button asChild variant="ghost" className="h-11 md:h-9">
                            <Link href={clearHref} data-test="clear-filters">
                                Clear filters
                            </Link>
                        </Button>
                        <Popover
                            open={open}
                            onOpenChange={(next) => {
                                setOpen(next);

                                if (!next) {
                                    setDraft(filters);
                                }
                            }}
                        >
                            <PopoverTrigger asChild>
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="h-11 md:h-9"
                                    data-test="filters"
                                >
                                    <ListFilter data-icon="inline-start" />
                                    Filters
                                </Button>
                            </PopoverTrigger>
                            <PopoverContent
                                align="end"
                                className="flex w-80 flex-col gap-4"
                            >
                                <PopoverHeader>
                                    <PopoverTitle>Filters</PopoverTitle>
                                </PopoverHeader>
                                <div className="flex flex-col gap-3">
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="type">Type</Label>
                                        <FormSelect
                                            id="type"
                                            value={draft.type ?? ''}
                                            onValueChange={(value) =>
                                                setDraft((current) => ({
                                                    ...current,
                                                    type:
                                                        value === ''
                                                            ? null
                                                            : value,
                                                }))
                                            }
                                            emptyLabel="All"
                                            data-test="filter-type"
                                            options={options.types.map(
                                                (type) => ({
                                                    value: type.value,
                                                    label: type.label,
                                                }),
                                            )}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="category">
                                            Category
                                        </Label>
                                        <FormSelect
                                            id="category"
                                            value={
                                                draft.category === null
                                                    ? ''
                                                    : String(draft.category)
                                            }
                                            onValueChange={(value) =>
                                                setDraft((current) => ({
                                                    ...current,
                                                    category:
                                                        value === ''
                                                            ? null
                                                            : Number(value),
                                                }))
                                            }
                                            emptyLabel="All"
                                            options={options.categories.map(
                                                (category) => ({
                                                    value: String(category.id),
                                                    label: category.name,
                                                }),
                                            )}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="account">Account</Label>
                                        <FormSelect
                                            id="account"
                                            value={
                                                draft.account === null
                                                    ? ''
                                                    : String(draft.account)
                                            }
                                            onValueChange={(value) =>
                                                setDraft((current) => ({
                                                    ...current,
                                                    account:
                                                        value === ''
                                                            ? null
                                                            : Number(value),
                                                }))
                                            }
                                            emptyLabel="All"
                                            options={options.accounts.map(
                                                (account) => ({
                                                    value: String(account.id),
                                                    label: account.name,
                                                }),
                                            )}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="person">Person</Label>
                                        <FormSelect
                                            id="person"
                                            value={
                                                draft.person === null
                                                    ? ''
                                                    : String(draft.person)
                                            }
                                            onValueChange={(value) =>
                                                setDraft((current) => ({
                                                    ...current,
                                                    person:
                                                        value === ''
                                                            ? null
                                                            : Number(value),
                                                }))
                                            }
                                            emptyLabel="All"
                                            options={options.people.map(
                                                (person) => ({
                                                    value: String(person.id),
                                                    label: person.name,
                                                }),
                                            )}
                                        />
                                    </div>
                                    <div className="flex flex-col gap-2">
                                        <Label htmlFor="currency">
                                            Currency
                                        </Label>
                                        <FormSelect
                                            id="currency"
                                            value={draft.currency ?? ''}
                                            onValueChange={(value) =>
                                                setDraft((current) => ({
                                                    ...current,
                                                    currency:
                                                        value === ''
                                                            ? null
                                                            : value,
                                                }))
                                            }
                                            emptyLabel="All"
                                            options={options.currencies.map(
                                                (currency) => ({
                                                    value: currency.value,
                                                    label: currency.label,
                                                }),
                                            )}
                                        />
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    className="h-11 md:h-9"
                                    onClick={applyDraft}
                                    data-test="apply-filters"
                                >
                                    Apply
                                </Button>
                            </PopoverContent>
                        </Popover>
                    </div>
                </div>
                <Separator />
                <ul
                    className="flex flex-wrap gap-2"
                    aria-label="Applied filters"
                    data-test="applied-filters"
                >
                    <li>
                        <FilterChip
                            icon={<CalendarIcon />}
                            label={`Date: ${monthSpan(month.value)}`}
                            href={toUrl(
                                index({
                                    query: listQuery(today, filters, sort),
                                }),
                            )}
                            removeLabel="Reset date to this month"
                            test="filter-date"
                        />
                    </li>
                    {filters.type !== null && typeLabel ? (
                        <li>
                            <FilterChip
                                label={`Type: ${typeLabel}`}
                                href={toUrl(without('type'))}
                                removeLabel="Remove type filter"
                                test="filter-type-chip"
                            />
                        </li>
                    ) : null}
                    {filters.category !== null && categoryLabel ? (
                        <li>
                            <FilterChip
                                label={`Category: ${categoryLabel}`}
                                href={toUrl(without('category'))}
                                removeLabel="Remove category filter"
                                test="filter-category-chip"
                            />
                        </li>
                    ) : null}
                    {filters.account !== null && accountLabel ? (
                        <li>
                            <FilterChip
                                label={`Account: ${accountLabel}`}
                                href={toUrl(without('account'))}
                                removeLabel="Remove account filter"
                                test="filter-account-chip"
                            />
                        </li>
                    ) : null}
                    {filters.person !== null && personLabel ? (
                        <li>
                            <FilterChip
                                label={`Person: ${personLabel}`}
                                href={toUrl(without('person'))}
                                removeLabel="Remove person filter"
                                test="filter-person-chip"
                            />
                        </li>
                    ) : null}
                    {filters.currency !== null ? (
                        <li>
                            <FilterChip
                                label={`Currency: ${filters.currency}`}
                                href={toUrl(without('currency'))}
                                removeLabel="Remove currency filter"
                                test="filter-currency-chip"
                            />
                        </li>
                    ) : null}
                </ul>
            </Form>
        </section>
    );
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
                <TransactionToolbar
                    key={`${month.value}|${filters.type ?? ''}|${filters.category ?? ''}|${filters.account ?? ''}|${filters.person ?? ''}|${filters.currency ?? ''}|${filters.search}|${sort.column}|${sort.direction}`}
                    month={month}
                    filters={filters}
                    sort={sort}
                    options={options}
                />

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
                                                    pinnedColumn,
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
                                                className={pinnedColumn}
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
