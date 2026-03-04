import { Head } from '@inertiajs/react';
import { Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface ExpenseByCategory {
    name: string;
    total: number;
    color: string;
}

interface DashboardProps {
    expenseByCategory: ExpenseByCategory[];
}

function slugify(name: string): string {
    return name
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9-]/g, '');
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
}

export default function Dashboard({ expenseByCategory }: DashboardProps) {
    const chartData =
        expenseByCategory.length > 0
            ? expenseByCategory.map((row) => ({
                  name: row.name,
                  total: row.total,
                  fill: row.color,
              }))
            : [];

    const chartConfig: ChartConfig = expenseByCategory.reduce(
        (acc, row) => {
            const key = slugify(row.name);
            acc[key] = { label: row.name, color: row.color };
            return acc;
        },
        {} as ChartConfig
    );

    const totalExpenses = expenseByCategory.reduce((sum, row) => sum + row.total, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    {chartData.length > 0 ? (
                        <Card className="flex flex-col">
                            <CardHeader className="items-center pb-0">
                                <CardTitle>Expenses by category</CardTitle>
                                <CardDescription>Total spending per category</CardDescription>
                            </CardHeader>
                            <CardContent className="flex-1 pb-0">
                                <ChartContainer
                                    config={chartConfig}
                                    className="mx-auto aspect-square max-h-[250px] pb-0 [&_.recharts-pie-label-text]:fill-foreground"
                                >
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Tooltip
                                                content={
                                                    <ChartTooltipContent
                                                        nameKey="name"
                                                        valueKey="total"
                                                        formatter={formatCurrency}
                                                    />
                                                }
                                            />
                                            <Pie
                                                data={chartData}
                                                dataKey="total"
                                                nameKey="name"
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={0}
                                                outerRadius="80%"
                                                paddingAngle={2}
                                                label
                                            />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </ChartContainer>
                            </CardContent>
                            <CardFooter className="flex-col gap-2 text-sm">
                                <div className="leading-none font-medium">
                                    Total expenses: {formatCurrency(totalExpenses)}
                                </div>
                                <div className="leading-none text-muted-foreground">
                                    Showing spending by category
                                </div>
                            </CardFooter>
                        </Card>
                    ) : (
                        <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                            <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                        </div>
                    )}
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout>
    );
}
