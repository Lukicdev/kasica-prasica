import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { FormSelect } from '@/components/form-select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { cn } from '@/lib/utils';
import { update } from '@/routes/display-currency';

type CurrencySwitchProps = {
    className?: string;
};

export function CurrencySwitch({ className }: CurrencySwitchProps) {
    const { auth, currencies } = usePage().props;
    const saved = auth.user?.display_currency;
    const [pending, setPending] = useState<string | null>(null);
    const known = currencies.supported.some((currency) => currency.code === saved);

    if (!auth.user || !saved || !known) {
        return null;
    }

    const selected = pending ?? saved;

    const select = (currency: string): void => {
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

    if (currencies.supported.length > 2) {
        return (
            <FormSelect
                id="display-currency"
                value={selected}
                data-test="display-currency"
                className={cn('w-28', className)}
                disabled={pending !== null}
                options={currencies.supported.map((currency) => ({
                    value: currency.code,
                    label: currency.code,
                }))}
                onValueChange={(next) => {
                    if (currencies.supported.some((currency) => currency.code === next)) {
                        select(next);
                    }
                }}
            />
        );
    }

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
