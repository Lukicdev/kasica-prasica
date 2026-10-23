export type TransferAccountOption = {
    id: number;
    name: string;
    icon: string | null;
    currency: string;
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
    currency: string;
    exchange_rate: string;
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
    currency: string;
};

export type TransferFormPage = {
    transfer: TransferValues | null;
    accounts: TransferAccountOption[];
    people: TransferPersonOption[];
    defaults: TransferDefaults;
    needs_second_account: boolean;
};
