import type { OverviewFigure } from '@/types/overview';

export type InsightsMonth = {
    value: string;
    label: string;
};

export type InsightsDirection = 'up' | 'down' | 'same';

export type InsightsComparisonRow = {
    id: number | null;
    name: string;
    icon: string | null;
    description: string | null;
    archived: boolean;
    left: OverviewFigure;
    right: OverviewFigure;
    change: OverviewFigure;
    direction: InsightsDirection;
    direction_label: string;
};

export type InsightsComparison = {
    left: InsightsMonth;
    right: InsightsMonth;
    rows: InsightsComparisonRow[];
    total: {
        left: OverviewFigure;
        right: OverviewFigure;
        change: OverviewFigure;
        direction: InsightsDirection;
        direction_label: string;
    };
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
