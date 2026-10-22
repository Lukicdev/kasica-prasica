import type { SupportedCurrency } from '@/types/currency';

export function isEnabledCurrency(
    value: string,
    supported: SupportedCurrency[],
): boolean {
    return supported.some((currency) => currency.code === value);
}

export function rateMap(
    supported: SupportedCurrency[],
    saved?: { currency: string; exchange_rate?: string | null } | null,
    selected?: string,
): Record<string, string> {
    const rates: Record<string, string> = {};

    for (const currency of supported) {
        if (currency.rateToBase !== null) {
            rates[currency.code] = currency.rateToBase;
        }
    }

    if (
        saved?.exchange_rate &&
        selected !== undefined &&
        selected === saved.currency
    ) {
        rates[saved.currency] = saved.exchange_rate;
    }

    return rates;
}
