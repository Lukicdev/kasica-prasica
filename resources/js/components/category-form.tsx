import { Form } from '@inertiajs/react';
import { useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import { IconPicker } from '@/components/icon-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import type { CategoryFormValues, CategoryKindOption } from '@/types/category';

const fieldClassName =
    'border-input h-11 w-full rounded-md border bg-transparent px-3 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:h-9 md:text-sm';

export function CategoryForm({
    category,
    kinds,
}: {
    category?: CategoryFormValues;
    kinds: CategoryKindOption[];
}) {
    const editing = category !== undefined;
    const [icon, setIcon] = useState<string | null>(category?.icon ?? null);

    return (
        <Form
            {...(editing
                ? CategoryController.update.form(category.id)
                : CategoryController.store.form())}
            className="flex flex-col gap-6"
        >
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            required
                            defaultValue={category?.name ?? ''}
                            placeholder="Groceries"
                            className="h-11 md:h-9"
                            data-test="category-name"
                        />
                        <InputError message={errors.name} />
                    </div>

                    <IconPicker id="icon" value={icon} onChange={setIcon} />
                    <InputError message={errors.icon} />

                    <div className="grid gap-2">
                        <Label htmlFor="kind">Kind</Label>
                        {category?.kind_locked ? (
                            <input
                                type="hidden"
                                name="kind"
                                value={category.kind}
                            />
                        ) : null}
                        <select
                            id="kind"
                            name={category?.kind_locked ? undefined : 'kind'}
                            required
                            defaultValue={category?.kind ?? 'expense'}
                            disabled={category?.kind_locked ?? false}
                            className={cn(
                                fieldClassName,
                                'disabled:cursor-not-allowed disabled:opacity-50',
                            )}
                        >
                            {kinds.map((kind) => (
                                <option key={kind.value} value={kind.value}>
                                    {kind.label}
                                </option>
                            ))}
                        </select>
                        {category?.kind_locked ? (
                            <p className="text-sm text-muted-foreground">
                                Kind stays the same once the category has
                                transactions.
                            </p>
                        ) : null}
                        <InputError message={errors.kind} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            name="description"
                            defaultValue={category?.description ?? ''}
                            rows={2}
                            placeholder="A1: mobile provider"
                            className={cn(fieldClassName, 'h-auto py-2')}
                            data-test="category-description-input"
                        />
                        <p className="text-sm text-muted-foreground">
                            Shown in the list and when you pick this category.
                        </p>
                        <InputError message={errors.description} />
                    </div>

                    <Button
                        type="submit"
                        disabled={processing}
                        className="h-11 w-full sm:w-auto"
                        data-test="save-category"
                    >
                        {editing ? 'Save changes' : 'Create category'}
                    </Button>
                </>
            )}
        </Form>
    );
}
