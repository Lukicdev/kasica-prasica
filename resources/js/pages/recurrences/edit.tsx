import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { RecurrenceForm } from '@/components/recurrence-form';
import { Button } from '@/components/ui/button';
import { create as createAccount } from '@/routes/accounts';
import { edit } from '@/routes/recurrences';
import { index } from '@/routes/transactions';
import type { RecurrenceFormPage } from '@/types/recurrence';

export default function EditRecurrence({
    recurrence,
    accounts,
    needs_account,
}: RecurrenceFormPage) {
    return (
        <>
            <Head title={`Confirm ${recurrence.type_label}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Confirm {recurrence.type_label.toLowerCase()}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {recurrence.month_label}. Change the amount, account
                            or currency if this month is different. Confirming
                            records it and updates the account balance.
                        </p>
                    </div>
                </section>

                {needs_account ? (
                    <section className="flex flex-col gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <h2 className="font-medium">Add an account first</h2>
                        <p className="text-sm text-muted-foreground">
                            The account on this repeat can no longer be used.
                            Add another, then confirm.
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
                    <RecurrenceForm
                        recurrence={recurrence}
                        accounts={accounts}
                    />
                )}
            </div>
        </>
    );
}

EditRecurrence.layout = (
    props: RecurrenceFormPage,
): {
    breadcrumbs: { title: string; href: ReturnType<typeof index> }[];
} => ({
    breadcrumbs: [
        { title: 'Transactions', href: index() },
        {
            title: 'Confirm',
            href: edit(props.recurrence.id, {
                query: { month: props.recurrence.month },
            }),
        },
    ],
});
