import { Head } from '@inertiajs/react';
import { TransferForm } from '@/components/transfer-form';
import { index as transactions, show } from '@/routes/transactions';
import { edit } from '@/routes/transfers';
import type { TransferFormPage } from '@/types/transfer';

export default function EditTransfer({
    transfer,
    accounts,
    people,
    defaults,
}: TransferFormPage) {
    return (
        <>
            <Head title="Edit transfer" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Edit transfer
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Saving updates both account balances. A transfer
                            still does not count as income or spending.
                        </p>
                    </div>
                </section>
                {transfer ? (
                    <TransferForm
                        transfer={transfer}
                        accounts={accounts}
                        people={people}
                        defaults={defaults}
                    />
                ) : null}
            </div>
        </>
    );
}

EditTransfer.layout = (
    props: TransferFormPage,
): {
    breadcrumbs: { title: string; href: ReturnType<typeof transactions> }[];
} => ({
    breadcrumbs: [
        { title: 'Transactions', href: transactions() },
        {
            title: 'Transfer',
            href: props.transfer ? show(props.transfer.id) : transactions(),
        },
        {
            title: 'Edit',
            href: props.transfer ? edit(props.transfer.id) : transactions(),
        },
    ],
});
