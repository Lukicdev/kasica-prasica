import { Head, Link } from '@inertiajs/react';
import { Plus, Receipt } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { create, show } from '@/routes/transactions';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: '/transactions',
    },
];

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

interface TransactionsIndexProps {
    transactions: Transaction[];
}

export default function TransactionsIndex({ transactions }: TransactionsIndexProps) {
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

                {transactions.length === 0 ? (
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
                    <div className="mt-6 space-y-2">
                        {transactions.map((transaction) => (
                            <Card key={transaction.id} className="hover:shadow-md transition-shadow">
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-4 flex-1">
                                            {transaction.category && (
                                                <div
                                                    className="size-3 rounded-full"
                                                    style={{ backgroundColor: transaction.category.color }}
                                                />
                                            )}
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium">
                                                        {transaction.description || 'No description'}
                                                    </p>
                                                    <span className="text-xs text-muted-foreground">
                                                        {transaction.type}
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2 text-sm text-muted-foreground mt-1">
                                                    <span>{transaction.account.name}</span>
                                                    {transaction.category && (
                                                        <>
                                                            <span>•</span>
                                                            <span>{transaction.category.name}</span>
                                                        </>
                                                    )}
                                                    <span>•</span>
                                                    <span>
                                                        {new Date(transaction.transaction_date).toLocaleDateString()}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-4">
                                            <span className={`text-lg font-semibold ${getAmountColor(transaction.type)}`}>
                                                {formatAmount(transaction.amount, transaction.type)}
                                            </span>
                                            <Button
                                                asChild
                                                variant="ghost"
                                                size="sm"
                                            >
                                                <Link href={show(transaction.id).url}>
                                                    View
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
