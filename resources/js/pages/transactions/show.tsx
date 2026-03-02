import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Edit, Receipt, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { destroy, edit, index } from '@/routes/transactions';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: index().url,
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
    related_transaction_id: number | null;
    account: {
        id: number;
        name: string;
    };
    category: {
        id: number;
        name: string;
        color: string;
    } | null;
    relatedTransaction?: {
        id: number;
        account: {
            name: string;
        };
    } | null;
}

interface TransactionShowProps {
    transaction: Transaction;
}

export default function TransactionShow({ transaction }: TransactionShowProps) {
    const handleDelete = () => {
        router.delete(destroy(transaction.id).url, {
            onSuccess: () => {
                router.visit(index().url);
            },
        });
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
            <Head title={`Transaction ${transaction.id}`} />

            <div className="p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                Transaction Details
                            </h1>
                            <p className="text-muted-foreground">
                                View transaction information
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(transaction.id).url}>
                                <Edit className="mr-2 size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 className="mr-2 size-4" />
                                    Delete
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete Transaction</DialogTitle>
                                    <DialogDescription>
                                        Are you sure you want to delete this
                                        transaction? This action cannot be undone.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button
                                        variant="destructive"
                                        onClick={handleDelete}
                                    >
                                        Delete Transaction
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2 mt-6">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Receipt className="size-5" />
                                <CardTitle>Transaction Information</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <CardDescription>Description</CardDescription>
                                <p className="text-sm font-medium">
                                    {transaction.description || 'No description'}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Type</CardDescription>
                                <p className="text-sm font-medium">
                                    {transaction.type.charAt(0).toUpperCase() +
                                        transaction.type.slice(1)}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Account</CardDescription>
                                <p className="text-sm font-medium">
                                    {transaction.account.name}
                                </p>
                            </div>
                            {transaction.category && (
                                <div>
                                    <CardDescription>Category</CardDescription>
                                    <div className="flex items-center gap-2 mt-1">
                                        <div
                                            className="size-3 rounded-full"
                                            style={{ backgroundColor: transaction.category.color }}
                                        />
                                        <p className="text-sm font-medium">
                                            {transaction.category.name}
                                        </p>
                                    </div>
                                </div>
                            )}
                            <div>
                                <CardDescription>Date</CardDescription>
                                <p className="text-sm font-medium">
                                    {new Date(transaction.transaction_date).toLocaleDateString()}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Amount</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className={`text-3xl font-bold ${getAmountColor(transaction.type)}`}>
                                {formatAmount(transaction.amount, transaction.type)}
                            </div>
                            {transaction.relatedTransaction && (
                                <CardDescription className="mt-2">
                                    Transfer to: {transaction.relatedTransaction.account.name}
                                </CardDescription>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
