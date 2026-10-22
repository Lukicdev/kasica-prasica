/**
 * Decimal money that rounds halves away from zero, matching the PHP Decimal class.
 * Preview figures on the transaction form use this so they agree with what is saved.
 */

type Parts = {
    coefficient: bigint;
    scale: number;
};

const decimalPattern = /^-?\d+(\.\d+)?$/;

function parse(value: string): Parts | null {
    const trimmed = value.trim();

    if (!decimalPattern.test(trimmed)) {
        return null;
    }

    const negative = trimmed.startsWith('-');
    const absolute = negative ? trimmed.slice(1) : trimmed;
    const [whole, fraction = ''] = absolute.split('.');
    const digits = `${whole}${fraction}`.replace(/^0+(?=\d)/, '');
    const coefficient = BigInt(digits);

    return {
        coefficient: negative ? -coefficient : coefficient,
        scale: fraction.length,
    };
}

function round(parts: Parts, scale: number): Parts {
    if (scale >= parts.scale) {
        return {
            coefficient: parts.coefficient * 10n ** BigInt(scale - parts.scale),
            scale,
        };
    }

    const negative = parts.coefficient < 0n;
    const power = 10n ** BigInt(parts.scale - scale);
    const absolute = negative ? -parts.coefficient : parts.coefficient;
    let quotient = absolute / power;
    const remainder = absolute % power;
    const half = power / 2n + (power % 2n === 0n ? 0n : 1n);

    if (remainder >= half) {
        quotient += 1n;
    }

    return {
        coefficient: negative ? -quotient : quotient,
        scale,
    };
}

function toAmount(parts: Parts): string {
    const rounded = round(parts, 2);
    const negative = rounded.coefficient < 0n;
    const digits = (negative ? -rounded.coefficient : rounded.coefficient)
        .toString()
        .padStart(3, '0');
    const whole = digits.slice(0, -2).replace(/^0+(?=\d)/, '');
    const fraction = digits.slice(-2);

    return `${negative ? '-' : ''}${whole}.${fraction}`;
}

function operate(
    left: string,
    right: string,
    combine: (left: Parts, right: Parts) => Parts | null,
): string | null {
    const parsedLeft = parse(left);
    const parsedRight = parse(right);

    if (!parsedLeft || !parsedRight) {
        return null;
    }

    const combined = combine(parsedLeft, parsedRight);

    return combined ? toAmount(combined) : null;
}

export function canonicalAmount(value: string): string | null {
    const parsed = parse(value.trim());

    if (!parsed || parsed.coefficient < 0n) {
        return null;
    }

    return toAmount(parsed);
}

export function normalizeAmount(value: string): string | null {
    const parsed = parse(value);

    if (!parsed || parsed.coefficient === 0n) {
        return null;
    }

    return toAmount(parsed);
}

export function negateAmount(value: string): string | null {
    const parsed = parse(value);

    if (!parsed) {
        return null;
    }

    return toAmount({
        coefficient: -parsed.coefficient,
        scale: parsed.scale,
    });
}

export function addAmounts(left: string, right: string): string | null {
    return operate(left, right, (parsedLeft, parsedRight) => {
        const scale = Math.max(parsedLeft.scale, parsedRight.scale);

        return {
            coefficient:
                round(parsedLeft, scale).coefficient +
                round(parsedRight, scale).coefficient,
            scale,
        };
    });
}

export function subtractAmounts(left: string, right: string): string | null {
    return operate(left, right, (parsedLeft, parsedRight) => {
        const scale = Math.max(parsedLeft.scale, parsedRight.scale);

        return {
            coefficient:
                round(parsedLeft, scale).coefficient -
                round(parsedRight, scale).coefficient,
            scale,
        };
    });
}

export function multiplyAmount(amount: string, rate: string): string | null {
    return operate(amount, rate, (parsedAmount, parsedRate) => ({
        coefficient: parsedAmount.coefficient * parsedRate.coefficient,
        scale: parsedAmount.scale + parsedRate.scale,
    }));
}

export function divideAmount(amount: string, rate: string): string | null {
    const parsedAmount = parse(amount);
    const parsedRate = parse(rate);

    if (!parsedAmount || !parsedRate || parsedRate.coefficient === 0n) {
        return null;
    }

    const scale = 2;
    const power = scale + parsedRate.scale - parsedAmount.scale;
    let numerator = parsedAmount.coefficient;
    let denominator = parsedRate.coefficient;

    if (power > 0) {
        numerator *= 10n ** BigInt(power);
    } else if (power < 0) {
        denominator *= 10n ** BigInt(-power);
    }

    const negative = numerator < 0n !== denominator < 0n;
    const absoluteNumerator = numerator < 0n ? -numerator : numerator;
    const absoluteDenominator = denominator < 0n ? -denominator : denominator;
    let quotient = absoluteNumerator / absoluteDenominator;
    const remainder = absoluteNumerator % absoluteDenominator;
    const half =
        absoluteDenominator / 2n + (absoluteDenominator % 2n === 0n ? 0n : 1n);

    if (remainder >= half) {
        quotient += 1n;
    }

    return toAmount({
        coefficient: negative ? -quotient : quotient,
        scale,
    });
}

export function convertAmount(
    amount: string,
    from: string,
    to: string,
    rates: Record<string, string>,
): string | null {
    if (from === to) {
        return normalizeAmount(amount);
    }

    const fromRate = rates[from];
    const toRate = rates[to];

    if (!fromRate || !toRate) {
        return null;
    }

    const inBase = multiplyAmount(amount, fromRate);

    if (!inBase) {
        return null;
    }

    if (toRate === '1') {
        return inBase;
    }

    return divideAmount(inBase, toRate);
}

export function formatMoney(amount: string, currency: string): string | null {
    const normalized = parse(amount);

    if (!normalized) {
        return null;
    }

    const value = toAmount(normalized);
    const negative = value.startsWith('-');
    const [whole, fraction] = value.replace('-', '').split('.');
    const grouped = (whole ?? '0').replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return `${negative ? '-' : ''}${grouped}.${fraction} ${currency}`;
}

export function isNegativeAmount(amount: string): boolean {
    const parsed = parse(amount);

    return parsed !== null && parsed.coefficient < 0n;
}
