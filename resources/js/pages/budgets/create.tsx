import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
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
import { index, store } from '@/routes/budgets';
import { type BreadcrumbItem } from '@/types';

interface ExpenseCategory {
    id: number;
    name: string;
}

interface CreateBudgetProps {
    expenseCategories: ExpenseCategory[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Budgets',
        href: index().url,
    },
    {
        title: 'Create Budget',
        href: '/budgets/create',
    },
];

export default function CreateBudget({
    expenseCategories,
}: CreateBudgetProps) {
    const [categoryId, setCategoryId] = useState<string>('');
    const [periodMonth, setPeriodMonth] = useState<string>('');

    const periodValue = periodMonth ? `${periodMonth}-01` : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Budget" />

            <div className="p-4">
                <div className="m-2 flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Budget
                        </h1>
                        <p className="text-muted-foreground">
                            Set a monthly budget for an expense category
                        </p>
                    </div>
                </div>

                <Form
                    action={store.post().url}
                    method="post"
                    className="space-y-6"
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <HeadingSmall
                                title="Budget Details"
                                description="Select category, month and amount"
                            />

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="category_id">
                                        Category (expense only)
                                    </Label>
                                    <Select
                                        required
                                        value={categoryId}
                                        onValueChange={setCategoryId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select expense category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {expenseCategories.map(
                                                (category) => (
                                                    <SelectItem
                                                        key={category.id}
                                                        value={category.id.toString()}
                                                    >
                                                        {category.name}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="category_id"
                                        value={categoryId}
                                        required
                                    />
                                    <InputError message={errors.category_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="period">Month</Label>
                                    <Input
                                        id="period"
                                        type="month"
                                        required
                                        value={periodMonth}
                                        onChange={(e) =>
                                            setPeriodMonth(e.target.value)
                                        }
                                    />
                                    <input
                                        type="hidden"
                                        name="period"
                                        value={periodValue}
                                        required
                                    />
                                    <InputError message={errors.period} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="amount">Amount</Label>
                                    <Input
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        required
                                        placeholder="0.00"
                                    />
                                    <InputError message={errors.amount} />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    Create Budget
                                </Button>

                                <Button variant="outline" asChild>
                                    <Link href={index().url}>Cancel</Link>
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-neutral-600">
                                        Budget created successfully
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
