export type CurrencyCode = 'RSD' | 'EUR';

export type SupportedCurrency = {
    code: CurrencyCode;
    name: string;
};

export type Currencies = {
    base: CurrencyCode;
    supported: SupportedCurrency[];
    eurToRsd: string;
};

export type DisplayedMoney = {
    amount: string;
    currency: CurrencyCode;
    formatted: string;
    original_amount: string | null;
    original_currency: CurrencyCode | null;
    original_formatted: string | null;
};
