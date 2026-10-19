import { Form, usePage } from '@inertiajs/react';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

                    <div className="grid gap-2">
                        <Label htmlFor="type">Type</Label>
                        <select
                            id="type"
                            name="type"
                            required
                            defaultValue={account?.type ?? 'cash'}
                            className={fieldClassName}
                        >
                            {types.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>
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
                        <select
                            id="currency"
                            name={
                                account?.currency_locked
                                    ? undefined
                                    : 'currency'
                            }
                            required
                            defaultValue={account?.currency ?? 'RSD'}
                            disabled={account?.currency_locked ?? false}
                            className={cn(
                                fieldClassName,
                                'disabled:cursor-not-allowed disabled:opacity-50',
                            )}
                        >
                            {currencies.supported.map((currency) => (
                                <option
                                    key={currency.code}
                                    value={currency.code}
                                >
                                    {currency.code} · {currency.name}
                                </option>
                            ))}
                        </select>
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
                        <Input
                            id="opening_balance_on"
                            name="opening_balance_on"
                            type="date"
                            required
                            defaultValue={
                                account?.opening_balance_on ?? today ?? ''
                            }
                            className="h-11 md:h-9"
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
