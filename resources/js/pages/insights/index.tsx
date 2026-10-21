import { Form, Head } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Minus } from 'lucide-react';
import { EntityIcon } from '@/components/entity-icon';
import { MoneyAmount } from '@/components/money-amount';
import { MonthPicker } from '@/components/month-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { index as insights } from '@/routes/insights';
import type {
    InsightsComparison,
    InsightsComparisonRow,
    InsightsDirection,
    InsightsTrendMonth,
    InsightsTrends,
} from '@/types/insights';
import type { OverviewFigure } from '@/types/overview';

export default function Insights({
    comparison,
    trends,
}: {
    comparison: InsightsComparison;
    trends: InsightsTrends;
}) {
    return (
        <>
            <Head title="Insights" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h1 className="text-lg font-semibold tracking-tight">
                        Insights
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Compare two months, and follow income against spending.
                    </p>
                </section>

                <Comparison comparison={comparison} />
                <Trends trends={trends} />
            </div>
        </>
    );
}

function Comparison({ comparison }: { comparison: InsightsComparison }) {
    return (
        <section className="flex flex-col gap-4" data-test="comparison">
            <div className="flex flex-col gap-1">
                <h2 className="font-medium">Month against month</h2>
                <p className="text-sm text-muted-foreground">
                    Spent per category. Change is {comparison.right.label} minus{' '}
                    {comparison.left.label}.
                </p>
            </div>
            <Form
                {...insights.form()}
                className="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border"
            >
                <div className="grid gap-2">
                    <Label htmlFor="left">First month</Label>
                    <MonthPicker
                        id="left"
                        name="left"
                        value={comparison.left.value}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="right">Second month</Label>
                    <MonthPicker
                        id="right"
                        name="right"
                        value={comparison.right.value}
                    />
                </div>
                <Button type="submit" className="h-11 sm:col-span-2">
                    Compare
                </Button>
            </Form>
            <ul className="flex flex-col gap-3">
                {comparison.rows.map((row) => (
                    <ComparisonRow
                        key={row.id ?? row.name}
                        row={row}
                        leftLabel={comparison.left.label}
                        rightLabel={comparison.right.label}
                    />
                ))}
            </ul>
            <ComparisonTotal comparison={comparison} />
        </section>
    );
}

function ComparisonRow({
    row,
    leftLabel,
    rightLabel,
}: {
    row: InsightsComparisonRow;
    leftLabel: string;
    rightLabel: string;
}) {
    return (
        <li
            className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test="comparison-row"
        >
            <div className="flex flex-wrap items-center gap-2">
                <EntityIcon name={row.icon} />
                <span className="font-medium">{row.name}</span>
                {row.archived ? (
                    <Badge variant="outline">Archived</Badge>
                ) : null}
            </div>
            {row.description ? (
                <p className="text-sm text-muted-foreground">
                    {row.description}
                </p>
            ) : null}
            <dl className="grid gap-2 text-sm sm:grid-cols-3">
                <Figure label={leftLabel} figure={row.left} />
                <Figure label={rightLabel} figure={row.right} />
                <Change
                    figure={row.change}
                    direction={row.direction}
                    label={row.direction_label}
                />
            </dl>
        </li>
    );
}

function ComparisonTotal({ comparison }: { comparison: InsightsComparison }) {
    return (
        <div
            className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test="comparison-total"
        >
            <span className="font-medium">Total spent</span>
            <dl className="grid gap-2 text-sm sm:grid-cols-3">
                <Figure
                    label={comparison.left.label}
                    figure={comparison.total.left}
                />
                <Figure
                    label={comparison.right.label}
                    figure={comparison.total.right}
                />
                <Change
                    figure={comparison.total.change}
                    direction={comparison.total.direction}
                    label={comparison.total.direction_label}
                />
            </dl>
        </div>
    );
}

function Figure({ label, figure }: { label: string; figure: OverviewFigure }) {
    return (
        <div className="flex flex-col gap-1">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">
                <MoneyAmount
                    formatted={figure.formatted}
                    originalFormatted={figure.original_formatted}
                />
            </dd>
        </div>
    );
}

