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
import { index, show, update } from '@/routes/transactions';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: index().url,
    },
];

interface Account {
    id: number;
    name: string;
}

interface Category {
    id: number;
    name: string;
    type: string;
}

interface Transaction {
    id: number;
    account_id: number;
    category_id: number | null;
    type: string;
    amount: string;
    description: string | null;
    transaction_date: string;
}

interface TransactionEditProps {
    transaction: Transaction;
    accounts: Account[];
    categories: Category[];
}

export default function TransactionEdit({
    transaction,
    accounts,
    categories,
}: TransactionEditProps) {
    const [type, setType] = useState<string>(transaction.type);
    const [accountId, setAccountId] = useState<string>(transaction.account_id.toString());
    const [categoryId, setCategoryId] = useState<string>(
        transaction.category_id?.toString() || ''
    );
    const [transactionDate, setTransactionDate] = useState<string>(
        transaction.transaction_date.split('T')[0]
    );

    const filteredCategories = type
        ? categories.filter((cat) => cat.type === type)
        : categories;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Transaction ${transaction.id}`} />

            <div className="p-4">
                <div className="flex items-center gap-4 m-2">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={show(transaction.id).url}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold">
                            Edit Transaction
                        </h1>
                        <p className="text-muted-foreground">
                            Update transaction information
                        </p>
                    </div>
                </div>

                <Form
                    action={update.put(transaction.id).url}
                    method="put"
                    className="space-y-6"
                    options={{
                        preserveScroll: true,
                    }}
                >
                    {({ processing, recentlySuccessful, errors }) => (
                        <>
                            <HeadingSmall
                                title="Transaction Information"
                                description="Update the details for this transaction"
                            />

                            <div className="grid gap-6 md:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="account_id">Account</Label>
                                    <Select
                                        required
                                        value={accountId}
                                        onValueChange={setAccountId}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select account" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {accounts.map((account) => (
                                                <SelectItem
                                                    key={account.id}
                                                    value={account.id.toString()}
                                                >
                                                    {account.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <input
                                        type="hidden"
                                        name="account_id"
                                        value={accountId}
                                        required
                                    />
                                    <InputError message={errors.account_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="type">Transaction Type</Label>
                                    <Select
                                        required
                                        value={type}
                                        onValueChange={(value) => {
                                            setType(value);
                                            if (value === 'transfer') {
                                                setCategoryId('');
                                            }
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="income">Income</SelectItem>
                                            <SelectItem value="expense">Expense</SelectItem>
                                            <SelectItem value="transfer">Transfer</SelectItem>
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

                                {type !== 'transfer' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="category_id">Category</Label>
                                        <Select
                                            value={categoryId}
                                            onValueChange={setCategoryId}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {filteredCategories.map((category) => (
                                                    <SelectItem
                                                        key={category.id}
                                                        value={category.id.toString()}
                                                    >
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <input
                                            type="hidden"
                                            name="category_id"
                                            value={categoryId || ''}
                                        />
                                        <InputError message={errors.category_id} />
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="amount">Amount</Label>
                                    <Input
                                        id="amount"
                                        name="amount"
                                        type="number"
                                        step="0.01"
                                        required
                                        defaultValue={transaction.amount}
                                        min="0.01"
                                    />
                                    <InputError message={errors.amount} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="transaction_date">Transaction Date</Label>
                                    <Input
                                        id="transaction_date"
                                        name="transaction_date"
                                        type="date"
                                        required
                                        value={transactionDate}
                                        onChange={(e) => setTransactionDate(e.target.value)}
                                    />
                                    <InputError message={errors.transaction_date} />
                                </div>

                                <div className="grid gap-2 md:col-span-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Input
                                        id="description"
                                        name="description"
                                        defaultValue={transaction.description || ''}
                                        placeholder="Optional description"
                                    />
                                    <InputError message={errors.description} />
                                </div>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} type="submit">
                                    Update Transaction
                                </Button>

                                <Button variant="outline" asChild>
                                    <Link href={show(transaction.id).url}>Cancel</Link>
                                </Button>

                                <Transition
                                    show={recentlySuccessful}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-neutral-600">
                                        Transaction updated successfully
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
