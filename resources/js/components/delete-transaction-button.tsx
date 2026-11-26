import { router } from '@inertiajs/react';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import { Button } from '@/components/ui/button';

export function DeleteTransactionButton({
    transactionId,
}: {
    transactionId: number;
}) {
    const remove = (): void => {
        if (!confirm('Delete this transaction? This cannot be undone.')) {
            return;
        }

        router.delete(TransactionController.destroy.url(transactionId));
    };

    return (
        <Button
            type="button"
            variant="destructive"
            className="h-11 w-full sm:w-auto sm:self-start md:h-9"
            onClick={remove}
            data-test="delete-transaction"
        >
            Delete
        </Button>
    );
}
