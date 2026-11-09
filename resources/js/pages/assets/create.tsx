import { Head } from '@inertiajs/react';
import { AssetForm } from '@/components/asset-form';
import { create, index } from '@/routes/assets';
import type { AssetTypeOption } from '@/types/asset';

export default function CreateAsset({
    types,
    today,
}: {
    types: AssetTypeOption[];
    today: string;
}) {
    return (
        <>
            <Head title="New asset" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            New asset
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Name it, choose a type, and enter what it is worth.
                            The value is entered by hand.
                        </p>
                    </div>
                </section>
                <AssetForm types={types} today={today} />
            </div>
        </>
    );
}

CreateAsset.layout = {
    breadcrumbs: [
        {
            title: 'Assets',
            href: index(),
        },
        {
            title: 'New asset',
            href: create(),
        },
    ],
};
