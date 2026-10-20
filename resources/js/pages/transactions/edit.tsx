import { Head } from '@inertiajs/react';
import { TransactionForm } from '@/components/transaction-form';
import { edit, index, show } from '@/routes/transactions';
import type { TransactionFormPage } from '@/types/transaction';

export default function EditTransaction({
    transaction,
    accounts,
    categories,
    people,
    defaults,
}: TransactionFormPage) {
    return (
        <>
            <Head title="Edit transaction" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Edit transaction
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Saving updates the totals and every account this
                            transaction touches.
                        </p>
                    </div>
                </section>
                {transaction ? (
                    <TransactionForm
                        transaction={transaction}
                        accounts={accounts}
                        categories={categories}
                        people={people}
                        defaults={defaults}
                    />
                ) : null}
            </div>
        </>
    );
}

EditTransaction.layout = (
    props: TransactionFormPage,
): {
    breadcrumbs: { title: string; href: ReturnType<typeof index> }[];
} => ({
    breadcrumbs: [
        { title: 'Transactions', href: index() },
        {
            title: 'Transaction',
            href: props.transaction ? show(props.transaction.id) : index(),
        },
        {
            title: 'Edit',
            href: props.transaction ? edit(props.transaction.id) : index(),
        },
    ],
});
