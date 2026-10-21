import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import RecurrenceController from '@/actions/App/Http/Controllers/RecurrenceController';
import { DatePicker } from '@/components/date-picker';
import { FormSelect } from '@/components/form-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    addAmounts,
    divideAmount,
    formatMoney,
    multiplyAmount,
    negateAmount,
    normalizeAmount,
    subtractAmounts,
} from '@/lib/money';
import type { CurrencyCode } from '@/types/currency';
import type { RecurrenceFormPage } from '@/types/recurrence';

function isCurrency(value: string): value is CurrencyCode {
    return value === 'RSD' || value === 'EUR';
}

function convertAmount(
    amount: string,
    from: CurrencyCode,
    to: CurrencyCode,
    rate: string,
): string | null {
    if (from === to) {
        return normalizeAmount(amount);
    }

    if (from === 'EUR' && to === 'RSD') {
        return multiplyAmount(amount, rate);
    }

    if (from === 'RSD' && to === 'EUR') {
        return divideAmount(amount, rate);
    }

    return null;
}

function signedAmount(amount: string, refund: boolean): string | null {
    const normalized = normalizeAmount(amount);

    if (!normalized) {
        return null;
    }

    if (refund && !normalized.startsWith('-')) {
        return negateAmount(normalized);
    }

    return normalized;
}

export function RecurrenceForm({
    recurrence,
    accounts,
}: Pick<RecurrenceFormPage, 'recurrence' | 'accounts'>) {
    const { currencies } = usePage().props;
    const rate = currencies.eurToRsd;
    const [amount, setAmount] = useState(recurrence.amount);
    const [refund, setRefund] = useState(recurrence.refund);
    const [accountId, setAccountId] = useState(String(recurrence.account_id));
    const [currency, setCurrency] = useState<CurrencyCode>(recurrence.currency);
    const [date, setDate] = useState(recurrence.booked_on);

    const account = accounts.find((item) => String(item.id) === accountId);
    const accountCurrency =
        account && isCurrency(account.currency) ? account.currency : null;
    const signed = signedAmount(
        amount,
        recurrence.type === 'expense' && refund,
    );
    const settled =
        signed && accountCurrency
            ? convertAmount(signed, currency, accountCurrency, rate)
            : null;
    const nextBalance =
        account && settled
            ? recurrence.type === 'income'
                ? addAmounts(account.balance, settled)
                : subtractAmounts(account.balance, settled)
            : null;
    const baseAmount = signed
        ? convertAmount(signed, currency, 'RSD', rate)
        : null;

    const changeAccount = (value: string): void => {
        setAccountId(value);
        const next = accounts.find((item) => String(item.id) === value);

        if (next && isCurrency(next.currency)) {
            setCurrency(next.currency);
        }
    };

    return (
        <Form
            {...RecurrenceController.store.form(recurrence.id)}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <input
                        type="hidden"
                        name="month"
                        value={recurrence.month}
                    />

                    <dl className="flex flex-col gap-3 text-sm">
                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                            <dt className="text-muted-foreground">Category</dt>
                            <dd>{recurrence.category ?? '—'}</dd>
                        </div>
                        {recurrence.person ? (
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <dt className="text-muted-foreground">
                                    Person
                                </dt>
                                <dd>{recurrence.person}</dd>
                            </div>
                        ) : null}
                        {recurrence.note ? (
                            <div className="flex flex-wrap items-baseline justify-between gap-2">
                                <dt className="text-muted-foreground">Note</dt>
                                <dd>{recurrence.note}</dd>
                            </div>
                        ) : null}
                    </dl>

                    <div className="grid gap-2">
                        <Label htmlFor="amount">Amount</Label>
                        <Input
                            id="amount"
                            name="amount"
                            inputMode="decimal"
                            autoComplete="off"
                            required
                            value={amount}
                            onChange={(event) => setAmount(event.target.value)}
                            placeholder="0.00"
                            className="h-14 text-2xl md:h-14"
                            data-test="recurrence-amount"
                        />
                        <InputError message={errors.amount} />
                    </div>

                    {recurrence.type === 'expense' ? (
                        <div className="flex min-h-11 items-center gap-3">
                            {refund ? (
                                <input type="hidden" name="refund" value="1" />
                            ) : null}
                            <Checkbox
                                id="refund"
                                checked={refund}
                                onCheckedChange={(checked) =>
                                    setRefund(checked === true)
                                }
                            />
                            <Label htmlFor="refund">This is a refund</Label>
                        </div>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="account_id">Account</Label>
                        <FormSelect
                            id="account_id"
                            name="account_id"
                            required
                            value={accountId}
                            onValueChange={changeAccount}
                            invalid={Boolean(errors.account_id)}
                            data-test="recurrence-account"
                            options={accounts.map((item) => ({
                                value: String(item.id),
                                label: item.name,
                            }))}
                        />
                        <InputError message={errors.account_id} />
                        {account?.archived ? (
                            <p className="text-sm text-muted-foreground">
                                This account is archived. Choose another to
                                confirm.
                            </p>
                        ) : null}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        <FormSelect
                            id="currency"
                            name="currency"
                            required
                            value={currency}
                            onValueChange={(next) => {
                                if (isCurrency(next)) {
                                    setCurrency(next);
                                }
                            }}
                            invalid={Boolean(errors.currency)}
                            data-test="recurrence-currency"
                            options={currencies.supported.map((item) => ({
                                value: item.code,
                                label: `${item.code} · ${item.name}`,
                            }))}
                        />
                        <InputError message={errors.currency} />
                    </div>

                    {currency === 'EUR' && baseAmount ? (
                        <p className="text-sm">
                            <span className="text-muted-foreground">
                                In dinars
                            </span>{' '}
                            <span className="font-medium">
                                {formatMoney(baseAmount, 'RSD')}
                            </span>
                        </p>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="booked_on">Date</Label>
                        <DatePicker
                            id="booked_on"
                            name="booked_on"
                            required
                            min={recurrence.min_date}
                            max={recurrence.max_date}
                            value={date}
                            onChange={setDate}
                        />
                        <InputError message={errors.booked_on} />
                        <InputError message={errors.month} />
                    </div>

                    <div className="sticky bottom-0 -mx-4 flex flex-col gap-4 border-t bg-background px-4 py-4">
                        <div
                            aria-live="polite"
                            className="flex flex-col gap-1 text-sm"
                        >
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-muted-foreground">
                                    Value in RSD
                                </span>
                                <span>
                                    {baseAmount
                                        ? formatMoney(baseAmount, 'RSD')
                                        : '—'}
                                </span>
                            </p>
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-muted-foreground">
                                    New balance
                                    {account ? ` of ${account.name}` : ''}
                                </span>
                                <span className="font-medium">
                                    {nextBalance && accountCurrency
                                        ? formatMoney(
                                              nextBalance,
                                              accountCurrency,
                                          )
                                        : '—'}
                                </span>
                            </p>
                        </div>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-11 md:h-9"
                            data-test="save-recurrence"
                        >
                            Confirm
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
