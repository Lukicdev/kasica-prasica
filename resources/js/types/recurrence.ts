import type { DisplayedMoney } from '@/types/currency';

export type PendingRecurrence = {
    id: number;
    month: string;
    date: string;
    date_label: string;
    type: string;
    type_label: string;
    category: string | null;
    note: string | null;
    account: string;
    person: string | null;
    amount: DisplayedMoney;
    status_label: string;
};

export type RecurrenceFormValues = {
    id: number;
    month: string;
    month_label: string;
    type: 'income' | 'expense';
    type_label: string;
    category: string | null;
    person: string | null;
    note: string | null;
    amount: string;
    refund: boolean;
    currency: string;
    exchange_rate: string;
    account_id: number;
    booked_on: string;
    min_date: string;
    max_date: string;
};

export type RecurrenceFormPage = {
    recurrence: RecurrenceFormValues;
    accounts: {
        id: number;
        name: string;
        currency: string;
        balance: string;
        archived: boolean;
    }[];
    needs_account: boolean;
};
