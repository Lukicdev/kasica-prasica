import type { CurrencyCode } from '@/types/currency';

export type TransferAccountOption = {
    id: number;
    name: string;
    currency: CurrencyCode;
    balance: string;
    archived: boolean;
};

export type TransferPersonOption = {
    id: number;
    name: string;
};

export type TransferValues = {
    id: number;
    amount: string;
    currency: CurrencyCode;
    from_account_id: number;
    to_account_id: number | null;
    account_amount: string;
    destination_amount: string | null;
    booked_on: string;
    person_id: number | null;
    note: string | null;
};

export type TransferDefaults = {
    from_account_id: number | null;
    to_account_id: number | null;
    person_id: number;
    date: string;
    currency: CurrencyCode;
};

export type TransferFormPage = {
    transfer: TransferValues | null;
    accounts: TransferAccountOption[];
    people: TransferPersonOption[];
    defaults: TransferDefaults;
    needs_second_account: boolean;
};
