import { Head, Link } from '@inertiajs/react';
import { ArrowDownWideNarrow, ArrowUpWideNarrow, Plus } from 'lucide-react';
import { useId, useState, type ComponentProps } from 'react';
import { Area, AreaChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { EntityIcon } from '@/components/entity-icon';
import { MoneyAmount } from '@/components/money-amount';
import { MonthNav } from '@/components/month-nav';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    ChartContainer,
    ChartLegend,
    ChartLegendContent,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { edit as editCategory } from '@/routes/categories';
import { index as insights } from '@/routes/insights';
import { create as createTransaction } from '@/routes/transactions';
import type { MoneyFigure } from '@/types/account';
import type {
    OverviewExpenseRow,
    OverviewFigure,
    OverviewFlow,
    OverviewMonth,
    OverviewSummary,
    OverviewTotals,
} from '@/types/overview';

function monthHref(month: string) {
    return dashboard({ query: { month } });
}

export default function Overview({
    month,
    expenses,
    totals,
    summary,
    flow,
    balance,
}: {
    month: OverviewMonth;
    expenses: { rows: OverviewExpenseRow[] };
    totals: OverviewTotals;
    summary: OverviewSummary;
    flow: OverviewFlow;
    balance: MoneyFigure;
}) {
    return (
        <>
            <Head title="Overview" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Overview
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {month.label}. Income, spending and the plan, in the
                            display currency from the header.
                        </p>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                        <Button
                            asChild
                            variant="outline"
                            className="h-11 w-full sm:w-auto md:h-9"
                        >
                            <Link href={insights()}>Insights</Link>
                        </Button>
                        <Button
                            asChild
                            className="h-11 w-full sm:w-auto md:h-9"
                        >
                            <Link
                                href={createTransaction({
                                    query: { date: month.entry_date },
                                })}
                                data-test="overview-add-transaction"
                            >
                                <Plus data-icon="inline-start" />
                                Add a transaction
                            </Link>
                        </Button>
                    </div>
                </section>

                <MonthSummary
                    month={month}
                    totals={totals}
                    summary={summary}
                    balance={balance}
                />

                <MonthPicture month={month} rows={expenses.rows} flow={flow} />
            </div>
        </>
    );
}

function flowDirection(
    figure: OverviewFigure,
    direction: 'income' | 'expense',
): 'income' | 'expense' | null {
    return figure.base_amount === '0.00' ? null : direction;
}

function savingsRateClass(rate: number | null): string {
    if (rate === null) {
        return 'text-muted-foreground';
    }

    if (rate > 0) {
        return 'text-success';
    }

    if (rate < 0) {
        return 'text-destructive';
    }

    return 'text-foreground';
}

function MonthSummary({
    month,
    totals,
    summary,
    balance,
}: {
    month: OverviewMonth;
    totals: OverviewTotals;
    summary: OverviewSummary;
    balance: MoneyFigure;
}) {
    return (
        <section
            aria-label="Month summary"
            data-test="overview-summary"
            className="grid overflow-hidden rounded-xl border border-sidebar-border/70 bg-card lg:grid-cols-[minmax(0,1fr)_20rem] dark:border-sidebar-border"
        >
            <div className="flex flex-col gap-5 p-4 sm:p-5">
                <div className="flex flex-col gap-1">
                    <span className="text-sm text-muted-foreground">
                        Savings rate
                    </span>
                    <span
                        className={cn(
                            'text-4xl font-semibold tracking-tight tabular-nums sm:text-5xl',
                            savingsRateClass(summary.savings_rate),
                        )}
                        data-test="overview-savings-rate"
                    >
                        {summary.savings_rate === null
                            ? '—'
                            : `${summary.savings_rate}%`}
                    </span>
                </div>
                <dl className="grid gap-4 sm:grid-cols-3">
                    <div
                        className="flex flex-col gap-1"
                        data-test="overview-summary-balance"
                    >
                        <dt className="text-sm text-muted-foreground">
                            Total balance
                        </dt>
                        <dd className="text-base font-medium tabular-nums">
                            <MoneyAmount formatted={balance.formatted} />
                        </dd>
                    </div>
                    <div
                        className="flex flex-col gap-1"
                        data-test="overview-summary-income"
                    >
                        <dt className="text-sm text-muted-foreground">
                            Monthly income
                        </dt>
                        <dd className="text-base font-medium tabular-nums">
                            <MoneyAmount
                                formatted={totals.income.formatted}
                                originalFormatted={
                                    totals.income.original_formatted
                                }
                                direction={flowDirection(
                                    totals.income,
                                    'income',
                                )}
                            />
                        </dd>
                    </div>
                    <div
                        className="flex flex-col gap-1"
                        data-test="overview-summary-expenses"
                    >
                        <dt className="text-sm text-muted-foreground">
                            Monthly expenses
                        </dt>
                        <dd className="text-base font-medium tabular-nums">
                            <MoneyAmount
                                formatted={totals.spent.formatted}
                                originalFormatted={
                                    totals.spent.original_formatted
                                }
                                direction={flowDirection(
                                    totals.spent,
                                    'expense',
                                )}
                            />
                        </dd>
                    </div>
                </dl>
                {summary.pace ? (
                    <p
                        className="text-sm text-muted-foreground"
                        data-test="overview-pace"
                    >
                        At this pace, you'll spend{' '}
                        <MoneyAmount
                            formatted={summary.pace.formatted}
                            originalFormatted={summary.pace.original_formatted}
                            className="font-medium text-foreground"
                        />{' '}
                        by month end.
                    </p>
                ) : null}
            </div>
            <div className="flex items-center justify-center border-t border-sidebar-border/70 p-4 lg:border-t-0 lg:border-l dark:border-sidebar-border">
                <MonthNav
                    value={month.value}
                    previousHref={monthHref(month.previous)}
                    nextHref={monthHref(month.next)}
                    hrefFor={monthHref}
                />
            </div>
        </section>
    );
}

function barWidth(row: OverviewExpenseRow): number {
    const spent = Number(row.spent.base_amount);
    const budget = Number(row.budget.base_amount);

    if (!Number.isFinite(spent) || spent <= 0) {
        return 0;
    }

    if (!Number.isFinite(budget) || budget <= 0) {
        return 100;
    }

    return Math.min(100, (spent / budget) * 100);
}

function barTone(row: OverviewExpenseRow): 'over' | 'near' | 'under' {
    if (row.status === 'over') {
        return 'over';
    }

    if (row.budget_percent !== null && row.budget_percent >= 80) {
        return 'near';
    }

    return 'under';
}

const barFill = {
    over: 'bg-destructive',
    near: 'bg-primary',
    under: 'bg-success',
} as const;

const barBadge = {
    over: 'destructive',
    near: 'default',
    under: 'success',
} as const;

type SpendingOrder = 'highest' | 'lowest';

function MonthPicture({
    month,
    rows,
    flow,
}: {
    month: OverviewMonth;
    rows: OverviewExpenseRow[];
    flow: OverviewFlow;
}) {
    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <SpendingBars month={month} rows={rows} />
            <BalanceFlow flow={flow} />
        </div>
    );
}

