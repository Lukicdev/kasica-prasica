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
import { index, store } from '@/routes/accounts';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts',
        href: index().url,
    },
    {
        title: 'Create Account',
        href: '/accounts/create',
    },
];

export default function CreateAccount() {
    const [type, setType] = useState<string>('');
    const [currency, setCurrency] = useState<string>('EUR');
    const [isShared, setIsShared] = useState<boolean>(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Account" />

            <div className="p-4">
                <div className="flex items-center gap-4 m-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index().url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Create Account
                        </h1>
                        <p className="text-muted-foreground">
                            Add a new financial account
                        </p>
                    </div>
                </div>

                <Form
                    {...store.form()}
                    className="space-y-6"
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <HeadingSmall
                                title="Account Information"
                                description="Enter the details for your new account"
                            />

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Account Name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        required
                                        placeholder="e.g., Main Checking"
                                        autoFocus
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">Account Type</Label>
                                    <Select
                                        required
                                        value={type}
                                        onValueChange={setType}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="cash">Cash</SelectItem>
                                            <SelectItem value="bank">Bank</SelectItem>
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
                                    <Label htmlFor="currency">Currency</Label>
                                    <Select
                                        required
                                        value={currency}
                                        onValueChange={setCurrency}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select currency" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="EUR">EUR</SelectItem>
                                            <SelectItem value="USD">USD</SelectItem>
                                            <SelectItem value="RSD">RSD</SelectItem>
                                            <SelectItem value="RUB">RUB</SelectItem>
                                            <SelectItem value="CAD">CAD</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="currency"
                                        value={currency}
                                        required
                                    />
                                    <InputError message={errors.currency} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="initial_balance">
                                        Initial Balance
                                    </Label>
                                    <Input
                                        id="initial_balance"
                                        name="initial_balance"
                                        type="number"
                                        step="0.01"
                                        required
                                        placeholder="0.00"
                                        defaultValue="0"
                                    />
                                    <InputError message={errors.initial_balance} />
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
                                    This is a shared account
                                </Label>
                            </div>
                            <InputError message={errors.is_shared} />

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    Create Account
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
                                        Account created successfully
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
