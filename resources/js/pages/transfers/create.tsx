import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { TransferForm } from '@/components/transfer-form';
import { Button } from '@/components/ui/button';
import { create as createAccount } from '@/routes/accounts';
import { index as transactions } from '@/routes/transactions';
import { create } from '@/routes/transfers';
import type { TransferFormPage } from '@/types/transfer';

export default function CreateTransfer({
    transfer,
    accounts,
    people,
    defaults,
    needs_second_account,
}: TransferFormPage) {
    return (
        <>
            <Head title="Transfer" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Transfer
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Move money from one account to another. A transfer
                            does not count as income or spending.
                        </p>
                    </div>
                </section>

                {needs_second_account ? (
                    <section
                        className="flex flex-col gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        data-test="needs-second-account"
                    >
                        <h2 className="font-medium">Add another account</h2>
                        <p className="text-sm text-muted-foreground">
                            A transfer needs two accounts, such as a bank
                            account and cash.
                        </p>
                        <Button
                            asChild
                            className="h-11 w-full sm:w-auto md:h-9"
                        >
                            <Link href={createAccount()}>
                                <Plus data-icon="inline-start" />
                                Create an account
                            </Link>
                        </Button>
                    </section>
                ) : (
                    <TransferForm
                        transfer={transfer}
                        accounts={accounts}
                        people={people}
                        defaults={defaults}
                    />
                )}
            </div>
        </>
    );
}

CreateTransfer.layout = {
    breadcrumbs: [
        { title: 'Transactions', href: transactions() },
        { title: 'Transfer', href: create() },
    ],
};
