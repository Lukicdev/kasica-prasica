import { Head } from '@inertiajs/react';
import { CategoryForm } from '@/components/category-form';
import { create, index } from '@/routes/categories';
import type { CategoryKindOption } from '@/types/category';

export default function CreateCategory({
    kinds,
}: {
    kinds: CategoryKindOption[];
}) {
    return (
        <>
            <Head title="New category" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            New category
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Choose income or expense. Add a short description if
                            the name needs explaining.
                        </p>
                    </div>
                </section>
                <CategoryForm kinds={kinds} />
            </div>
        </>
    );
}

CreateCategory.layout = {
    breadcrumbs: [
        {
            title: 'Categories',
            href: index(),
        },
        {
            title: 'New category',
            href: create(),
        },
    ],
};
