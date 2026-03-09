import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Banknote, Edit, Trash2 } from 'lucide-react';

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
import { destroy, edit, index } from '@/routes/budgets';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Budgets',
        href: index().url,
    },
];

interface Budget {
    id: number;
    amount: string;
    period: string;
    category: {
        id: number;
        name: string;
    } | null;
}

interface BudgetShowProps {
    budget: Budget;
}

function formatAmount(amount: string): string {
    return new Intl.NumberFormat(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

function formatPeriod(periodStr: string): string {
    return new Intl.DateTimeFormat(undefined, {
        month: 'long',
        year: 'numeric',
    }).format(new Date(periodStr));
}

export default function BudgetShow({ budget }: BudgetShowProps) {
    const handleDelete = () => {
        router.delete(destroy(budget.id).url, {
            onSuccess: () => {
                router.visit(index().url);
            },
        });
    };

    const title = `${budget.category?.name ?? 'Budget'} – ${formatPeriod(budget.period)}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={title} />

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
                                {title}
                            </h1>
                            <p className="text-muted-foreground">
                                Budget details
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(budget.id).url}>
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
                                    <DialogTitle>Delete Budget</DialogTitle>
                                    <DialogDescription>
                                        Are you sure you want to delete this
                                        budget? This action cannot be undone.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button
                                        variant="destructive"
                                        onClick={handleDelete}
                                    >
                                        Delete Budget
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="mt-6 grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Banknote className="size-5" />
                                <CardTitle>Budget Information</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <CardDescription>Category</CardDescription>
                                <p className="text-sm font-medium">
                                    {budget.category?.name ?? '—'}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Month</CardDescription>
                                <p className="text-sm font-medium">
                                    {formatPeriod(budget.period)}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Amount</CardDescription>
                                <p className="text-sm font-medium">
                                    {formatAmount(budget.amount)}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
