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
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold tracking-tight">
                        New category
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Choose income or expense. Add a short description if the
                        name needs explaining.
                    </p>
                </div>
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
