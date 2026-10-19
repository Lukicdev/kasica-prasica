import { Form, Head, router } from '@inertiajs/react';
import AccountController from '@/actions/App/Http/Controllers/AccountController';
import { AccountForm } from '@/components/account-form';
import { Button } from '@/components/ui/button';
import { edit, index, show } from '@/routes/accounts';
import type { AccountFormValues, AccountTypeOption } from '@/types/account';

export default function EditAccount({
    account,
    types,
}: {
    account: AccountFormValues;
    types: AccountTypeOption[];
}) {
    const remove = (): void => {
        if (!confirm('Delete this account? This cannot be undone.')) {
            return;
        }

        router.delete(AccountController.destroy.url(account.id));
    };

    return (
        <>
            <Head title={`Edit ${account.name}`} />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-8 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold tracking-tight">
                        Edit {account.name}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        The balance updates from the opening balance and the
                        transactions on this account.
                    </p>
                </div>

                <AccountForm account={account} types={types} />

                <section className="flex flex-col gap-3 border-t pt-6">
                    <h2 className="font-medium">
                        {account.archived ? 'Archived' : 'Archive'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Archived accounts stay in the history and cannot be
                        picked for new transactions.
                    </p>
                    {account.archived ? (
                        <Form {...AccountController.restore.form(account.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 w-full sm:w-auto"
                                    data-test="restore-account"
                                >
                                    Restore account
                                </Button>
                            )}
                        </Form>
                    ) : (
                        <Form {...AccountController.archive.form(account.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 w-full sm:w-auto"
                                    data-test="archive-account"
                                >
                                    Archive account
                                </Button>
                            )}
                        </Form>
                    )}
                    {account.can_delete ? (
                        <Button
                            type="button"
                            variant="destructive"
                            className="h-11 w-full sm:w-auto"
                            onClick={remove}
                            data-test="delete-account"
                        >
                            Delete account
                        </Button>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            This account has transactions, so it can be archived
                            and cannot be deleted.
                        </p>
                    )}
                </section>
            </div>
        </>
    );
}

EditAccount.layout = (props: {
    account: AccountFormValues;
}): { breadcrumbs: { title: string; href: ReturnType<typeof index> }[] } => ({
    breadcrumbs: [
        { title: 'Accounts', href: index() },
        { title: props.account.name, href: show(props.account.id) },
        { title: 'Edit', href: edit(props.account.id) },
    ],
});
