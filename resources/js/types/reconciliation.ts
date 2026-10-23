import type { MoneyFigure } from '@/types/account';

export type ReconciliationAccount = {
    id: number;
    name: string;
    currency: string;
    balance: MoneyFigure;
};

export type ReconciliationPerson = {
    id: number;
    name: string;
};

export type ReconciliationCreatePage = {
    account: ReconciliationAccount;
    people: ReconciliationPerson[];
    defaults: {
        person_id: number;
        date: string;
        note: string;
    };
};

export type AdjustmentAccountOption = {
    id: number;
    name: string;
    icon: string | null;
    currency: string;
    balance: string;
    archived: boolean;
};

export type AdjustmentValues = {
    id: number;
    amount: string;
    account_id: number;
    booked_on: string;
    person_id: number | null;
    note: string | null;
};

export type ReconciliationEditPage = {
    adjustment: AdjustmentValues;
    accounts: AdjustmentAccountOption[];
    people: ReconciliationPerson[];
};
