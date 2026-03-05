import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { index, show, update } from '@/routes/categories';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: index().url,
    },
];

interface Category {
    id: number;
    name: string;
    type: string;
    color: string;
    user_id: number | null;
}

interface CategoryEditProps {
    category: Category;
}

export default function CategoryEdit({ category }: CategoryEditProps) {
    const [type, setType] = useState<string>(category.type);
    const [color, setColor] = useState<string>(category.color);
    const [isShared, setIsShared] = useState<boolean>(category.user_id === null);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${category.name}`} />

            <div className="p-4">
                <div className="flex items-center gap-4 m-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={show(category.id).url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Category
                        </h1>
                        <p className="text-muted-foreground">
                            Update category information
                        </p>
                    </div>
                </div>

                <Form
                    action={update.put(category.id).url}
                    method="put"
                    className="space-y-6"
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <HeadingSmall
                                title="Category Information"
                                description="Update the details for this category"
                            />

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Category Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        defaultValue={category.name}
                                        placeholder="e.g., Groceries"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">Category Type</Label>
                                    <Select
                                        required
                                        value={type}
                                        onValueChange={setType}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="income">Income</SelectItem>
                                            <SelectItem value="expense">Expense</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="type"
                                        value={type}
                                        required
                                    />
                                    <InputError message={errors.type} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="color">Color</Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            id="color"
                                            name="color"
                                            type="color"
                                            required
                                            value={color}
                                            onChange={(e) => setColor(e.target.value)}
                                            className="h-10 w-20 cursor-pointer"
                                        />
                                        <Input
                                            type="text"
                                            value={color}
                                            onChange={(e) => setColor(e.target.value)}
                                            placeholder="#3B82F6"
                                            maxLength={7}
                                            className="flex-1"
                                        />
                                    </div>
                                    <InputError message={errors.color} />
                                </div>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="is_shared"
                                    checked={isShared}
                                    onCheckedChange={(checked) =>
                                        setIsShared(checked === true)
                                    }
                                />
                                <input
                                    type="hidden"
                                    name="is_shared"
                                    value={isShared ? '1' : '0'}
                                />
                                <Label
                                    htmlFor="is_shared"
                                    className="text-sm font-normal leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                                >
                                    This is a shared category
                                </Label>
                            </div>
                            <InputError message={errors.is_shared} />

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    Update Category
                                </Button>

                                <Button variant="outline" asChild>
                                    <Link href={show(category.id).url}>Cancel</Link>
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-neutral-600">
                                        Category updated successfully
                                    </p>
                                </Transition>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </AppLayout>
    );
}
