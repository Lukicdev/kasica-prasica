import type { CurrencyCode, DisplayedMoney } from '@/types/currency';

export type BudgetMonth = {
    value: string;
    label: string;
    previous: string;
    next: string;
};

export type BudgetCopyFrom = {
    value: string;
    label: string;
};

export type BudgetFigure = {
    amount: string;
    currency: CurrencyCode;
    formatted: string;
    negative: boolean;
    base_amount: string;
};

export type BudgetCategory = {
    id: number;
    name: string;
    description: string | null;
    archived: boolean;
    amount: string;
    display: DisplayedMoney;
    updated_by: string | null;
};
