import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import CurrencyController from '@/actions/App/Http/Controllers/Settings/CurrencyController';
import ExchangeRateController from '@/actions/App/Http/Controllers/Settings/ExchangeRateController';
import { FormSelect } from '@/components/form-select';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/currencies';

type CurrencyRow = {
    code: string;
    name: string;
    rateToBase: string | null;
    manualRate: string | null;
    source: 'base' | 'manual' | 'frankfurter';
    quotedOn: string | null;
    removable: boolean;
};

type CatalogEntry = {
    code: string;
    name: string;
};

function sourceLabel(currency: CurrencyRow, base: string): string {
    if (currency.source === 'base') {
        return 'Base currency';
    }

    if (currency.source === 'manual') {
        return 'Your rate';
    }

    return currency.quotedOn
        ? `Frankfurter, ${currency.quotedOn}`
        : `Frankfurter, in ${base}`;
}

export default function Currencies({
    currencySettings,
}: {
    currencySettings: {
        base: string;
        baseName: string;
        locked: boolean;
        currencies: CurrencyRow[];
        catalog: CatalogEntry[];
    };
}) {
    const { base, baseName, locked, currencies, catalog } = currencySettings;
    const [addFormKey, setAddFormKey] = useState(0);
    const currencyOptions = catalog.map((currency) => ({
        value: currency.code,
        label: `${currency.code} · ${currency.name}`,
    }));
    return (
        <>
            <Head title="Currencies" />

            <h1 className="sr-only">Currency settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Currencies"
                    description="Choose the currencies this household uses. Amounts are stored in the base currency."
                />

                <div className="space-y-2">
                    <p className="text-sm font-medium">Base currency</p>
                    <p className="text-sm text-muted-foreground">
                        {base} · {baseName}
                        {locked
                            ? '. This stays fixed once money has been entered.'
                            : '. You can change it until the first transaction or budget.'}
                    </p>
                    {locked ? null : (
                        <Form
                            {...CurrencyController.updateBase.form()}
                            className="flex flex-col gap-3 sm:flex-row sm:items-end"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid flex-1 gap-2">
                                        <Label htmlFor="base-currency">
                                            Change base currency
                                        </Label>
                                        <FormSelect
                                            id="base-currency"
                                            name="currency"
                                            defaultValue={base}
                                            options={[
                                                {
                                                    value: base,
                                                    label: `${base} · ${baseName}`,
                                                },
                                                ...currencyOptions,
                                            ]}
                                        />
                                        <InputError message={errors.currency} />
                                    </div>
                                    <Button
                                        type="submit"
                                        variant="outline"
                                        disabled={processing}
                                    >
                                        Save base
                                    </Button>
                                </>
                            )}
                        </Form>
                    )}
                </div>

                <div className="space-y-4">
                    {currencies.map((currency) => (
                        <div
                            key={currency.code}
                            className="space-y-3 rounded-md border p-4"
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="font-medium">
                                        {currency.code} · {currency.name}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {currency.rateToBase
                                            ? `1 ${currency.code} = ${currency.rateToBase} ${base}`
                                            : 'No rate yet'}
                                        . {sourceLabel(currency, base)}
                                    </p>
                                </div>
                                {currency.removable ? (
                                    <Form
                                        {...CurrencyController.destroy.form(
                                            currency.code,
                                        )}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="sm"
                                                disabled={processing}
                                            >
                                                Remove
                                            </Button>
                                        )}
                                    </Form>
                                ) : null}
                            </div>
                            {currency.source === 'base' ? null : (
                                <Form
                                    {...CurrencyController.update.form(
                                        currency.code,
                                    )}
                                    className="flex flex-col gap-3 sm:flex-row sm:items-end"
                                >
                                    {({ processing, errors }) => (
                                        <>
                                            <div className="grid flex-1 gap-2">
                                                <Label
                                                    htmlFor={`rate-${currency.code}`}
                                                >
                                                    Your rate, in {base}
                                                </Label>
                                                <Input
                                                    id={`rate-${currency.code}`}
                                                    name="manual_rate"
                                                    inputMode="decimal"
                                                    defaultValue={
                                                        currency.manualRate ??
                                                        ''
                                                    }
                                                    placeholder="Leave empty to use Frankfurter"
                                                />
                                                <InputError
                                                    message={errors.manual_rate}
                                                />
                                            </div>
                                            <Button
                                                type="submit"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                Save rate
                                            </Button>
                                        </>
                                    )}
                                </Form>
                            )}
                        </div>
                    ))}
                </div>

                <Form
                    {...CurrencyController.store.form()}
                    resetOnSuccess
                    onSuccess={() => setAddFormKey((key) => key + 1)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <Heading
                                variant="small"
                                title="Add a currency"
                                description="Frankfurter supplies the daily rate."
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="currency-code">Currency</Label>
                                <FormSelect
                                    key={addFormKey}
                                    id="currency-code"
                                    name="code"
                                    required
                                    defaultValue=""
                                    emptyLabel="Choose a currency"
                                    invalid={Boolean(errors.code)}
                                    options={currencyOptions}
                                />
                                <InputError message={errors.code} />
                                <InputError message={errors.manual_rate} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Add currency
                            </Button>
                        </>
                    )}
                </Form>

                <Form {...ExchangeRateController.form()}>
                    {({ processing, errors }) => (
                        <div className="space-y-2">
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={processing}
                            >
                                Update rates from Frankfurter
                            </Button>
                            <p className="text-sm text-muted-foreground">
                                Rates are also fetched every day at 16:30
                                Europe/Berlin. Figures already saved keep the
                                rate from the day they were entered.
                            </p>
                            <InputError message={errors.rate} />
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

Currencies.layout = {
    breadcrumbs: [
        {
            title: 'Currencies',
            href: edit(),
        },
    ],
};
