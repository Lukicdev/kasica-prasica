import type { AccountSummary, MoneyFigure } from '@/types/account';
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

export type OverviewStep = {
    key: 'account' | 'budget' | 'transaction';
    done: boolean;
};

export type OverviewPerson = {
    id: number | null;
    name: string;
    amount: OverviewFigure;
};

export type OverviewIncomeRow = {
    id: number | null;
    name: string;
    icon: string | null;
    color: string | null;
    description: string | null;
    archived: boolean;
    amount: OverviewFigure;
    people: OverviewPerson[];
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
};

export type OverviewExpenseTotal = {
    spent: OverviewFigure;
    budget: OverviewFigure;
    difference: OverviewFigure;
    status: OverviewStanding;
    status_label: string;
};

export type OverviewTotals = {
    income: OverviewFigure;
    spent: OverviewFigure;
    budget: OverviewFigure;
    difference: OverviewFigure;
    leftover: OverviewFigure;
    unallocated: OverviewFigure;
};

export type OverviewDrilldownTransaction = {
    id: number;
    date: string;
    date_label: string;
    note: string | null;
    account: string;
    account_icon: string | null;
    person: string | null;
    amount: OverviewFigure;
};

export type OverviewExpenseSort = 'category' | 'overspend';

export type OverviewDrilldown = {
    id: number;
    name: string;
    icon: string | null;
    color: string | null;
    description: string | null;
    kind: 'income' | 'expense';
    total: OverviewFigure;
    budget: OverviewFigure | null;
    difference: OverviewFigure | null;
    status: OverviewStanding | null;
    status_label: string | null;
    transactions: OverviewDrilldownTransaction[];
};

export type OverviewAccounts = {
    items: AccountSummary[];
    total: MoneyFigure;
};
