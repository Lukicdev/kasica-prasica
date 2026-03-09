import { Transition } from '@headlessui/react';
import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { index, show, update } from '@/routes/budgets';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Budgets',
        href: index().url,
    },
];

interface Budget {
    id: number;
    name: string;
    amount: string;
}

interface BudgetsEditProps {
    budget: Budget;
}

export default function EditBudget({ budget }: BudgetsEditProps) {
    const [name, setName] = useState<string>(budget.name);
    const [amount, setAmount] = useState<string>(budget.amount);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Budget" />

            <div className="p-4">
                <div className="m-2 flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={show(budget.id).url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Budget
                        </h1>
                        <p className="text-muted-foreground">
                            Update the budget name and amount
                        </p>
                    </div>
                </div>

                <Form
                    action={update.put(budget.id).url}
                    method="put"
                    className="space-y-6"
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <HeadingSmall
                                title="Budget Details"
                                description="Update the name and amount"
                            />

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        type="text"
                                        required
                                        value={name}
                                        onChange={(e) =>
                                            setName(e.target.value)
                                        }
                                        placeholder="e.g. Monthly groceries"
                                    />
                                    <InputError message={errors.name} />
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
                                        value={amount}
                                        onChange={(e) =>
                                            setAmount(e.target.value)
                                        }
                                        placeholder="0.00"
                                    />
                                    <InputError message={errors.amount} />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    Update Budget
                                </Button>

                                <Button variant="outline" asChild>
                                    <Link href={show(budget.id).url}>
                                        Cancel
                                    </Link>
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-neutral-600">
                                        Budget updated successfully
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
