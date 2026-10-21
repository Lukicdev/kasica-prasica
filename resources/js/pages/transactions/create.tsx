import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { TransactionForm } from '@/components/transaction-form';
import { Button } from '@/components/ui/button';
import { create as createAccount } from '@/routes/accounts';
import { create, index } from '@/routes/transactions';
import type { TransactionFormPage } from '@/types/transaction';

export default function CreateTransaction({
    transaction,
    accounts,
    categories,
    people,
    defaults,
    needs_account,
    confirmation,
}: TransactionFormPage) {
    return (
        <>
            <Head title="Add a transaction" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Add a transaction
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            The date starts as today, and the account is the
                            last one you used.
                        </p>
                    </div>
                </section>

                {confirmation ? (
                    <p
                        role="status"
                        className="rounded-xl border border-sidebar-border/70 p-4 text-sm dark:border-sidebar-border"
                        data-test="saved-balance"
                    >
                        Saved. {confirmation.account} is now{' '}
                        {confirmation.formatted}.
                    </p>
                ) : null}

                {needs_account ? (
                    <section
                        className="flex flex-col gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border"
                        data-test="needs-account"
                    >
                        <h2 className="font-medium">Add an account first</h2>
                        <p className="text-sm text-muted-foreground">
                            Every transaction is booked to an account, so the
                            balance can update on its own.
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
                    <TransactionForm
                        key={confirmation?.id ?? 'new'}
                        transaction={transaction}
                        accounts={accounts}
                        categories={categories}
                        people={people}
                        defaults={defaults}
                    />
                )}
            </div>
        </>
    );
}

CreateTransaction.layout = {
    breadcrumbs: [
        {
            title: 'Transactions',
            href: index(),
        },
        {
            title: 'Add',
            href: create(),
        },
    ],
};
