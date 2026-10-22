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
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            New account
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Name it, choose a currency, and enter the balance
                            you are starting from.
                        </p>
                    </div>
                </section>
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
