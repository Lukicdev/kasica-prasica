export type MoneyFigure = {
    amount: string;
    currency: string;
    formatted: string;
    negative: boolean;
};

export type AccountSummary = {
    id: number;
    name: string;
    icon: string | null;
    type: string;
    type_label: string;
    currency: string;
    balance: MoneyFigure;
    archived: boolean;
    note: string | null;
};

export type AccountGroup = {
    type: string;
    label: string;
    accounts: AccountSummary[];
};

export type AccountTypeOption = {
    value: string;
    label: string;
};

export type AccountFormValues = {
    id: number;
    name: string;
    icon: string | null;
    type: string;
    currency: string;
    opening_balance: string;
    opening_balance_on: string;
    note: string | null;
    currency_locked: boolean;
    archived: boolean;
    can_delete: boolean;
};

export type AccountDetail = AccountFormValues & {
    type_label: string;
    can_transfer: boolean;
    balance: MoneyFigure;
    created_by: string | null;
    updated_by: string | null;
};

export type StatementLine = {
    id: number | null;
    kind: string;
    date: string;
    date_label: string;
    type: string | null;
    type_label: string;
    note: string | null;
    person: string | null;
    amount: (MoneyFigure & { original_formatted: string | null }) | null;
    running_balance: MoneyFigure;
};
