import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Edit, Trash2, Wallet } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { destroy, edit, index } from '@/routes/accounts';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Accounts',
        href: index().url,
    },
];

interface Account {
    id: number;
    name: string;
    type: string;
    currency: string;
    initial_balance: string;
    is_shared: boolean;
    user: {
        id: number;
        name: string;
    };
}

interface AccountShowProps {
    account: Account;
}

export default function AccountShow({ account }: AccountShowProps) {
    const handleDelete = () => {
        router.delete(destroy(account.id).url, {
            onSuccess: () => {
                router.visit(index().url);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={account.name} />

            <div className="p-4">
                <div className="flex items-center justify-between m-2">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={index().url}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-semibold">
                                {account.name}
                            </h1>
                            <p className="text-muted-foreground">
                                Account details
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(account.id).url}>
                                <Edit className="mr-2 size-4" />
                                Edit
                            </Link>
                        </Button>
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive">
                                    <Trash2 className="mr-2 size-4" />
                                    Delete
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete Account</DialogTitle>
                                    <DialogDescription>
                                        Are you sure you want to delete this
                                        account? This action cannot be undone.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button
                                        variant="destructive"
                                        onClick={handleDelete}
                                    >
                                        Delete Account
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Wallet className="size-5" />
                                <CardTitle>Account Information</CardTitle>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <CardDescription>Name</CardDescription>
                                <p className="text-sm font-medium">
                                    {account.name}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Type</CardDescription>
                                <p className="text-sm font-medium">
                                    {account.type.charAt(0).toUpperCase() +
                                        account.type.slice(1)}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Currency</CardDescription>
                                <p className="text-sm font-medium">
                                    {account.currency}
                                </p>
                            </div>
                            <div>
                                <CardDescription>Shared Account</CardDescription>
                                <p className="text-sm font-medium">
                                    {account.is_shared ? 'Yes' : 'No'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Balance</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold">
                                {parseFloat(account.initial_balance).toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2,
                                })}{' '}
                                {account.currency}
                            </div>
                            <CardDescription className="mt-2">
                                Initial balance
                            </CardDescription>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
