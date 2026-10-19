import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { update } from '@/routes/display-currency';
import type { CurrencyCode } from '@/types/currency';

type CurrencySwitchProps = {
    className?: string;
};

export function CurrencySwitch({ className }: CurrencySwitchProps) {
    const { auth, currencies } = usePage().props;
    const saved = auth.user?.display_currency;
    const [pending, setPending] = useState<CurrencyCode | null>(null);

    if (!auth.user || (saved !== 'RSD' && saved !== 'EUR')) {
        return null;
    }

    const selected = pending ?? saved;

    const select = (currency: CurrencyCode): void => {
        if (currency === saved || pending !== null) {
            return;
        }

        router.patch(
            update.url(),
            { currency },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setPending(currency),
                onFinish: () => setPending(null),
            },
        );
    };

    return (
        <div
            role="radiogroup"
            aria-label="Display currency"
            data-test="display-currency"
            className={cn(
                'inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800',
                className,
            )}
        >
            {currencies.supported.map((currency) => {
                const isSelected = selected === currency.code;

                return (
                    <button
                        key={currency.code}
                        type="button"
                        role="radio"
                        aria-checked={isSelected}
                        aria-label={currency.name}
                        data-test={`display-currency-${currency.code}`}
                        disabled={pending !== null}
                        onClick={() => select(currency.code)}
                        className={cn(
                            'rounded-md px-3 py-1.5 text-sm transition-colors',
                            isSelected
                                ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                                : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                        )}
                    >
                        {currency.code}
                    </button>
                );
            })}
        </div>
    );
}
