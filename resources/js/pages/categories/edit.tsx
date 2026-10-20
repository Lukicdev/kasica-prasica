import { Form, Head, router } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import { CategoryForm } from '@/components/category-form';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/categories';
import type { CategoryFormValues, CategoryKindOption } from '@/types/category';

export default function EditCategory({
    category,
    kinds,
}: {
    category: CategoryFormValues;
    kinds: CategoryKindOption[];
}) {
    const remove = (): void => {
        if (!confirm('Delete this category? This cannot be undone.')) {
            return;
        }

        router.delete(CategoryController.destroy.url(category.id));
    };

    return (
        <>
            <Head title={`Edit ${category.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <section className="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-col gap-1">
                        <h1 className="text-lg font-semibold tracking-tight">
                            Edit {category.name}
                        </h1>
                        {category.description ? (
                            <p className="text-sm text-muted-foreground">
                                {category.label}
                            </p>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Rename it, or add a description people will see
                                when they pick it.
                            </p>
                        )}
                    </div>
                </section>

                <CategoryForm category={category} kinds={kinds} />

                <section className="flex flex-col gap-3 border-t pt-6">
                    <h2 className="font-medium">
                        {category.archived ? 'Archived' : 'Archive'}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Archived categories stay in the history and cannot be
                        picked for new transactions.
                    </p>
                    {category.archived ? (
                        <Form {...CategoryController.restore.form(category.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 w-full sm:w-auto"
                                    data-test="restore-category"
                                >
                                    Restore category
                                </Button>
                            )}
                        </Form>
                    ) : (
                        <Form {...CategoryController.archive.form(category.id)}>
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    variant="outline"
                                    disabled={processing}
                                    className="h-11 w-full sm:w-auto"
                                    data-test="archive-category"
                                >
                                    Archive category
                                </Button>
                            )}
                        </Form>
                    )}
                    {category.can_delete ? (
                        <Button
                            type="button"
                            variant="destructive"
                            className="h-11 w-full sm:w-auto"
                            onClick={remove}
                            data-test="delete-category"
                        >
                            Delete category
                        </Button>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            This category has transactions, so it can be
                            archived and cannot be deleted.
                        </p>
                    )}
                </section>

                {category.created_by || category.updated_by ? (
                    <p className="text-sm text-muted-foreground">
                        {category.created_by
                            ? `Added by ${category.created_by}.`
                            : null}{' '}
                        {category.updated_by
                            ? `Last changed by ${category.updated_by}.`
                            : null}
                    </p>
                ) : null}
            </div>
        </>
    );
}

EditCategory.layout = (props: {
    category: CategoryFormValues;
}): {
    breadcrumbs: { title: string; href: ReturnType<typeof index> }[];
} => ({
    breadcrumbs: [
        { title: 'Categories', href: index() },
        { title: props.category.name, href: edit(props.category.id) },
    ],
});
