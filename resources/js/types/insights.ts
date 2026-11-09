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

export type InsightsNetWorthMonth = {
    value: string;
    label: string;
    short_label: string;
    net_worth: OverviewFigure;
};

export type InsightsNetWorth = {
    through: InsightsMonth;
    months: InsightsNetWorthMonth[];
};
