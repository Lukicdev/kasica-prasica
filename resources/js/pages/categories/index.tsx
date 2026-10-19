import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index, reorder } from '@/routes/categories';
import type { CategoryGroup, CategorySummary } from '@/types/category';

function reorderError(errors: unknown): string | null {
    if (typeof errors !== 'object' || errors === null || !('ids' in errors)) {
        return null;
    }

    const message = errors.ids;

    return typeof message === 'string' ? message : null;
}

function move(
    kind: string,
    categories: CategorySummary[],
    index: number,
    direction: -1 | 1,
    onStart: () => void,
    onFinish: () => void,
): void {
    const next = index + direction;

    if (next < 0 || next >= categories.length) {
        return;
    }

    const ids = categories.map((category) => category.id);
    const current = ids[index];
    const target = ids[next];

    if (current === undefined || target === undefined) {
        return;
    }

    ids[index] = target;
    ids[next] = current;

    router.patch(
        reorder.url(),
        { kind, ids },
        { preserveScroll: true, onStart, onFinish },
    );
}

export default function Categories({ groups }: { groups: CategoryGroup[] }) {
    const error = reorderError(usePage().props.errors);
    const [movingId, setMovingId] = useState<number | null>(null);

    return (
        <>
            <Head title="Categories" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            Categories
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Income and expenses, in the order you want. A
                            description shows here and when you pick a
                            category.
                        </p>
                    </div>
                    <Button asChild className="h-11 w-full sm:w-auto">
                        <Link href={create()} data-test="new-category">
                            New category
                        </Link>
                    </Button>
                </div>

                {error ? (
                    <p className="text-sm text-destructive" role="alert">
                        {error}
                    </p>
                ) : null}

                {groups.map((group) => (
                    <section
                        key={group.kind}
                        className="flex flex-col gap-3"
                        aria-label={group.label}
                    >
                        <h2 className="text-sm font-medium text-muted-foreground">
                            {group.label}
                        </h2>
                        {group.categories.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {group.kind === 'income'
                                    ? 'No income categories yet.'
                                    : 'No expense categories yet.'}
                            </p>
                        ) : (
                            <ul className="flex flex-col gap-3">
                                {group.categories.map((category, index) => (
                                    <CategoryRow
                                        key={category.id}
                                        category={category}
                                        canMoveUp={index > 0}
                                        canMoveDown={
                                            index < group.categories.length - 1
                                        }
                                        moving={movingId !== null}
                                        onMove={(direction) =>
                                            move(
                                                group.kind,
                                                group.categories,
                                                index,
                                                direction,
                                                () => setMovingId(category.id),
                                                () => setMovingId(null),
                                            )
                                        }
                                    />
                                ))}
                            </ul>
                        )}
                        {group.archived.length > 0 ? (
                            <div className="flex flex-col gap-3">
                                <h3 className="text-sm font-medium">
                                    Archived
                                </h3>
                                <p className="text-sm text-muted-foreground">
                                    Archived categories stay in the history and
                                    are left out when you pick a category.
                                </p>
                                <ul className="flex flex-col gap-3">
                                    {group.archived.map((category) => (
                                        <CategoryRow
                                            key={category.id}
                                            category={category}
                                            canMoveUp={false}
                                            canMoveDown={false}
                                            moving={false}
                                            onMove={() => undefined}
                                        />
                                    ))}
                                </ul>
                            </div>
                        ) : null}
                    </section>
                ))}
            </div>
        </>
    );
}

function CategoryRow({
    category,
    canMoveUp,
    canMoveDown,
    moving,
    onMove,
}: {
    category: CategorySummary;
    canMoveUp: boolean;
    canMoveDown: boolean;
    moving: boolean;
    onMove: (direction: -1 | 1) => void;
}) {
    return (
        <li
            className="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border"
            data-test="category"
        >
            <div className="flex flex-col gap-1">
                <span className="flex flex-wrap items-center gap-2">
                    <span className="font-medium">{category.name}</span>
                    {category.archived ? (
                        <Badge variant="outline" data-test="archived-category">
                            Archived
                        </Badge>
                    ) : null}
                </span>
                {category.description ? (
                    <span
                        className="text-sm text-muted-foreground"
                        data-test="category-description"
                    >
                        {category.description}
                    </span>
                ) : null}
            </div>
            <div className="flex flex-wrap gap-2">
                {category.archived ? null : (
                    <>
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11"
                            disabled={!canMoveUp || moving}
                            aria-label={`Move ${category.name} up`}
                            onClick={() => onMove(-1)}
                        >
                            Up
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            className="h-11"
                            disabled={!canMoveDown || moving}
                            aria-label={`Move ${category.name} down`}
                            onClick={() => onMove(1)}
                        >
                            Down
                        </Button>
                    </>
                )}
                <Button
                    asChild
                    variant="outline"
                    className="h-11 flex-1 sm:flex-none"
                >
                    <Link href={edit(category.id)}>Edit</Link>
                </Button>
            </div>
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
