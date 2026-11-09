import { Head } from '@inertiajs/react';
import { type ComponentProps, type ReactNode } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    XAxis,
    YAxis,
} from 'recharts';
import { MoneyAmount } from '@/components/money-amount';
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
import { cn } from '@/lib/utils';
import { index as insights } from '@/routes/insights';
import type {
    InsightsNetWorth,
    InsightsNetWorthMonth,
    InsightsTrendMonth,
    InsightsTrends,
} from '@/types/insights';

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

const netWorthChartConfig = {
    netWorth: {
        label: 'Net worth',
        color: 'var(--chart-1)',
    },
} satisfies ChartConfig;

export default function Insights({
    trends,
    net_worth,
}: {
    trends: InsightsTrends;
    net_worth: InsightsNetWorth;
}) {
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
                <Card data-test="net-worth">
                    <CardHeader>
                        <CardTitle>Net worth</CardTitle>
                        <CardDescription>
                            Accounts and assets for the last 12 months, through{' '}
                            {net_worth.through.label}.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4">
                        <NetWorthChart months={net_worth.months} />
                        <p
                            className="text-sm text-muted-foreground"
                            data-test="net-worth-previous"
                        >
                            In {net_worth.previous.month} on day{' '}
                            {net_worth.previous.day}:{' '}
                            <MoneyAmount
                                formatted={net_worth.previous.balance.formatted}
                                originalFormatted={
                                    net_worth.previous.balance
                                        .original_formatted
                                }
                                className="font-medium text-foreground"
                            />
                            {net_worth.previous.percent === null ? null : (
                                <span
                                    className={cn(
                                        'font-medium',
                                        net_worth.previous.percent > 0 &&
                                            'text-success',
                                        net_worth.previous.percent < 0 &&
                                            'text-destructive',
                                    )}
                                >
                                    {' '}
                                    ({net_worth.previous.percent > 0 ? '+' : ''}
                                    {net_worth.previous.percent}%)
                                </span>
                            )}
                        </p>
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
            <BarChart
                accessibilityLayer
                data={trendChartData(months)}
                margin={{ left: 12, right: 12 }}
            >
                <CartesianGrid vertical={false} />
                <XAxis
                    dataKey="month"
                    tickLine={false}
                    tickMargin={10}
                    axisLine={false}
                    minTickGap={16}
                />
                <YAxis
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={16}
                    width={72}
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

function NetWorthChart({ months }: { months: InsightsNetWorthMonth[] }) {
    return (
        <ChartContainer
            config={netWorthChartConfig}
            className="aspect-auto h-72 w-full"
            aria-label="Line chart of net worth"
        >
            <LineChart
                accessibilityLayer
                data={netWorthChartData(months)}
                margin={{ left: 12, right: 12 }}
            >
                <CartesianGrid vertical={false} />
                <XAxis
                    dataKey="month"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={16}
                />
                <YAxis
                    dataKey="netWorth"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={16}
                    width={72}
                />
                <ChartTooltip
                    cursor={false}
                    content={<NetWorthTooltipContent />}
                />
                <Line
                    dataKey="netWorth"
                    type="monotone"
                    stroke="var(--color-netWorth)"
                    strokeWidth={2}
                    dot={false}
                />
            </LineChart>
        </ChartContainer>
    );
}

type NetWorthChartRow = {
    month: string;
    label: string;
    netWorth: number;
    netWorthFormatted: string;
    netWorthOriginalFormatted: string | null;
};

function netWorthChartData(
    months: InsightsNetWorthMonth[],
): NetWorthChartRow[] {
    return months.map((month) => ({
        month: month.short_label,
        label: month.label,
        netWorth: chartAmount(month.net_worth.amount),
        netWorthFormatted: month.net_worth.formatted,
        netWorthOriginalFormatted: month.net_worth.original_formatted,
    }));
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

                return (
                    <MoneyTooltipRow
                        label={chartConfig[series].label}
                        formatted={item.payload?.[`${series}Formatted`]}
                        original={item.payload?.[`${series}OriginalFormatted`]}
                        fallback={value}
                    />
                );
            }}
        />
    );
}

function NetWorthTooltipContent({
    payload,
    ...props
}: ComponentProps<typeof ChartTooltipContent>) {
    const label = payload?.[0]?.payload?.label;

    return (
        <ChartTooltipContent
            {...props}
            payload={payload}
            label={typeof label === 'string' ? label : props.label}
            formatter={(value, _name, item) => (
                <MoneyTooltipRow
                    label={netWorthChartConfig.netWorth.label}
                    formatted={item.payload?.netWorthFormatted}
                    original={item.payload?.netWorthOriginalFormatted}
                    fallback={value}
                />
            )}
        />
    );
}

function MoneyTooltipRow({
    label,
    formatted,
    original,
    fallback,
}: {
    label: ReactNode;
    formatted: unknown;
    original: unknown;
    fallback: unknown;
}) {
    return (
        <span className="flex flex-1 items-center justify-between gap-2">
            <span className="text-muted-foreground">{label}</span>
            <span className="font-medium text-foreground tabular-nums">
                {typeof formatted === 'string' ? formatted : String(fallback)}
                {typeof original === 'string' ? (
                    <span className="font-normal text-muted-foreground">
                        {' '}
                        {original}
                    </span>
                ) : null}
            </span>
        </span>
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
