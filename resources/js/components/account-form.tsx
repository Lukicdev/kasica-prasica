import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import { DatePicker } from '@/components/date-picker';
import { FormSelect } from '@/components/form-select';
import { IconPicker } from '@/components/icon-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { accountTypeIcons } from '@/lib/icons';
import { cn } from '@/lib/utils';
import type { AccountFormValues, AccountTypeOption } from '@/types/account';

const fieldClassName =
    'border-input h-11 w-full rounded-md border bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:h-9 md:text-sm';

export function AccountForm({
    account,
    types,
    today,
}: {
    account?: AccountFormValues;
    types: AccountTypeOption[];
    today?: string;
}) {
    const { currencies } = usePage().props;
    const editing = account !== undefined;
    const [type, setType] = useState(account?.type ?? 'cash');
    const [currency, setCurrency] = useState(account?.currency ?? 'RSD');
    const [icon, setIcon] = useState<string | null>(
        account?.icon ?? accountTypeIcons[account?.type ?? 'cash'] ?? null,
    );
    const [iconChosen, setIconChosen] = useState(account?.icon != null);
    const [openingBalanceOn, setOpeningBalanceOn] = useState(
        account?.opening_balance_on ?? today ?? '',
    );

    return (
        <Form
            {...(editing
                ? AccountController.update.form(account.id)
                : AccountController.store.form())}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            defaultValue={account?.name ?? ''}
                            placeholder="Household cash"
                            className="h-11 md:h-9"
                            data-test="account-name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <IconPicker
                        id="icon"
                        value={icon}
                        onChange={(next) => {
                            setIconChosen(true);
                            setIcon(next);
                        }}
                    />
                    <InputError message={errors.icon} />

                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <FormSelect
                            id="type"
                            name="type"
                            required
                            value={type}
                            invalid={Boolean(errors.type)}
                            onValueChange={(next) => {
                                setType(next);

                                if (!iconChosen) {
                                    setIcon(accountTypeIcons[next] ?? null);
                                }
                            }}
                            options={types.map((option) => ({
                                value: option.value,
                                label: option.label,
                            }))}
                        />
                        <InputError message={errors.type} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="currency">Currency</Label>
                        {account?.currency_locked ? (
                            <input
                                type="hidden"
                                name="currency"
                                value={account.currency}
                            />
                        ) : null}
                        <FormSelect
                            id="currency"
                            name={
                                account?.currency_locked
                                    ? undefined
                                    : 'currency'
                            }
                            required
                            value={currency}
                            onValueChange={setCurrency}
                            disabled={account?.currency_locked ?? false}
                            invalid={Boolean(errors.currency)}
                            options={currencies.supported.map((option) => ({
                                value: option.code,
                                label: `${option.code} · ${option.name}`,
                            }))}
                        />
                        {account?.currency_locked ? (
                            <p className="text-sm text-muted-foreground">
                                Currency stays the same once the account has
                                transactions.
                            </p>
                        ) : null}
                        <InputError message={errors.currency} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="opening_balance">Opening balance</Label>
                        <Input
                            id="opening_balance"
                            name="opening_balance"
                            inputMode="decimal"
                            required
                            defaultValue={account?.opening_balance ?? ''}
                            placeholder="0.00"
                            className="h-11 md:h-9"
                        />
                        <InputError message={errors.opening_balance} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="opening_balance_on">
                            Opening balance date
                        </Label>
                        <DatePicker
                            id="opening_balance_on"
                            name="opening_balance_on"
                            required
                            value={openingBalanceOn}
                            onChange={setOpeningBalanceOn}
                        />
                        <InputError message={errors.opening_balance_on} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="note">Note</Label>
                        <textarea
                            id="note"
                            name="note"
                            defaultValue={account?.note ?? ''}
                            rows={3}
                            placeholder="Optional"
                            className={cn(fieldClassName, 'h-auto py-2')}
                        />
                        <InputError message={errors.note} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="h-11 w-full sm:w-auto"
                        data-test="save-account"
                    >
                        {editing ? 'Save changes' : 'Create account'}
                    </Button>
                </>
            )}
        </Form>
    );
}
