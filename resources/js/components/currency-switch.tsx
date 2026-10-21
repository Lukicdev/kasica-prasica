import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
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
        <ToggleGroup
            type="single"
            variant="outline"
            value={selected}
            aria-label="Display currency"
            data-test="display-currency"
            className={cn(className)}
            onValueChange={(next) => {
                const match = currencies.supported.find(
                    (currency) => currency.code === next,
                );

                if (match) {
                    select(match.code);
                }
            }}
        >
            {currencies.supported.map((currency) => (
                <ToggleGroupItem
                    key={currency.code}
                    value={currency.code}
                    aria-label={currency.name}
                    data-test={`display-currency-${currency.code}`}
                    disabled={pending !== null}
                >
                    {currency.code}
                </ToggleGroupItem>
            ))}
        </ToggleGroup>
    );
}
