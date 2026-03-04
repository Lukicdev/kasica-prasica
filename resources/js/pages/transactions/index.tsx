import { Head, Link, router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ChevronLeft, ChevronRight, Plus, Receipt } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { create, index, show } from '@/routes/transactions';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: '/transactions',
    },
];

const SORT_COLUMNS = [
    { key: 'transaction_date', label: 'Date' },
    { key: 'description', label: 'Description' },
    { key: 'type', label: 'Type' },
    { key: 'account_name', label: 'Account' },
    { key: 'category_name', label: 'Category' },
    { key: 'amount', label: 'Amount' },
] as const;

type SortKey = (typeof SORT_COLUMNS)[number]['key'];

interface Transaction {
    id: number;
    account_id: number;
    category_id: number | null;
    type: string;
    amount: string;
    description: string | null;
    transaction_date: string;
    account: {
        id: number;
        name: string;
    };
    category: {
        id: number;
        name: string;
        color: string;
    } | null;
}

interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface TransactionsIndexProps {
    transactions: {
        data: Transaction[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: PaginatorLink[];
    };
    sort: SortKey;
    direction: 'asc' | 'desc';
}

export default function TransactionsIndex({
    transactions,
    sort,
    direction,
}: TransactionsIndexProps) {
    const { data: transactionList, links: paginationLinks } = transactions;

    const sortUrl = (column: SortKey, page: number) => {
        const nextDirection =
            sort === column ? (direction === 'asc' ? 'desc' : 'asc') : 'desc';
        return index().url +
            '?' +
            new URLSearchParams({
                sort: column,
                direction: nextDirection,
                page: String(page),
            }).toString();
    };

    const formatAmount = (amount: string, type: string) => {
        const numAmount = parseFloat(amount);
        const formatted = numAmount.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        if (type === 'income') {
            return `+${formatted}`;
        }
        if (type === 'expense') {
            return `-${formatted}`;
        }
        return formatted;
    };

    const getAmountColor = (type: string) => {
        if (type === 'income') {
            return 'text-green-600 dark:text-green-400';
        }
        if (type === 'expense') {
            return 'text-red-600 dark:text-red-400';
        }
        return 'text-neutral-600 dark:text-neutral-400';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transactions" />

            <div className="p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Transactions</h1>
                        <p className="text-muted-foreground">
                            Manage your financial transactions
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Create Transaction
                        </Link>
                    </Button>
                </div>

                {transactionList.length === 0 ? (
                    <Card className="mt-6">
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Receipt className="mb-4 size-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No transactions yet</CardTitle>
                            <CardDescription className="mb-4">
                                Get started by creating your first transaction
                            </CardDescription>
                            <Button asChild>
                                <Link href={create().url}>
                                    <Plus className="mr-2 size-4" />
                                    Create Transaction
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <Card className="mt-6 overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full caption-bottom text-sm">
                                <thead>
                                    <tr className="border-b border-border bg-muted/50 transition-colors hover:bg-muted/50">
                                        {SORT_COLUMNS.map(({ key, label }) => {
                                            const isRight =
                                                key === 'amount';
                                            return (
                                                <th
                                                    key={key}
                                                    className={
                                                        'h-10 px-4 align-middle font-medium text-muted-foreground ' +
                                                        (isRight
                                                            ? 'text-right'
                                                            : 'text-left')
                                                    }
                                                >
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            router.visit(
                                                                sortUrl(key, 1)
                                                            )
                                                        }
                                                        className="inline-flex items-center gap-1 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 rounded"
                                                    >
                                                        {label}
                                                        {sort === key &&
                                                            (direction ===
                                                            'asc' ? (
                                                                <ArrowUp className="size-4 shrink-0" />
                                                            ) : (
                                                                <ArrowDown className="size-4 shrink-0" />
                                                            ))}
                                                    </button>
                                                </th>
                                            );
                                        })}
                                        <th className="h-10 px-4 text-right align-middle font-medium text-muted-foreground">
                                            <span className="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactionList.map((transaction) => (
                                        <tr
                                            key={transaction.id}
                                            className="border-b border-border transition-colors hover:bg-muted/50"
                                        >
                                            <td className="p-4 align-middle text-muted-foreground">
                                                {new Date(
                                                    transaction.transaction_date
                                                ).toLocaleDateString()}
                                            </td>
                                            <td className="p-4 align-middle font-medium">
                                                {transaction.description || 'No description'}
                                            </td>
                                            <td className="p-4 align-middle capitalize">
                                                {transaction.type}
                                            </td>
                                            <td className="p-4 align-middle">
                                                {transaction.account.name}
                                            </td>
                                            <td className="p-4 align-middle">
                                                {transaction.category ? (
                                                    <span
                                                        className="inline-flex items-center gap-1.5"
                                                        title={transaction.category.name}
                                                    >
                                                        <span
                                                            className="size-2 shrink-0 rounded-full"
                                                            style={{
                                                                backgroundColor:
                                                                    transaction.category.color,
                                                            }}
                                                        />
                                                        {transaction.category.name}
                                                    </span>
                                                ) : (
                                                    <span className="text-muted-foreground">—</span>
                                                )}
                                            </td>
                                            <td
                                                className={`p-4 text-right align-middle font-semibold tabular-nums ${getAmountColor(transaction.type)}`}
                                            >
                                                {formatAmount(transaction.amount, transaction.type)}
                                            </td>
                                            <td className="p-4 text-right align-middle">
                                                <Button asChild variant="ghost" size="sm">
                                                    <Link href={show(transaction.id).url}>
                                                        View
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="flex flex-wrap items-center justify-between gap-4 border-t border-border px-4 py-3">
                            <p className="text-sm text-muted-foreground">
                                Showing{' '}
                                <span className="font-medium">
                                    {transactions.from ?? 0}
                                </span>{' '}
                                to{' '}
                                <span className="font-medium">
                                    {transactions.to ?? 0}
                                </span>{' '}
                                of{' '}
                                <span className="font-medium">
                                    {transactions.total}
                                </span>{' '}
                                results
                            </p>
                            <nav
                                className="flex items-center gap-1"
                                aria-label="Pagination"
                            >
                                {paginationLinks.map((link, i) => {
                                    const isPrev =
                                        link.label.includes('Previous');
                                    const isNext = link.label.includes('Next');
                                    const isEllipsis =
                                        link.label === '...';
                                    if (isEllipsis) {
                                        return (
                                            <span
                                                key={i}
                                                className="px-2 text-muted-foreground"
                                            >
                                                …
                                            </span>
                                        );
                                    }
                                    if (isPrev || isNext) {
                                        const Icon = isPrev
                                            ? ChevronLeft
                                            : ChevronRight;
                                        return link.url ? (
                                            <Link
                                                key={i}
                                                href={link.url}
                                                className="inline-flex size-9 items-center justify-center rounded-md border border-input bg-background text-sm font-medium hover:bg-accent hover:text-accent-foreground"
                                                aria-label={link.label.replace(
                                                    /&[^;]+;/g,
                                                    ''
                                                )}
                                            >
                                                <Icon className="size-4" />
                                            </Link>
                                        ) : (
                                            <span
                                                key={i}
                                                className="inline-flex size-9 cursor-not-allowed items-center justify-center rounded-md border border-input bg-muted text-muted-foreground"
                                                aria-disabled="true"
                                            >
                                                <Icon className="size-4" />
                                            </span>
                                        );
                                    }
                                    return link.url ? (
                                        <Link
                                            key={i}
                                            href={link.url}
                                            className={
                                                'inline-flex size-9 items-center justify-center rounded-md text-sm font-medium ' +
                                                (link.active
                                                    ? 'border border-input bg-primary text-primary-foreground'
                                                    : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground')
                                            }
                                        >
                                            {link.label}
                                        </Link>
                                    ) : (
                                        <span
                                            key={i}
                                            className="inline-flex size-9 items-center justify-center rounded-md border border-input bg-primary text-primary-foreground text-sm font-medium"
                                        >
                                            {link.label}
                                        </span>
                                    );
                                })}
                            </nav>
                        </div>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
