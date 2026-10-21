import { Form } from '@inertiajs/react';
import { useState } from 'react';
import ReconciliationController from '@/actions/App/Http/Controllers/ReconciliationController';
import { DatePicker } from '@/components/date-picker';
import { FormSelect } from '@/components/form-select';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    addAmounts,
    formatMoney,
    normalizeAmount,
    subtractAmounts,
} from '@/lib/money';
import type { CurrencyCode } from '@/types/currency';
import type {
    AdjustmentAccountOption,
    AdjustmentValues,
    ReconciliationPerson,
} from '@/types/reconciliation';

function isCurrency(value: string): value is CurrencyCode {
    return value === 'RSD' || value === 'EUR';
}

export function AdjustmentForm({
    adjustment,
    accounts,
    people,
}: {
    adjustment: AdjustmentValues;
    accounts: AdjustmentAccountOption[];
    people: ReconciliationPerson[];
}) {
    const [amount, setAmount] = useState(adjustment.amount);
    const [accountId, setAccountId] = useState(String(adjustment.account_id));
    const [personId, setPersonId] = useState(
        String(adjustment.person_id ?? ''),
    );
    const [date, setDate] = useState(adjustment.booked_on);
    const [note, setNote] = useState(adjustment.note ?? '');
    const account = accounts.find((item) => String(item.id) === accountId);
    const currency =
        account && isCurrency(account.currency) ? account.currency : null;
    const signed = normalizeAmount(amount);
    const withoutEntry =
        account === undefined
            ? null
            : account.id === adjustment.account_id
              ? subtractAmounts(account.balance, adjustment.amount)
              : account.balance;
    const nextBalance =
        withoutEntry && signed ? addAmounts(withoutEntry, signed) : null;

    return (
        <Form
            {...ReconciliationController.update.form(adjustment.id)}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="amount">
                            Amount
                            {currency ? ` (${currency})` : ''}
                        </Label>
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
                            data-test="adjustment-amount"
                        />
                        <p className="text-sm text-muted-foreground">
                            A positive amount increases the balance. A negative
                            amount decreases it. This entry is left out of
                            income, spending, and the budget.
                        </p>
                        <InputError message={errors.amount} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="account_id">Account</Label>
                        <FormSelect
                            id="account_id"
                            name="account_id"
                            required
                            value={accountId}
                            onValueChange={setAccountId}
                            invalid={Boolean(errors.account_id)}
                            options={accounts.map((item) => ({
                                value: String(item.id),
                                label: item.name,
                            }))}
                        />
                        <InputError message={errors.account_id} />
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
                            aria-invalid={errors.note ? true : undefined}
                        />
                        <InputError message={errors.note} />
                    </div>

                    <div className="sticky bottom-0 -mx-4 flex flex-col gap-4 border-t bg-background px-4 py-4">
                        <p className="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                            <span className="text-muted-foreground">
                                New balance
                                {account ? ` of ${account.name}` : ''}
                            </span>
                            <span
                                className="font-medium"
                                data-test="adjustment-balance"
                            >
                                {nextBalance && currency
                                    ? formatMoney(nextBalance, currency)
                                    : '—'}
                            </span>
                        </p>
                        <Button
                            type="submit"
                            disabled={processing}
                            className="h-11"
                            data-test="save-adjustment"
                        >
                            Save changes
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