function Change({
    figure,
    direction,
    label,
}: {
    figure: OverviewFigure;
    direction: InsightsDirection;
    label: string;
}) {
    const icon =
        direction === 'up' ? (
            <ArrowUp aria-hidden="true" />
        ) : direction === 'down' ? (
            <ArrowDown aria-hidden="true" />
        ) : (
            <Minus aria-hidden="true" />
        );

    return (
        <div className="flex flex-col gap-1">
            <dt className="text-muted-foreground">Change</dt>
            <dd className="flex flex-col items-start gap-1 font-medium">
                <MoneyAmount
                    formatted={figure.formatted}
                    originalFormatted={figure.original_formatted}
                />
                <Badge
                    variant={
                        direction === 'up'
                            ? 'destructive'
                            : direction === 'down'
                              ? 'success'
                              : 'outline'
                    }
                    data-test="change-direction"
                >
                    {icon}
                    {label}
                </Badge>
            </dd>
        </div>
    );
}

function Trends({ trends }: { trends: InsightsTrends }) {
    return (
        <section className="flex flex-col gap-4" data-test="trends">
            <div className="flex flex-col gap-1">
                <h2 className="font-medium">Income and spending</h2>
                <p className="text-sm text-muted-foreground">
                    The last 12 months, through {trends.through.label}.
                </p>
            </div>
            <TrendChart months={trends.months} />
        </section>
    );
}

function TrendChart({ months }: { months: InsightsTrendMonth[] }) {
    const width = Math.max(months.length * 56, 280);
    const height = 180;
    const baseline = 150;
    const peak = Math.max(
        1,
        ...months.flatMap((month) => [
            barValue(month.income.base_amount),
            barValue(month.spent.base_amount),
        ]),
    );

    return (
        <div className="overflow-x-auto rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div className="mb-3 flex flex-wrap gap-4 text-sm">
                <span className="inline-flex items-center gap-2">
                    <svg width="16" height="16" aria-hidden="true">
                        <rect
                            width="16"
                            height="16"
                            className="fill-foreground"
                        />
                    </svg>
                    Income
                </span>
                <span className="inline-flex items-center gap-2">
                    <svg width="16" height="16" aria-hidden="true">
                        <defs>
                            <pattern
                                id="trend-legend-hatch"
                                patternUnits="userSpaceOnUse"
                                width="6"
                                height="6"
                            >
                                <path
                                    d="M0 6 L6 0"
                                    className="stroke-foreground"
                                    strokeWidth="1"
                                />
                            </pattern>
                        </defs>
                        <rect
                            width="16"
                            height="16"
                            fill="url(#trend-legend-hatch)"
                            className="stroke-foreground"
                        />
                    </svg>
                    Spending
                </span>
            </div>
            <svg
                viewBox={`0 0 ${width} ${height}`}
                role="img"
                aria-label="Bar chart of income and spending"
                className="h-44 w-full min-w-[20rem] text-foreground"
            >
                <defs>
                    <pattern
                        id="trend-spent-hatch"
                        patternUnits="userSpaceOnUse"
                        width="6"
                        height="6"
                    >
                        <path
                            d="M0 6 L6 0"
                            className="stroke-foreground"
                            strokeWidth="1"
                        />
                    </pattern>
                </defs>
                <line
                    x1="0"
                    x2={width}
                    y1={baseline}
                    y2={baseline}
                    className="stroke-border"
                />
                {months.map((month, index) => {
                    const groupX = index * 56 + 8;
                    const incomeHeight =
                        (barValue(month.income.base_amount) / peak) * 120;
                    const spentHeight =
                        (barValue(month.spent.base_amount) / peak) * 120;

                    return (
                        <g key={month.value}>
                            <rect
                                x={groupX}
                                y={baseline - incomeHeight}
                                width="16"
                                height={incomeHeight}
                                className="fill-foreground"
                            >
                                <title>
                                    {month.label} income{' '}
                                    {month.income.formatted}
                                </title>
                            </rect>
                            <rect
                                x={groupX + 20}
                                y={baseline - spentHeight}
                                width="16"
                                height={spentHeight}
                                fill="url(#trend-spent-hatch)"
                                className="stroke-foreground"
                            >
                                <title>
                                    {month.label} spending{' '}
                                    {month.spent.formatted}
                                </title>
                            </rect>
                            <text
                                x={groupX + 18}
                                y={baseline + 18}
                                textAnchor="middle"
                                className="fill-muted-foreground text-[10px]"
                            >
                                {month.short_label}
                            </text>
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

function barValue(amount: string): number {
    const value = Number(amount);

    if (!Number.isFinite(value) || value < 0) {
        return 0;
    }

    return value;
}

Insights.layout = {
    breadcrumbs: [
        {
            title: 'Insights',
            href: insights(),
        },
    ],
};
