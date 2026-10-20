import { Head } from '@inertiajs/react';
import { AdjustmentForm } from '@/components/adjustment-form';
import { show } from '@/routes/transactions';
import { index as transactions } from '@/routes/transactions';
import { edit } from '@/routes/adjustments';
import type { ReconciliationEditPage } from '@/types/reconciliation';

export default function EditAdjustment({
    adjustment,
    accounts,
    people,
}: ReconciliationEditPage) {
    return (
        <>
            <Head title="Edit balance adjustment" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <h1 className="text-lg font-semibold tracking-tight">
                        Edit balance adjustment
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Saving updates the account balance. The entry stays out
                        of income, spending, and the budget.
                    </p>
                </section>
                <AdjustmentForm
                    adjustment={adjustment}
                    accounts={accounts}
                    people={people}
                />
            </div>
        </>
    );
}

EditAdjustment.layout = (
    props: ReconciliationEditPage,
): {
    breadcrumbs: { title: string; href: ReturnType<typeof transactions> }[];
} => ({
    breadcrumbs: [
        { title: 'Transactions', href: transactions() },
        { title: 'Balance adjustment', href: show(props.adjustment.id) },
        { title: 'Edit', href: edit(props.adjustment.id) },
    ],
});
