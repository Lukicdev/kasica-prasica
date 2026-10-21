import { Form } from '@inertiajs/react';
import { useState } from 'react';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import { FormSelect } from '@/components/form-select';
import { IconPicker } from '@/components/icon-picker';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { CategoryFormValues, CategoryKindOption } from '@/types/category';

export function CategoryForm({
    category,
    kinds,
}: {
    category?: CategoryFormValues;
    kinds: CategoryKindOption[];
}) {
    const editing = category !== undefined;
    const [icon, setIcon] = useState<string | null>(category?.icon ?? null);
    const [kind, setKind] = useState(category?.kind ?? 'expense');

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
                        <FormSelect
                            id="kind"
                            name={category?.kind_locked ? undefined : 'kind'}
                            required
                            value={kind}
                            onValueChange={setKind}
                            disabled={category?.kind_locked ?? false}
                            invalid={Boolean(errors.kind)}
                            options={kinds.map((option) => ({
                                value: option.value,
                                label: option.label,
                            }))}
                        />
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
                        <Textarea
                            id="description"
                            name="description"
                            defaultValue={category?.description ?? ''}
                            rows={2}
                            placeholder="A1: mobile provider"
                            aria-invalid={errors.description ? true : undefined}
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