function SpendingBars({
    month,
    rows,
}: {
    month: OverviewMonth;
    rows: OverviewExpenseRow[];
}) {
    const [order, setOrder] = useState<SpendingOrder>('highest');
    const bars = rows
        .filter(
            (row) =>
                row.spent.base_amount !== '0.00' ||
                row.budget.base_amount !== '0.00',
        )
        .sort((left, right) => {
            const bySpent =
                Number(left.spent.base_amount) -
                Number(right.spent.base_amount);
            const directed = order === 'lowest' ? bySpent : -bySpent;

            if (directed !== 0) {
                return directed;
            }

            return left.name.localeCompare(right.name);
        });

    return (
        <section
            className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
            data-test="overview-spending"
        >
            <div className="flex items-center justify-between gap-3">
                <h2 className="font-medium">Spending by category</h2>
                <Button
                    type="button"
                    variant="ghost"
                    onClick={() =>
                        setOrder((current) =>
                            current === 'highest' ? 'lowest' : 'highest',
                        )
                    }
                    aria-pressed={order === 'lowest'}
                    data-test="spending-order"
                >
                    {order === 'highest' ? (
                        <ArrowDownWideNarrow data-icon="inline-start" />
                    ) : (
                        <ArrowUpWideNarrow data-icon="inline-start" />
                    )}
                    {order === 'highest' ? 'Highest first' : 'Lowest first'}
                </Button>
            </div>
            {bars.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No spending or budgets in {month.label}.
                </p>
            ) : (
                <ul
                    className="flex max-h-80 flex-col gap-4 overflow-y-auto pr-4"
                    aria-label={`Spending by category in ${month.label}`}
                >
                    {bars.map((row) => {
                        const tone = barTone(row);

                        return (
                            <li
                                key={row.id ?? row.name}
                                className="flex flex-col gap-2"
                                data-test="spending-bar"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <CategoryName row={row} />
                                    <span className="flex flex-col items-end gap-1">
                                        <span className="flex items-center gap-2">
                                            <span className="flex flex-col items-end font-medium tabular-nums">
                                                <MoneyAmount
                                                    formatted={
                                                        row.spent.formatted
                                                    }
                                                    originalFormatted={
                                                        row.spent
                                                            .original_formatted
                                                    }
                                                />
                                                <span className="text-xs text-muted-foreground">
                                                    of {row.budget.formatted}
                                                </span>
                                            </span>
                                            {row.budget_percent === null ? (
                                                <Badge variant="destructive">
                                                    No budget
                                                </Badge>
                                            ) : (
                                                <Badge
                                                    variant={barBadge[tone]}
                                                    data-test="budget-percent"
                                                >
                                                    {row.budget_percent}%
                                                </Badge>
                                            )}
                                        </span>
                                    </span>
                                </div>
                                <div className="h-2 overflow-hidden rounded-full bg-background">
                                    <div
                                        className={cn(
                                            'h-full rounded-full',
                                            barFill[tone],
                                        )}
                                        style={{ width: `${barWidth(row)}%` }}
                                    />
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}
        </section>
    );
}

function BalanceFlow({ flow }: { flow: OverviewFlow }) {
    return (
        <section
            className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border"
            data-test="overview-flow"
        >
            <div className="flex items-start justify-between gap-3">
                <div className="flex flex-col gap-1">
                    <h2 className="font-medium">Balance flow</h2>
                    {flow.from && flow.to ? (
                        <p className="text-sm text-muted-foreground">
                            {flow.from} – {flow.to}
                        </p>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Household balance through the month.
                        </p>
                    )}
                </div>
                {flow.change ? (
                    <span
                        className="font-medium tabular-nums"
                        data-test="flow-change"
                    >
                        <MoneyAmount
                            formatted={flow.change.formatted}
                            originalFormatted={flow.change.original_formatted}
                            direction={
                                flow.change.base_amount === '0.00'
                                    ? null
                                    : flow.change.negative
                                      ? 'expense'
                                      : 'income'
                            }
                        />
                    </span>
                ) : null}
            </div>
            {flow.points.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No balance to plot for this month.
                </p>
            ) : (
                <FlowChart flow={flow} />
            )}
            {flow.previous ? (
                <p
                    className="text-sm text-muted-foreground"
                    data-test="flow-previous"
                >
                    In {flow.previous.month} on day {flow.previous.day}:{' '}
                    <MoneyAmount
                        formatted={flow.previous.balance.formatted}
                        originalFormatted={
                            flow.previous.balance.original_formatted
                        }
                        className="font-medium text-foreground"
                    />
                    {flow.previous.percent === null ? null : (
                        <span
                            className={cn(
                                'font-medium',
                                flow.previous.percent > 0 && 'text-success',
                                flow.previous.percent < 0 && 'text-destructive',
                            )}
                        >
                            {' '}
                            ({flow.previous.percent > 0 ? '+' : ''}
                            {flow.previous.percent}%)
                        </span>
                    )}
                </p>
            ) : null}
        </section>
    );
}

const flowChartConfig = {
    balance: {
        label: 'This month',
        color: 'var(--chart-1)',
    },
    previous: {
        label: 'Previous month',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

type FlowChartRow = {
    day: string;
    balance: number | null;
    previous: number | null;
    balanceFormatted: string | null;
    previousFormatted: string | null;
};

function flowChartData(flow: OverviewFlow): FlowChartRow[] {
    const days = Math.max(
        0,
        ...flow.points.map((point) => point.day),
        ...flow.previous_points.map((point) => point.day),
    );
    const balance = new Map(flow.points.map((point) => [point.day, point]));
    const previous = new Map(
        flow.previous_points.map((point) => [point.day, point]),
    );

    return Array.from({ length: days }, (_, index) => {
        const day = index + 1;
        const current = balance.get(day);
        const earlier = previous.get(day);

        return {
            day: String(day),
            balance: current ? Number(current.amount) : null,
            previous: earlier ? Number(earlier.amount) : null,
            balanceFormatted: current?.formatted ?? null,
            previousFormatted: earlier?.formatted ?? null,
        };
    });
}

function flowSeriesRank(item: { dataKey?: unknown; name?: unknown }): number {
    return (item.dataKey ?? item.name) === 'balance' ? 0 : 1;
}

function FlowTooltipContent({
    payload,
    ...props
}: ComponentProps<typeof ChartTooltipContent>) {
    const ordered = payload
        ? [...payload].sort(
              (left, right) => flowSeriesRank(left) - flowSeriesRank(right),
          )
        : payload;

    return (
        <ChartTooltipContent
            {...props}
            payload={ordered}
            labelFormatter={(value) => `Day ${value}`}
            formatter={(value, name, item) => {
                const key =
                    name === 'previous'
                        ? 'previousFormatted'
                        : 'balanceFormatted';
                const formatted = item.payload?.[key];

                return (
                    <span className="flex flex-1 items-center justify-between gap-2">
                        <span className="text-muted-foreground">
                            {
                                flowChartConfig[
                                    name === 'previous' ? 'previous' : 'balance'
                                ].label
                            }
                        </span>
                        <span className="font-medium text-foreground tabular-nums">
                            {typeof formatted === 'string'
                                ? formatted
                                : String(value)}
                        </span>
                    </span>
                );
            }}
        />
    );
}

function FlowChart({ flow }: { flow: OverviewFlow }) {
    const gradientId = useId().replace(/:/g, '');
    const data = flowChartData(flow);
    const hasPrevious = flow.previous_points.length > 0;

    return (
        <ChartContainer
            config={flowChartConfig}
            className="aspect-auto h-64 w-full"
            aria-label={
                flow.from && flow.to
                    ? `Balance from ${flow.from} to ${flow.to}`
                    : 'Balance through the month'
            }
        >
            <AreaChart
                accessibilityLayer
                data={data}
                margin={{ left: 12, right: 12 }}
            >
                <CartesianGrid vertical={false} />
                <XAxis
                    dataKey="day"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={16}
                />
                <YAxis
                    dataKey="balance"
                    tickLine={false}
                    axisLine={false}
                    tickMargin={8}
                    minTickGap={16}
                />
                <ChartTooltip cursor={false} content={<FlowTooltipContent />} />
                <ChartLegend content={<ChartLegendContent />} />
                <defs>
                    <linearGradient
                        id={`${gradientId}-balance`}
                        x1="0"
                        y1="0"
                        x2="0"
                        y2="1"
                    >
                        <stop
                            offset="5%"
                            stopColor="var(--color-balance)"
                            stopOpacity={0.8}
                        />
                        <stop
                            offset="95%"
                            stopColor="var(--color-balance)"
                            stopOpacity={0.1}
                        />
                    </linearGradient>
                    {hasPrevious ? (
                        <linearGradient
                            id={`${gradientId}-previous`}
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="5%"
                                stopColor="var(--color-previous)"
                                stopOpacity={0.8}
                            />
                            <stop
                                offset="95%"
                                stopColor="var(--color-previous)"
                                stopOpacity={0.1}
                            />
                        </linearGradient>
                    ) : null}
                </defs>
                {hasPrevious ? (
                    <Area
                        dataKey="previous"
                        type="natural"
                        fill={`url(#${gradientId}-previous)`}
                        fillOpacity={0.4}
                        stroke="var(--color-previous)"
                    />
                ) : null}
                <Area
                    dataKey="balance"
                    type="natural"
                    fill={`url(#${gradientId}-balance)`}
                    fillOpacity={0.4}
                    stroke="var(--color-balance)"
                />
            </AreaChart>
        </ChartContainer>
    );
}

function CategoryName({
    row,
}: {
    row: {
        id: number | null;
        name: string;
        icon: string | null;
        color: string | null;
        archived: boolean;
    };
}) {
    return (
        <span className="flex flex-wrap items-center gap-2">
            <EntityIcon name={row.icon} color={row.color} />
            {row.id === null ? (
                <span>{row.name}</span>
            ) : (
                <Link
                    href={editCategory(row.id)}
                    className="underline-offset-4 hover:underline"
                    data-test="open-category"
                >
                    {row.name}
                </Link>
            )}
            {row.archived ? <Badge variant="outline">Archived</Badge> : null}
        </span>
    );
}

Overview.layout = {
    breadcrumbs: [
        {
            title: 'Overview',
            href: dashboard(),
        },
    ],
};
