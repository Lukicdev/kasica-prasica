import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { EntityIcon } from '@/components/entity-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/categories';
import type { CategoryGroup, CategorySummary } from '@/types/category';

export default function Categories({ groups }: { groups: CategoryGroup[] }) {
    return (
        <>
            <Head title="Categories" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Categories
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Income and expenses. A description shows here and
                            when you pick a category.
                        </p>
                    </div>
                    <Button asChild className="h-11 w-full sm:w-auto md:h-9">
                        <Link href={create()} data-test="new-category">
                            <Plus data-icon="inline-start" />
                            New category
                        </Link>
                    </Button>
                </section>

                <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                    {groups.map((group, groupIndex) => (
                        <CategoryGroupList
                            key={group.kind}
                            group={group}
                            divided={groupIndex > 0}
                        />
                    ))}
                </section>
            </div>
        </>
    );
}

function CategoryGroupList({
    group,
    divided,
}: {
    group: CategoryGroup;
    divided: boolean;
}) {
    const categories = [...group.categories, ...group.archived];

    return (
        <div
            className={
                divided
                    ? 'border-t border-sidebar-border/70 dark:border-sidebar-border'
                    : undefined
            }
            aria-label={group.label}
        >
            <h2 className="px-4 py-3 text-sm font-medium text-muted-foreground">
                {group.label}
            </h2>
            {categories.length === 0 ? (
                <p className="border-t border-sidebar-border/70 px-4 py-6 text-sm text-muted-foreground dark:border-sidebar-border">
                    {group.kind === 'income'
                        ? 'No income categories yet.'
                        : 'No expense categories yet.'}
                </p>
            ) : (
                <ul className="divide-y divide-sidebar-border/70 border-t border-sidebar-border/70 dark:divide-sidebar-border dark:border-sidebar-border">
                    {categories.map((category) => (
                        <CategoryRow key={category.id} category={category} />
                    ))}
                </ul>
            )}
        </div>
    );
}

function CategoryRow({ category }: { category: CategorySummary }) {
    return (
        <li data-test="category">
            <Link
                href={edit(category.id)}
                className="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-accent focus-visible:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
            >
                <EntityIcon name={category.icon} color={category.color} />
                <span className="flex min-w-0 flex-1 flex-col">
                    <span className="flex min-w-0 items-center gap-2">
                        <span className="truncate font-medium">
                            {category.name}
                        </span>
                        {category.archived ? (
                            <Badge
                                variant="outline"
                                data-test="archived-category"
                            >
                                Archived
                            </Badge>
                        ) : null}
                    </span>
                    {category.description ? (
                        <span
                            className="truncate text-sm text-muted-foreground"
                            data-test="category-description"
                        >
                            {category.description}
                        </span>
                    ) : null}
                </span>
            </Link>
        </li>
    );
}

Categories.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: index(),
        },
    ],
};
