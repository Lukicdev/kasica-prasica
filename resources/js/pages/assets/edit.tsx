import { Form, Head, router } from '@inertiajs/react';
import AssetController from '@/actions/App/Http/Controllers/AssetController';
import { AssetForm } from '@/components/asset-form';
import { Button } from '@/components/ui/button';
import { edit, index, show } from '@/routes/assets';
import type { AssetFormValues, AssetTypeOption } from '@/types/asset';

export default function EditAsset({
    asset,
    types,
}: {
    asset: AssetFormValues;
    types: AssetTypeOption[];
}) {
    const remove = (): void => {
        if (!confirm('Delete this asset? This cannot be undone.')) {
            return;
        }

        router.delete(AssetController.destroy.url(asset.id));
    };

    return (
        <>
            <Head title={`Edit ${asset.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Edit {asset.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Change the details, or enter a new value. Values are
                            entered by hand.
                        </p>
                    </div>
                </section>

                <AssetForm asset={asset} types={types} />

                <section className="flex flex-col gap-3 border-t pt-6">
                    <h2 className="font-medium">
                        {asset.archived ? 'Archived' : 'Archive'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Archived assets stay on the list and in the total.
                    </p>
                    {asset.archived ? (
                        <Form {...AssetController.restore.form(asset.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 w-full sm:w-auto md:h-9"
                                    data-test="restore-asset"
                                >
                                    Restore asset
                                </Button>
                            )}
                        </Form>
                    ) : (
                        <Form {...AssetController.archive.form(asset.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 w-full sm:w-auto md:h-9"
                                    data-test="archive-asset"
                                >
                                    Archive asset
                                </Button>
                            )}
                        </Form>
                    )}
                    <Button
                        type="button"
                        variant="destructive"
                        className="h-11 w-full sm:w-auto md:h-9"
                        onClick={remove}
                        data-test="delete-asset"
                    >
                        Delete asset
                    </Button>
                </section>
            </div>
        </>
    );
}

EditAsset.layout = (props: {
    asset: AssetFormValues;
}): { breadcrumbs: { title: string; href: ReturnType<typeof index> }[] } => ({
    breadcrumbs: [
        { title: 'Assets', href: index() },
        { title: props.asset.name, href: show(props.asset.id) },
        { title: 'Edit', href: edit(props.asset.id) },
    ],
});
