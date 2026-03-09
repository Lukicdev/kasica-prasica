import { Head, Link } from '@inertiajs/react';
import { Banknote, Plus } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { create, show } from '@/routes/budgets';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Budgets',
        href: '/budgets',
    },
];

interface Budget {
    id: number;
    name: string;
    amount: string;
    period_start: string;
    period_end: string;
}

interface BudgetsIndexProps {
    budgets: Budget[];
}

function formatAmount(amount: string): string {
    return new Intl.NumberFormat(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount));
}

function formatDate(dateStr: string): string {
    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'short',
    }).format(new Date(dateStr));
}

export default function BudgetsIndex({ budgets }: BudgetsIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Budgets" />

            <div className="p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Budgets</h1>
                        <p className="text-muted-foreground">
                            Set and track your budgets
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            Create Budget
                        </Link>
                    </Button>
                </div>

                {budgets.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Banknote className="mb-4 size-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No budgets yet</CardTitle>
                            <CardDescription className="mb-4">
                                Get started by creating your first budget
                            </CardDescription>
                            <Button asChild>
                                <Link href={create().url}>
                                    <Plus className="mr-2 size-4" />
                                    Create Budget
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {budgets.map((budget) => (
                            <Card
                                key={budget.id}
                                className="transition-shadow hover:shadow-md"
                            >
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <CardTitle className="text-lg">
                                                {budget.name}
                                            </CardTitle>
                                            <CardDescription className="mt-1">
                                                {formatAmount(budget.amount)} ·{' '}
                                                {formatDate(budget.period_start)}{' '}
                                                – {formatDate(budget.period_end)}
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <Button
                                        asChild
                                        variant="outline"
                                        className="w-full"
                                    >
                                        <Link href={show(budget.id).url}>
                                            View Details
                                        </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
