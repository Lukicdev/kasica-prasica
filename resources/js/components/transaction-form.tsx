import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import { DatePicker } from '@/components/date-picker';
import { FormSelect } from '@/components/form-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
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
import type {
    TransactionAccountOption,
    TransactionDefaults,
    TransactionFormCategory,
    TransactionFormValues,
    TransactionOption,
} from '@/types/transaction';

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

export function TransactionForm({
    transaction,
    accounts,
    categories,
    people,
    defaults,
}: {
    transaction: TransactionFormValues | null;
    accounts: TransactionAccountOption[];
    categories: TransactionFormCategory[];
    people: TransactionOption[];
    defaults: TransactionDefaults;
}) {
    const { currencies } = usePage().props;
    const rate = currencies.eurToRsd;
    const editing = transaction !== null;
    const initialAccount =
        accounts.find(
            (account) =>
                account.id === (transaction?.account_id ?? defaults.account_id),
        ) ?? accounts[0];
    const [type, setType] = useState<'income' | 'expense'>(
        transaction?.type ?? defaults.type,
    );
    const [amount, setAmount] = useState(transaction?.amount ?? '');
    const [refund, setRefund] = useState(transaction?.refund ?? false);
    const [accountId, setAccountId] = useState(
        String(transaction?.account_id ?? defaults.account_id ?? ''),
    );
    const [currency, setCurrency] = useState<CurrencyCode>(
        transaction?.currency ??
            (initialAccount && isCurrency(initialAccount.currency)
                ? initialAccount.currency
                : defaults.currency),
    );
    const [categoryId, setCategoryId] = useState(
        String(transaction?.category_id ?? defaults.category_id ?? ''),
    );
    const [personId, setPersonId] = useState(
        String(transaction?.person_id ?? defaults.person_id),
    );
    const [date, setDate] = useState(transaction?.booked_on ?? defaults.date);
    const [note, setNote] = useState(transaction?.note ?? '');
    const [recurring, setRecurring] = useState(transaction?.recurring ?? false);

    const account = accounts.find((item) => String(item.id) === accountId);
    const accountCurrency =
        account && isCurrency(account.currency) ? account.currency : null;
    const signed = signedAmount(amount, type === 'expense' && refund);
    const settled =
        signed && accountCurrency
            ? convertAmount(signed, currency, accountCurrency, rate)
            : null;
    const nextBalance =
        account && settled
            ? type === 'income'
                ? addAmounts(account.balance, settled)
                : subtractAmounts(account.balance, settled)
            : null;
    const baseAmount = signed
        ? convertAmount(signed, currency, 'RSD', rate)
        : null;
    const categoriesForType = categories.filter(
        (category) => category.kind === type,
    );

    const changeAccount = (value: string): void => {
        setAccountId(value);
        const next = accounts.find((item) => String(item.id) === value);

        if (next && isCurrency(next.currency)) {
            setCurrency(next.currency);
        }
    };

    const changeType = (next: 'income' | 'expense'): void => {
        setType(next);

        if (next === 'income') {
            setRefund(false);
        }

        const selected = categories.find(
            (category) => String(category.id) === categoryId,
        );

        if (selected && selected.kind !== next) {
            setCategoryId('');
        }
    };

    return (
        <Form
            {...(editing
                ? TransactionController.update.form(transaction.id)
                : TransactionController.store.form())}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <input type="hidden" name="type" value={type} />

                    <div className="grid gap-2">
                        <Label id="type-label">Type</Label>
                        <ToggleGroup
                            type="single"
                            value={type}
                            variant="outline"
                            aria-labelledby="type-label"
                            className="w-full"
                            onValueChange={(next) => {
                                if (next === 'expense' || next === 'income') {
                                    changeType(next);
                                }
                            }}
                        >
                            <ToggleGroupItem
                                value="expense"
                                className="h-11 flex-1"
                                data-test="type-expense"
                            >
                                Expense
                            </ToggleGroupItem>
                            <ToggleGroupItem
                                value="income"
                                className="h-11 flex-1"
                                data-test="type-income"
                            >
                                Income
                            </ToggleGroupItem>
                        </ToggleGroup>
                        <InputError message={errors.type} />
                    </div>

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
                            data-test="transaction-amount"
                        />
                        <InputError message={errors.amount} />
                    </div>

                    {type === 'expense' ? (
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
                                data-test="refund"
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
                            options={accounts.map((item) => ({
                                value: String(item.id),
                                label: item.name,
                            }))}
                        />
                        <InputError message={errors.account_id} />
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
                            options={currencies.supported.map((item) => ({
                                value: item.code,
                                label: `${item.code} · ${item.name}`,
                            }))}
                        />
                        <InputError message={errors.currency} />
                    </div>

                    {currency === 'EUR' && baseAmount ? (
                        <p className="text-sm" data-test="rsd-value">
                            <span className="text-muted-foreground">
                                In dinars
                            </span>{' '}
                            <span className="font-medium">
                                {formatMoney(baseAmount, 'RSD')}
                            </span>
                        </p>
                    ) : null}

                    <div className="grid gap-2">
                        <Label htmlFor="category_id">Category</Label>
                        {categoriesForType.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Add a {type} category before saving this
                                transaction.
                            </p>
                        ) : (
                            <FormSelect
                                id="category_id"
                                name="category_id"
                                required
                                value={categoryId}
                                onValueChange={setCategoryId}
                                emptyLabel="Choose a category"
                                invalid={Boolean(errors.category_id)}
                                options={categoriesForType.map((category) => ({
                                    value: String(category.id),
                                    label: category.label,
                                }))}
                            />
                        )}
                        <InputError message={errors.category_id} />
                    </div>

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
                            data-test="transaction-note"
                        />
                        <InputError message={errors.note} />
                    </div>

                    <div className="flex min-h-11 items-start gap-3">
                        {recurring ? (
                            <input type="hidden" name="recurring" value="1" />
                        ) : null}
                        <Checkbox
                            id="recurring"
                            checked={recurring}
                            onCheckedChange={(checked) =>
                                setRecurring(checked === true)
                            }
                            className="mt-0.5"
                            data-test="recurring"
                        />
                        <Label htmlFor="recurring">
                            Repeat every month
                            <span className="mt-1 block text-muted-foreground">
                                Later months stay pending until you confirm
                                them. Pending items are left out of spending and
                                balances.
                            </span>
                        </Label>
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
                                <span data-test="base-amount">
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
                                <span
                                    className="font-medium"
                                    data-test="projected-balance"
                                >
                                    {nextBalance && accountCurrency
                                        ? formatMoney(
                                              nextBalance,
                                              accountCurrency,
                                          )
                                        : '—'}
                                </span>
                            </p>
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Button
                                type="submit"
                                disabled={processing}
                                className="h-11 sm:flex-1"
                                data-test="save-transaction"
                            >
                                {editing ? 'Save changes' : 'Save'}
                            </Button>
                            {editing ? null : (
                                <Button
                                    type="submit"
                                    name="another"
                                    value="1"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 sm:flex-1"
                                    data-test="save-and-add-another"
                                >
                                    Save and add another
                                </Button>
                            )}
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
