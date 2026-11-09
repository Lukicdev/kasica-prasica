import { Head, Link } from '@inertiajs/react';
import { EntityIcon } from '@/components/entity-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { edit, index, show } from '@/routes/assets';
import type { AssetDetail } from '@/types/asset';

export default function AssetShow({ asset }: { asset: AssetDetail }) {
    return (
        <>
            <Head title={asset.name} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="flex items-center gap-2 text-lg font-semibold tracking-tight">
                            <EntityIcon name={asset.icon} />
                            {asset.name}
                        </h1>
                        <div className="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <span>{asset.type_label}</span>
                            <span aria-hidden="true">·</span>
                            <span>{asset.currency}</span>
                            {asset.archived ? (
                                <Badge variant="outline">Archived</Badge>
                            ) : null}
                        </div>
                        {asset.note ? (
                            <p className="text-sm text-muted-foreground">
                                {asset.note}
                            </p>
                        ) : null}
                    </div>
                    <Button
                        asChild
                        variant="outline"
                        className="h-11 w-full sm:w-auto md:h-9"
                    >
                        <Link href={edit(asset.id)}>Edit</Link>
                    </Button>
                </section>

                <section
                    aria-label="Current value"
                    className="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                >
                    <p className="text-sm text-muted-foreground">
                        Current value
                    </p>
                    <p
                        className="text-2xl font-semibold tracking-tight tabular-nums"
                        data-test="asset-balance"
                    >
                        {asset.current_value.formatted}
                    </p>
                    {asset.display_value ? (
                        <p className="text-sm text-muted-foreground tabular-nums">
                            {asset.display_value.formatted}
                        </p>
                    ) : null}
                    <p className="text-sm text-muted-foreground">
                        {asset.valuation_label} on {asset.valued_on_label}
                    </p>
                </section>

                {asset.updated_by ? (
                    <p className="text-sm text-muted-foreground">
                        Last changed by {asset.updated_by}.
                    </p>
                ) : null}
            </div>
        </>
    );
}

AssetShow.layout = (props: {
    asset: AssetDetail;
}): { breadcrumbs: { title: string; href: ReturnType<typeof show> }[] } => ({
    breadcrumbs: [
        { title: 'Assets', href: index() },
        { title: props.asset.name, href: show(props.asset.id) },
    ],
});
