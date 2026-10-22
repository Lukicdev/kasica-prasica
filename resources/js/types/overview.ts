import type { DisplayedMoney } from '@/types/currency';

export type OverviewStanding = 'over' | 'under' | 'on';

export type OverviewFigure = DisplayedMoney & {
    negative: boolean;
    base_amount: string;
};

export type OverviewMonth = {
    value: string;
    label: string;
    previous: string;
    next: string;
    entry_date: string;
};

export type OverviewExpenseRow = {
    id: number | null;
    name: string;
    icon: string | null;
    color: string | null;
    description: string | null;
    archived: boolean;
    spent: OverviewFigure;
    budget: OverviewFigure;
    difference: OverviewFigure;
    status: OverviewStanding;
    status_label: string;
    budget_percent: number | null;
};

export type OverviewTotals = {
    income: OverviewFigure;
    spent: OverviewFigure;
};

export type OverviewSummary = {
    savings_rate: number | null;
    pace: OverviewFigure | null;
};

export type OverviewFlowPoint = {
    day: number;
    amount: string;
    formatted: string;
};

export type OverviewFlow = {
    from: string | null;
    to: string | null;
    change: OverviewFigure | null;
    previous: {
        month: string;
        day: number;
        balance: OverviewFigure;
        percent: number | null;
    } | null;
    points: OverviewFlowPoint[];
    previous_points: OverviewFlowPoint[];
};
