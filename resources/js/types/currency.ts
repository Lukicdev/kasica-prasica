export type SupportedCurrency = {
    code: string;
    name: string;
    rateToBase: string | null;
};

export type Currencies = {
    base: string;
    supported: SupportedCurrency[];
};

export type DisplayedMoney = {
    amount: string;
    currency: string;
    formatted: string;
    original_amount: string | null;
    original_currency: string | null;
    original_formatted: string | null;
};
