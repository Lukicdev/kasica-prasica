import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TransferController from '@/actions/App/Http/Controllers/TransferController';
import { DatePicker } from '@/components/date-picker';
import { FormSelect } from '@/components/form-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { isEnabledCurrency, rateMap } from '@/lib/currency';
import {
    addAmounts,
    convertAmount,
    formatMoney,
    normalizeAmount,
    subtractAmounts,
} from '@/lib/money';
import type {
    TransferAccountOption,
    TransferDefaults,
    TransferPersonOption,
    TransferValues,
} from '@/types/transfer';

function withoutCurrentTransfer(
    account: TransferAccountOption,
    transfer: TransferValues | null,
): string {
    if (!transfer) {
        return account.balance;
    }

    if (account.id === transfer.from_account_id) {
        return (
            addAmounts(account.balance, transfer.account_amount) ??
            account.balance
        );
    }

    if (account.id === transfer.to_account_id && transfer.destination_amount) {
        return (
            subtractAmounts(account.balance, transfer.destination_amount) ??
            account.balance
        );
    }

    return account.balance;
}

export function TransferForm({
    transfer,
    accounts,
    people,
    defaults,
}: {
    transfer: TransferValues | null;
    accounts: TransferAccountOption[];
    people: TransferPersonOption[];
    defaults: TransferDefaults;
}) {
    const { currencies } = usePage().props;
    const enabled = (value: string): boolean =>
        isEnabledCurrency(value, currencies.supported);
    const editing = transfer !== null;
    const initialFrom =
        accounts.find(
            (account) =>
                account.id ===
                (transfer?.from_account_id ?? defaults.from_account_id),
        ) ?? accounts[0];
    const [amount, setAmount] = useState(transfer?.amount ?? '');
    const [fromId, setFromId] = useState(
        String(transfer?.from_account_id ?? defaults.from_account_id ?? ''),
    );
    const [toId, setToId] = useState(
        String(transfer?.to_account_id ?? defaults.to_account_id ?? ''),
    );
    const [currency, setCurrency] = useState(
        transfer?.currency ??
            (initialFrom && enabled(initialFrom.currency)
                ? initialFrom.currency
                : defaults.currency),
    );
    const [personId, setPersonId] = useState(
        String(transfer?.person_id ?? defaults.person_id),
    );
    const [date, setDate] = useState(transfer?.booked_on ?? defaults.date);
    const [note, setNote] = useState(transfer?.note ?? '');

    const from = accounts.find((account) => String(account.id) === fromId);
    const to = accounts.find((account) => String(account.id) === toId);
    const rates = rateMap(currencies.supported, transfer, currency);
    const fromCurrency = from && enabled(from.currency) ? from.currency : null;
    const toCurrency = to && enabled(to.currency) ? to.currency : null;
    const sameAccount = from !== undefined && from.id === to?.id;
    const leaving =
        fromCurrency && normalizeAmount(amount)
            ? convertAmount(amount, currency, fromCurrency, rates)
            : null;
    const arriving =
        toCurrency && !sameAccount
            ? convertAmount(amount, currency, toCurrency, rates)
            : null;
    const fromBalance =
        from && leaving && !sameAccount
            ? subtractAmounts(withoutCurrentTransfer(from, transfer), leaving)
            : null;
    const toBalance =
        to && arriving && !sameAccount
            ? addAmounts(withoutCurrentTransfer(to, transfer), arriving)
            : null;
    const currenciesDiffer =
        fromCurrency !== null &&
        toCurrency !== null &&
        (fromCurrency !== toCurrency || currency !== fromCurrency);

    const changeFrom = (value: string): void => {
        setFromId(value);
        const next = accounts.find((account) => String(account.id) === value);

        if (next && enabled(next.currency)) {
            setCurrency(next.currency);
        }
    };

    return (
        <Form
            {...(editing
                ? TransferController.update.form(transfer.id)
                : TransferController.store.form())}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="amount">Amount</Label>
                        <Input
                            id="amount"
                            name="amount"
                            inputMode="decimal"
                            autoComplete="off"
                            autoFocus
                            required
                            value={amount}
                            onChange={(event) => setAmount(event.target.value)}
                            placeholder="0.00"
                            className="h-14 text-2xl md:h-14"
                            data-test="transfer-amount"
                        />
                        <InputError message={errors.amount} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="from_account_id">From</Label>
                        <FormSelect
                            id="from_account_id"
                            name="from_account_id"
                            required
                            value={fromId}
                            onValueChange={changeFrom}
                            invalid={Boolean(errors.from_account_id)}
                            data-test="transfer-from"
                            options={accounts.map((account) => ({
                                value: String(account.id),
                                label: account.name,
                                icon: account.icon,
                            }))}
                        />
                        <InputError message={errors.from_account_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="to_account_id">To</Label>
                        <FormSelect
                            id="to_account_id"
                            name="to_account_id"
                            required
                            value={toId}
                            onValueChange={setToId}
                            invalid={Boolean(errors.to_account_id)}
                            data-test="transfer-to"
                            options={accounts.map((account) => ({
                                value: String(account.id),
                                label: account.name,
                                icon: account.icon,
                            }))}
                        />
                        <InputError message={errors.to_account_id} />
                        {sameAccount ? (
                            <p className="text-sm text-muted-foreground">
                                Choose a different account.
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
                                if (enabled(next)) {
                                    setCurrency(next);
                                }
                            }}
                            invalid={Boolean(errors.currency)}
                            options={currencies.supported.map((item) => ({
                                value: item.code,
                                label: `${item.code} · ${item.name}`,
                            }))}
                        />
                        <InputError message={errors.currency} />
                    </div>

                    {currenciesDiffer ? (
                        <p className="text-sm" data-test="transfer-rate">
                            <span className="text-muted-foreground">
                                Exchange rate
                            </span>{' '}
                            <span className="font-medium">
                                1 {currency} = {rates[currency]}{' '}
                                {currencies.base}
                            </span>
                            . Both accounts move by this rate.
                        </p>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="booked_on">Date</Label>
                        <DatePicker
                            id="booked_on"
                            name="booked_on"
                            required
                            value={date}
                            onChange={setDate}
                        />
                        <InputError message={errors.booked_on} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="person_id">Person</Label>
                        <FormSelect
                            id="person_id"
                            name="person_id"
                            required
                            value={personId}
                            onValueChange={setPersonId}
                            invalid={Boolean(errors.person_id)}
                            options={people.map((person) => ({
                                value: String(person.id),
                                label: person.name,
                            }))}
                        />
                        <InputError message={errors.person_id} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="note">Note</Label>
                        <Textarea
                            id="note"
                            name="note"
                            value={note}
                            onChange={(event) => setNote(event.target.value)}
                            rows={3}
                            placeholder="Optional"
                            aria-invalid={errors.note ? true : undefined}
                            data-test="transfer-note"
                        />
                        <InputError message={errors.note} />
                    </div>

                    <div className="sticky bottom-0 -mx-4 flex flex-col gap-4 border-t bg-background px-4 py-4">
                        <div
                            aria-live="polite"
                            className="flex flex-col gap-1 text-sm"
                        >
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-muted-foreground">
                                    Leaves {from ? from.name : 'the account'}
                                </span>
                                <span data-test="transfer-leaves">
                                    {leaving && fromCurrency
                                        ? formatMoney(leaving, fromCurrency)
                                        : '—'}
                                </span>
                            </p>
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-muted-foreground">
                                    Arrives in {to ? to.name : 'the account'}
                                </span>
                                <span data-test="transfer-arrives">
                                    {arriving && toCurrency
                                        ? formatMoney(arriving, toCurrency)
                                        : '—'}
                                </span>
                            </p>
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-muted-foreground">
                                    New balance
                                    {from ? ` of ${from.name}` : ''}
                                </span>
                                <span
                                    className="font-medium"
                                    data-test="transfer-from-balance"
                                >
                                    {fromBalance && fromCurrency
                                        ? formatMoney(fromBalance, fromCurrency)
                                        : '—'}
                                </span>
                            </p>
                            <p className="flex flex-wrap items-baseline justify-between gap-2">
                                <span className="text-muted-foreground">
                                    New balance
                                    {to ? ` of ${to.name}` : ''}
                                </span>
                                <span
                                    className="font-medium"
                                    data-test="transfer-to-balance"
                                >
                                    {toBalance && toCurrency
                                        ? formatMoney(toBalance, toCurrency)
                                        : '—'}
                                </span>
                            </p>
                        </div>
                        <Button
                            type="submit"
                            disabled={processing || sameAccount}
                            className="h-11 md:h-9"
                            data-test="save-transfer"
                        >
                            {editing ? 'Save changes' : 'Save transfer'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
