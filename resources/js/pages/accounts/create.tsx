import { Head } from '@inertiajs/react';
import { AccountForm } from '@/components/account-form';
import { create, index } from '@/routes/accounts';
import type { AccountTypeOption } from '@/types/account';

export default function CreateAccount({
    types,
    today,
}: {
    types: AccountTypeOption[];
    today: string;
}) {
    return (
        <>
            <Head title="New account" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold tracking-tight">
                        New account
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Name it, choose RSD or EUR, and enter the balance you
                        are starting from.
                    </p>
                </div>
                <AccountForm types={types} today={today} />
            </div>
        </>
    );
}

CreateAccount.layout = {
    breadcrumbs: [
        {
            title: 'Accounts',
            href: index(),
        },
        {
            title: 'New account',
            href: create(),
        },
    ],
};
