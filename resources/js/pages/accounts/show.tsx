import { Form, Head, Link, router } from '@inertiajs/react';
import { ListFilter, SearchIcon } from 'lucide-react';
import { useRef, useState } from 'react';
import { AccountBalance } from '@/components/account-balance';
import { AccountLabel } from '@/components/account-label';
import { CategoryLabel } from '@/components/category-label';
import { EntityIcon } from '@/components/entity-icon';
import { FilterChip } from '@/components/filter-chip';
import { FormSelect } from '@/components/form-select';
import { MoneyAmount, moneyDirection } from '@/components/money-amount';
import { PaginationNav, type Pagination } from '@/components/pagination-nav';
import {
    nextSortDirection,
    SortableTableHead,
} from '@/components/sortable-table-head';
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
import { edit, index, reconcile, show } from '@/routes/accounts';
import { edit as editTransaction } from '@/routes/transactions';
import { create as createTransfer } from '@/routes/transfers';
import type {
    AccountActivityFilters,
    AccountActivityOptions,
    AccountActivitySort,
    AccountActivitySortColumn,
    AccountDetail,
    StatementLine,
} from '@/types/account';

const pinnedColumn = 'sticky left-0 bg-background group-hover:bg-muted/50';

const columns: { column: AccountActivitySortColumn; label: string }[] = [
    { column: 'date', label: 'Date' },
    { column: 'type', label: 'Type' },
    { column: 'category', label: 'Category' },
    { column: 'person', label: 'Person' },
    { column: 'amount', label: 'Amount' },
];

const noFilters: AccountActivityFilters = {
    type: null,
    category: null,
    person: null,
    currency: null,
    search: '',
};

function activityQuery(
    filters: AccountActivityFilters,
    sort: AccountActivitySort,
    page = 1,
) {
    const isDefaultSort = sort.column === 'date' && sort.direction === 'asc';

    return {
        type: filters.type,
        category: filters.category,
        person: filters.person,
        currency: filters.currency,
        search: filters.search === '' ? null : filters.search,
        sort: isDefaultSort ? null : sort.column,
        direction: isDefaultSort ? null : sort.direction,
        page: page > 1 ? page : null,
    };
}

