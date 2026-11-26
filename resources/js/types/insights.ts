import type { OverviewFigure } from '@/types/overview';

export type InsightsMonth = {
    value: string;
    label: string;
};

export type InsightsTrendMonth = {
    value: string;
    label: string;
    short_label: string;
    income: OverviewFigure;
    spent: OverviewFigure;
};

export type InsightsTrends = {
    through: InsightsMonth;
    months: InsightsTrendMonth[];
};

export type InsightsAverages = {
    from: InsightsMonth;
    through: InsightsMonth;
    months: number;
    income: OverviewFigure;
    spent: OverviewFigure;
    budgets: InsightsBudgetAverage[];
};

export type InsightsBudgetAverage = {
    id: number;
    name: string;
    icon: string | null;
    color: string | null;
    spent: OverviewFigure;
};

export type InsightsNetWorthMonth = {
    value: string;
    label: string;
    short_label: string;
    net_worth: OverviewFigure;
};

export type InsightsNetWorth = {
    through: InsightsMonth;
    months: InsightsNetWorthMonth[];
    previous: {
        month: string;
        day: number;
        balance: OverviewFigure;
        percent: number | null;
    };
};
