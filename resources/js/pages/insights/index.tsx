import { Head } from '@inertiajs/react';
import { type ComponentProps } from 'react';
import { Bar, BarChart, CartesianGrid, XAxis } from 'recharts';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { index as insights } from '@/routes/insights';
import type { InsightsTrendMonth, InsightsTrends } from '@/types/insights';

const chartConfig = {
    income: {
        label: 'Income',
        color: 'var(--chart-1)',
    },
    spent: {
        label: 'Spending',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

export default function Insights({ trends }: { trends: InsightsTrends }) {
    return (
        <>
            <Head title="Insights" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card data-test="trends">
                    <CardHeader>
                        <CardTitle>Income and spending</CardTitle>
                        <CardDescription>
                            The last 12 months, through {trends.through.label}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TrendChart months={trends.months} />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

type TrendChartRow = {
    month: string;
    label: string;
    income: number;
    spent: number;
    incomeFormatted: string;
    spentFormatted: string;
    incomeOriginalFormatted: string | null;
    spentOriginalFormatted: string | null;
};

function trendChartData(months: InsightsTrendMonth[]): TrendChartRow[] {
    return months.map((month) => ({
        month: month.short_label,
        label: month.label,
        income: chartAmount(month.income.amount),
        spent: chartAmount(month.spent.amount),
        incomeFormatted: month.income.formatted,
        spentFormatted: month.spent.formatted,
        incomeOriginalFormatted: month.income.original_formatted,
        spentOriginalFormatted: month.spent.original_formatted,
    }));
}

function TrendChart({ months }: { months: InsightsTrendMonth[] }) {
    return (
        <ChartContainer
            config={chartConfig}
            className="aspect-auto h-72 w-full"
            aria-label="Bar chart of income and spending"
        >
            <BarChart accessibilityLayer data={trendChartData(months)}>
                <CartesianGrid vertical={false} />
                <XAxis
                    dataKey="month"
                    tickLine={false}
                    tickMargin={10}
                    axisLine={false}
                    minTickGap={16}
                />
                <ChartTooltip
                    cursor={false}
                    content={<TrendTooltipContent indicator="dashed" />}
                />
                <ChartLegend content={<ChartLegendContent />} />
                <Bar dataKey="income" fill="var(--color-income)" radius={4} />
                <Bar dataKey="spent" fill="var(--color-spent)" radius={4} />
            </BarChart>
        </ChartContainer>
    );
}

function TrendTooltipContent({
    payload,
    ...props
}: ComponentProps<typeof ChartTooltipContent>) {
    const label = payload?.[0]?.payload?.label;

    return (
        <ChartTooltipContent
            {...props}
            payload={payload}
            label={typeof label === 'string' ? label : props.label}
            formatter={(value, name, item) => {
                const series = name === 'spent' ? 'spent' : 'income';
                const formatted = item.payload?.[`${series}Formatted`];
                const original = item.payload?.[`${series}OriginalFormatted`];

                return (
                    <span className="flex flex-1 items-center justify-between gap-2">
                        <span className="text-muted-foreground">
                            {chartConfig[series].label}
                        </span>
                        <span className="font-medium text-foreground tabular-nums">
                            {typeof formatted === 'string'
                                ? formatted
                                : String(value)}
                            {typeof original === 'string' ? (
                                <span className="font-normal text-muted-foreground">
                                    {' '}
                                    {original}
                                </span>
                            ) : null}
                        </span>
                    </span>
                );
            }}
        />
    );
}

function chartAmount(amount: string): number {
    const value = Number(amount);

    return Number.isFinite(value) ? value : 0;
}

Insights.layout = {
    breadcrumbs: [
        {
            title: 'Insights',
            href: insights(),
        },
    ],
};