function ActivityToolbar({
    account,
    filters,
    sort,
    options,
}: {
    account: AccountDetail;
    filters: AccountActivityFilters;
    sort: AccountActivitySort;
    options: AccountActivityOptions;
}) {
    const searchRef = useRef<HTMLInputElement>(null);
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState(filters);
    const typeLabel = options.types.find(
        (type) => type.value === filters.type,
    )?.label;
    const categoryLabel = options.categories.find(
        (category) => category.id === filters.category,
    )?.name;
    const personLabel = options.people.find(
        (person) => person.id === filters.person,
    )?.name;

    const hrefFor = (next: AccountActivityFilters) =>
        toUrl(show(account.id, { query: activityQuery(next, sort) }));

    const without = (key: 'type' | 'category' | 'person' | 'currency') =>
        hrefFor({ ...filters, [key]: null });

    const applyDraft = (): void => {
        const search = searchRef.current?.value ?? filters.search;

        setOpen(false);
        router.visit(hrefFor({ ...draft, search }));
    };

    const hasChips =
        (filters.type !== null && typeLabel !== undefined) ||
        (filters.category !== null && categoryLabel !== undefined) ||
        (filters.person !== null && personLabel !== undefined) ||
        filters.currency !== null;

    return (
        <Form {...show.form(account.id)} className="flex flex-col gap-3">
            {sort.column !== 'date' || sort.direction !== 'asc' ? (
                <>
                    <input type="hidden" name="sort" value={sort.column} />
                    <input
                        type="hidden"
                        name="direction"
                        value={sort.direction}
                    />
                </>
            ) : null}
            {filters.type !== null ? (
                <input type="hidden" name="type" value={filters.type} />
            ) : null}
            {filters.category !== null ? (
                <input type="hidden" name="category" value={filters.category} />
            ) : null}
            {filters.person !== null ? (
                <input type="hidden" name="person" value={filters.person} />
            ) : null}
            {filters.currency !== null ? (
                <input type="hidden" name="currency" value={filters.currency} />
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
                        placeholder="Search activity..."
                        className="h-11 md:h-9"
                        data-test="filter-search"
                    />
                </InputGroup>
                <div className="flex items-center justify-end gap-2">
                    <Button asChild variant="ghost" className="h-11 md:h-9">
                        <Link
                            href={hrefFor(noFilters)}
                            data-test="clear-filters"
                        >
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
                                                    value === '' ? null : value,
                                            }))
                                        }
                                        emptyLabel="All"
                                        data-test="filter-type"
                                        options={options.types.map((type) => ({
                                            value: type.value,
                                            label: type.label,
                                        }))}
                                    />
                                </div>
                                <div className="flex flex-col gap-2">
                                    <Label htmlFor="category">Category</Label>
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
                                                icon: category.icon,
                                                color: category.color,
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
                                    <Label htmlFor="currency">Currency</Label>
                                    <FormSelect
                                        id="currency"
                                        value={draft.currency ?? ''}
                                        onValueChange={(value) =>
                                            setDraft((current) => ({
                                                ...current,
                                                currency:
                                                    value === '' ? null : value,
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
            {hasChips ? (
                <>
                    <Separator />
                    <ul
                        className="flex flex-wrap gap-2"
                        aria-label="Applied filters"
                        data-test="applied-filters"
                    >
                        {filters.type !== null && typeLabel ? (
                            <li>
                                <FilterChip
                                    label={`Type: ${typeLabel}`}
                                    href={without('type')}
                                    removeLabel="Remove type filter"
                                    test="filter-type-chip"
                                />
                            </li>
                        ) : null}
                        {filters.category !== null && categoryLabel ? (
                            <li>
                                <FilterChip
                                    label={`Category: ${categoryLabel}`}
                                    href={without('category')}
                                    removeLabel="Remove category filter"
                                    test="filter-category-chip"
                                />
                            </li>
                        ) : null}
                        {filters.person !== null && personLabel ? (
                            <li>
                                <FilterChip
                                    label={`Person: ${personLabel}`}
                                    href={without('person')}
                                    removeLabel="Remove person filter"
                                    test="filter-person-chip"
                                />
                            </li>
                        ) : null}
                        {filters.currency !== null ? (
                            <li>
                                <FilterChip
                                    label={`Currency: ${filters.currency}`}
                                    href={without('currency')}
                                    removeLabel="Remove currency filter"
                                    test="filter-currency-chip"
                                />
                            </li>
                        ) : null}
                    </ul>
                </>
            ) : null}
        </Form>
    );
}

export default function AccountShow({
    account,
    lines,
    pagination,
    filters,
    sort,
    filtered,
    options,
}: {
    account: AccountDetail;
    lines: StatementLine[];
    pagination: Pagination;
    filters: AccountActivityFilters;
    sort: AccountActivitySort;
    filtered: boolean;
    options: AccountActivityOptions;
}) {
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
                    {!filtered && pagination.total === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No transactions yet. The balance is the opening
                            balance.
                        </p>
                    ) : null}
                    <ActivityToolbar
                        key={`${filters.type ?? ''}|${filters.category ?? ''}|${filters.person ?? ''}|${filters.currency ?? ''}|${filters.search}|${sort.column}|${sort.direction}`}
                        account={account}
                        filters={filters}
                        sort={sort}
                        options={options}
                    />
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
                                    {columns.map((column) => (
                                        <SortableTableHead
                                            key={column.column}
                                            column={column.column}
                                            label={column.label}
                                            sort={sort}
                                            href={show(account.id, {
                                                query: activityQuery(filters, {
                                                    column: column.column,
                                                    direction:
                                                        nextSortDirection(
                                                            sort,
                                                            column.column,
                                                        ),
                                                }),
                                            })}
                                            className={cn(
                                                column.column === 'date' &&
                                                    pinnedColumn,
                                                column.column === 'amount' &&
                                                    'text-right',
                                            )}
                                        />
                                    ))}
                                    <TableHead
                                        scope="col"
                                        className="text-right"
                                    >
                                        Balance
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {lines.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={columns.length + 1}
                                            className="py-8 whitespace-normal text-muted-foreground"
                                        >
                                            {filtered
                                                ? 'No transactions match these filters.'
                                                : 'No transactions yet.'}
                                        </TableCell>
                                    </TableRow>
                                ) : null}
                                {lines.map((line) => {
                                    const href =
                                        line.id === null
                                            ? null
                                            : editTransaction(line.id);

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
                                                className={pinnedColumn}
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
                                            <TableCell className="relative whitespace-normal">
                                                {href ? (
                                                    <TableRowLink href={href} />
                                                ) : null}
                                                {line.counterparty ? (
                                                    <span className="inline-flex items-center gap-2 whitespace-nowrap">
                                                        <span>
                                                            {line.counterparty
                                                                .direction ===
                                                            'from'
                                                                ? 'Transfer from'
                                                                : 'Transfer to'}
                                                        </span>
                                                        <AccountLabel
                                                            name={
                                                                line
                                                                    .counterparty
                                                                    .name
                                                            }
                                                            icon={
                                                                line
                                                                    .counterparty
                                                                    .icon
                                                            }
                                                        />
                                                    </span>
                                                ) : (
                                                    line.type_label
                                                )}
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
                                    query: activityQuery(filters, sort, page),
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
