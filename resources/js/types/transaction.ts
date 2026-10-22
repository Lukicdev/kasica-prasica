import type { DisplayedMoney } from '@/types/currency';

export type TransactionSortColumn =
    | 'date'
    | 'type'
    | 'category'
    | 'note'
    | 'account'
    | 'person'
    | 'amount';

export type TransactionSort = {
    column: TransactionSortColumn;
    direction: 'asc' | 'desc';
};

export type TransactionMonth = {
    value: string;
    label: string;
    previous: string;
    next: string;
};

export type TransactionListFilters = {
    type: string | null;
    category: number | null;
    account: number | null;
    person: number | null;
    currency: string | null;
    search: string;
};

export type TransactionOption = {
    id: number;
    name: string;
};

export type TransactionCategoryOption = TransactionOption & {
    kind: string;
    label?: string;
    archived?: boolean;
};

export type TransactionAccountOption = TransactionOption & {
    currency: string;
    balance: string;
    archived: boolean;
};

export type TransactionRow = {
    id: number;
    date: string;
    date_label: string;
    type: string;
    type_label: string;
    category: string | null;
    category_icon: string | null;
    category_color: string | null;
    note: string | null;
    account: string;
    account_icon: string | null;
    destination: string | null;
    destination_icon: string | null;
    person: string | null;
    amount: DisplayedMoney;
    repeats: boolean;
};

export type TransactionPage = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type TransactionTotal = {
    amount: string;
    currency: string;
    formatted: string;
    negative: boolean;
    caption: string;
};

export type TransactionListOptions = {
    types: { value: string; label: string }[];
    categories: TransactionCategoryOption[];
    accounts: TransactionOption[];
    people: TransactionOption[];
    currencies: { value: string; label: string }[];
};

export type TransactionFormValues = {
    id: number;
    type: 'income' | 'expense';
    amount: string;
    refund: boolean;
    currency: string;
    exchange_rate: string;
    account_id: number;
    account_amount: string;
    category_id: number | null;
    booked_on: string;
    person_id: number | null;
    note: string | null;
    recurring: boolean;
};

export type TransactionDefaults = {
    type: 'income' | 'expense';
    account_id: number | null;
    category_id: number | null;
    person_id: number;
    date: string;
    currency: string;
};

export type TransactionFormCategory = {
    id: number;
    name: string;
    kind: 'income' | 'expense';
    label: string;
    archived: boolean;
};

export type TransactionFormPage = {
    transaction: TransactionFormValues | null;
    accounts: TransactionAccountOption[];
    categories: TransactionFormCategory[];
    people: TransactionOption[];
    defaults: TransactionDefaults;
    needs_account: boolean;
    confirmation: {
        id: number | null;
        account: string;
        formatted: string;
    } | null;
};

export type TransactionDetail = {
    id: number;
    type: string;
    type_label: string;
    refund: boolean;
    date: string;
    date_label: string;
    category: string | null;
    note: string | null;
    account: { id: number; name: string; icon: string | null };
    destination: { id: number; name: string; icon: string | null } | null;
    destination_amount: {
        amount: string;
        currency: string;
        formatted: string;
        negative: boolean;
    } | null;
    destination_balance: {
        amount: string;
        currency: string;
        formatted: string;
        negative: boolean;
    } | null;
    excluded_from_budget: boolean;
    person: string | null;
    amount: DisplayedMoney;
    account_amount: {
        amount: string;
        currency: string;
        formatted: string;
        negative: boolean;
    };
    balance: {
        amount: string;
        currency: string;
        formatted: string;
        negative: boolean;
    };
    updated_by: string | null;
    repeats: boolean;
    from_recurrence: boolean;
};
