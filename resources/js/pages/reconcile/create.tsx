import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import ReconciliationController from '@/actions/App/Http/Controllers/ReconciliationController';
import { AccountBalance } from '@/components/account-balance';
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
    isNegativeAmount,
    negateAmount,
    subtractAmounts,
} from '@/lib/money';
import { index, reconcile, show } from '@/routes/accounts';
import type { ReconciliationCreatePage } from '@/types/reconciliation';

export default function ReconcileAccount({
    account,
    people,
    defaults,
}: ReconciliationCreatePage) {
    const [realBalance, setRealBalance] = useState('');
    const [personId, setPersonId] = useState(String(defaults.person_id));
    const [date, setDate] = useState(defaults.date);
    const [note, setNote] = useState(defaults.note);
    const real =
        realBalance.trim() === '' ? null : addAmounts(realBalance, '0');
    const difference = real
        ? subtractAmounts(real, account.balance.amount)
        : null;
    const gapAmount =
        difference && isNegativeAmount(difference)
            ? negateAmount(difference)
            : difference;
    const gap =
        gapAmount === null ? null : formatMoney(gapAmount, account.currency);
    const matches = difference === '0.00';

    return (
        <>
            <Head title={`Reconcile ${account.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h1 className="text-lg font-semibold tracking-tight">
                        Reconcile {account.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Enter the balance you counted. If it differs from the
                        app, you can add a balancing entry that is left out of
                        income, spending, and the budget.
                    </p>
                </section>

                <section
                    aria-label="Balance in the app"
                    className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p className="text-sm text-muted-foreground">
                        Balance in the app
                    </p>
                    <p className="text-2xl font-semibold tracking-tight">
                        <AccountBalance balance={account.balance} />
                    </p>
                </section>

                <Form
                    {...ReconciliationController.store.form(account.id)}
                    className="flex flex-col gap-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="real_balance">
                                    Real balance ({account.currency})
                                </Label>
                                <Input
                                    id="real_balance"
                                    name="real_balance"
                                    inputMode="decimal"
                                    autoComplete="off"
                                    autoFocus
                                    required
                                    value={realBalance}
                                    onChange={(event) =>
                                        setRealBalance(event.target.value)
                                    }
                                    placeholder="0.00"
                                    className="h-14 text-2xl md:h-14"
                                    data-test="real-balance"
                                />
                                <InputError message={errors.real_balance} />
                            </div>

                            <p
                                role="status"
                                className="text-sm"
                                data-test="reconciliation-difference"
                            >
                                {difference === null
                                    ? 'Enter the balance you counted.'
                                    : matches
                                      ? 'This matches the balance in the app. No balancing entry is needed.'
                                      : isNegativeAmount(difference)
                                        ? `The real balance is ${gap} lower. A balancing entry will remove ${gap} from ${account.name}. It is left out of income, spending, and the budget.`
                                        : `The real balance is ${gap} higher. A balancing entry will add ${gap} to ${account.name}. It is left out of income, spending, and the budget.`}
                            </p>

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
                                    onChange={(event) =>
                                        setNote(event.target.value)
                                    }
                                    rows={3}
                                    aria-invalid={
                                        errors.note ? true : undefined
                                    }
                                />
                                <InputError message={errors.note} />
                            </div>

                            <div className="sticky bottom-0 -mx-4 border-t bg-background px-4 py-4">
                                <Button
                                    type="submit"
                                    disabled={
                                        processing ||
                                        difference === null ||
                                        matches
                                    }
                                    className="h-11 w-full"
                                    data-test="add-balancing-entry"
                                >
                                    Add balancing entry
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ReconcileAccount.layout = (
    props: ReconciliationCreatePage,
): { breadcrumbs: { title: string; href: ReturnType<typeof show> }[] } => ({
    breadcrumbs: [
        { title: 'Accounts', href: index() },
        { title: props.account.name, href: show(props.account.id) },
        { title: 'Reconcile', href: reconcile(props.account.id) },
    ],
});
