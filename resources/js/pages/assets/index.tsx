import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { EntityIcon } from '@/components/entity-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/assets';
import type { AssetGroup, AssetSummary, MoneyFigure } from '@/types/asset';

export default function Assets({
    groups,
    total,
}: {
    groups: AssetGroup[];
    total: MoneyFigure;
}) {
    const assets = groups.flatMap((group) => group.assets);

    return (
        <>
            <Head title="Assets" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Assets
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Each value is entered by hand, in that asset&apos;s
                            currency. The total follows the display currency in
                            the totals section.
                        </p>
                    </div>
                    <Button asChild className="h-11 w-full sm:w-auto md:h-9">
                        <Link href={create()} data-test="new-asset">
                            <Plus data-icon="inline-start" />
                            New asset
                        </Link>
                    </Button>
                </section>
                <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {assets.length === 0 ? (
                        <div className="flex flex-col gap-1 px-4 py-6">
                            <h2 className="font-medium">No assets yet</h2>
                            <p className="text-sm text-muted-foreground">
                                Add a home, a vehicle, or anything else the
                                household owns.
                            </p>
                        </div>
                    ) : (
                        <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                            {assets.map((asset) => (
                                <AssetRow key={asset.id} asset={asset} />
                            ))}
                        </ul>
                    )}

                    <div
                        className="flex items-baseline justify-between gap-3 border-t border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border"
                        data-test="asset-total"
                    >
                        <span className="text-sm text-muted-foreground">
                            Total in {total.currency}
                        </span>
                        <span
                            className="font-medium tabular-nums"
                            data-test="asset-balance"
                        >
                            {total.formatted}
                        </span>
                    </div>
                </section>
            </div>
        </>
    );
}

function AssetRow({ asset }: { asset: AssetSummary }) {
    const details = asset.note
        ? `${asset.type_label} · ${asset.valued_on_label} · ${asset.note}`
        : `${asset.type_label} · ${asset.valued_on_label}`;

    return (
        <li>
            <Link
                href={show(asset.id)}
                className="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
            >
                <EntityIcon name={asset.icon} />
                <span className="flex min-w-0 flex-1 flex-col">
                    <span className="flex min-w-0 items-center gap-2">
                        <span className="truncate font-medium">
                            {asset.name}
                        </span>
                        {asset.archived ? (
                            <Badge variant="outline" data-test="archived-asset">
                                Archived
                            </Badge>
                        ) : null}
                    </span>
                    <span className="truncate text-sm text-muted-foreground">
                        {details}
                    </span>
                </span>
                <span className="flex shrink-0 flex-col items-end">
                    <span
                        className="font-medium tabular-nums"
                        data-test="asset-balance"
                    >
                        {asset.current_value.formatted}
                    </span>
                    {asset.display_value ? (
                        <span className="text-sm text-muted-foreground tabular-nums">
                            {asset.display_value.formatted}
                        </span>
                    ) : null}
                </span>
            </Link>
        </li>
    );
}

Assets.layout = {
    breadcrumbs: [
        {
            title: 'Assets',
            href: index(),
        },
    ],
};
